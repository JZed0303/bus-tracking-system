<?php

namespace App\Http\Controllers\Api;

use App\Events\CallAccepted;
use App\Events\CallEnded;
use App\Events\CallRejected;
use App\Events\IceCandidateSent;
use App\Events\IncomingCall;
use App\Events\WebRTCAnswerSent;
use App\Events\WebRTCOfferSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateVideoCallRequest;
use App\Http\Requests\Api\SendIceCandidateRequest;
use App\Http\Requests\Api\SendWebRTCAnswerRequest;
use App\Http\Requests\Api\SendWebRTCOfferRequest;
use App\Models\Bus;
use App\Models\User;
use App\Models\VideoCall;
use App\Models\VideoCallIceCandidate;
use App\Models\VideoCallRecording;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class VideoCallController extends Controller
{
    private function shouldPersistSessionDescriptions(): bool
    {
        return true;
    }

    private function shouldPersistIceCandidates(): bool
    {
        return true;
    }

    public function start(CreateVideoCallRequest $request): JsonResponse
    {
        $actor = $this->resolveAuthenticatedParticipant($request);
        $validated = $request->validated();

        if (($validated['caller_type'] ?? null) !== $actor['type'] || (int) ($validated['caller_id'] ?? 0) !== $actor['id']) {
            throw ValidationException::withMessages([
                'caller_id' => ['The authenticated participant must match the caller.'],
            ]);
        }

        if ($validated['callee_type'] === $actor['type'] && (int) $validated['callee_id'] === $actor['id']) {
            throw ValidationException::withMessages([
                'callee_id' => ['The caller and callee must be different participants.'],
            ]);
        }

        $this->assertSupportedParticipantPair($actor['type'], $validated['callee_type']);
        $this->assertParticipantExists($validated['callee_type'], (int) $validated['callee_id']);

        $videoCall = VideoCall::create([
            'caller_type' => $actor['type'],
            'caller_id' => $actor['id'],
            'callee_type' => $validated['callee_type'],
            'callee_id' => $validated['callee_id'],
            'status' => VideoCall::STATUS_RINGING,
            'started_at' => now(),
        ]);

        Log::info('Video call started', [
            'video_call_id' => $videoCall->id,
            'caller_type' => $videoCall->caller_type,
            'caller_id' => $videoCall->caller_id,
            'callee_type' => $videoCall->callee_type,
            'callee_id' => $videoCall->callee_id,
            'status' => $videoCall->status,
        ]);

        $this->dispatchSignalEvent(new IncomingCall(
            $this->participantBroadcastChannels($videoCall),
            $this->signalMetadataPayload($videoCall)
        ));

        return response()->json([
            'message' => 'Video call started successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
            ],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $this->assertParticipantCanAccessCall($request, $videoCall);

        return response()->json([
            'message' => 'Video call fetched successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
            ],
        ]);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $actor = $this->assertParticipantCanAccessCall($request, $videoCall);
        $this->assertActorIsCallee($actor, $videoCall);
        $previousStatus = $videoCall->status;

        $videoCall->update([
            'status' => VideoCall::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $videoCall->refresh();

        Log::info('Video call accepted', [
            'video_call_id' => $videoCall->id,
            'previous_status' => $previousStatus,
            'status' => $videoCall->status,
            'caller_type' => $videoCall->caller_type,
            'caller_id' => $videoCall->caller_id,
            'callee_type' => $videoCall->callee_type,
            'callee_id' => $videoCall->callee_id,
            'has_offer' => $videoCall->hasOffer(),
            'has_answer' => $videoCall->hasAnswer(),
            'accepted_at' => $videoCall->accepted_at?->toIso8601String(),
        ]);

        $this->dispatchSignalEvent(new CallAccepted(
            $this->participantBroadcastChannels($videoCall),
            $this->signalMetadataPayload($videoCall)
        ));

        return response()->json([
            'message' => 'Video call accepted successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
            ],
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $actor = $this->assertParticipantCanAccessCall($request, $videoCall);
        $this->assertActorIsCallee($actor, $videoCall);

        $videoCall->update([
            'status' => VideoCall::STATUS_REJECTED,
            'ended_at' => now(),
        ]);

        VideoCallIceCandidate::query()
            ->where('video_call_id', $videoCall->id)
            ->delete();

        $videoCall->refresh();

        $this->dispatchSignalEvent(new CallRejected(
            $this->participantBroadcastChannels($videoCall),
            $this->signalMetadataPayload($videoCall)
        ));

        return response()->json([
            'message' => 'Video call rejected successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
            ],
        ]);
    }

    public function storeOffer(SendWebRTCOfferRequest $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $actor = $this->assertParticipantCanAccessCall($request, $videoCall);
        $this->assertActorIsCaller($actor, $videoCall);
        $offer = $request->validated('offer');
        $persist = $this->shouldPersistSessionDescriptions();
        $recipientChannels = $this->recipientChannelsForOffer($videoCall);

        if ($persist) {
            $videoCall->update([
                'offer' => $offer,
                'status' => $videoCall->status === VideoCall::STATUS_CONNECTED
                    ? VideoCall::STATUS_CONNECTED
                    : $videoCall->status,
            ]);

            $videoCall->refresh();
        }

        Log::info('Video call offer stored', [
            'video_call_id' => $videoCall->id,
            'status' => $videoCall->status,
            'caller_type' => $videoCall->caller_type,
            'caller_id' => $videoCall->caller_id,
            'callee_type' => $videoCall->callee_type,
            'callee_id' => $videoCall->callee_id,
            'has_offer' => $persist ? $videoCall->hasOffer() : true,
            'has_answer' => $videoCall->hasAnswer(),
            'persisted' => $persist,
        ]);

        $signalPayload = $this->signalMetadataPayload($videoCall);
        if (!$persist) {
            $signalPayload['offer'] = $offer;
            $signalPayload['has_offer'] = true;
            $signalPayload['updated_at'] = now()->toIso8601String();
        }

        $this->dispatchSignalEvent(new WebRTCOfferSent(
            $recipientChannels,
            $signalPayload
        ));

        return response()->json([
            'message' => 'WebRTC offer saved successfully.',
            'data' => [
                'video_call' => $persist
                    ? $this->videoCallPayload($videoCall)
                    : array_merge($this->videoCallPayload($videoCall), [
                        'offer' => $offer,
                        'has_offer' => true,
                        'updated_at' => now()->toIso8601String(),
                    ]),
            ],
        ]);
    }

    public function storeAnswer(SendWebRTCAnswerRequest $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $actor = $this->assertParticipantCanAccessCall($request, $videoCall);
        $this->assertActorIsCallee($actor, $videoCall);
        $answer = $request->validated('answer');
        $persist = $this->shouldPersistSessionDescriptions();
        $recipientChannels = $this->recipientChannelsForAnswer($videoCall);

        if ($persist) {
            $videoCall->update([
                'answer' => $answer,
                'status' => VideoCall::STATUS_CONNECTED,
            ]);

            $videoCall->refresh();
        } else {
            $videoCall->update([
                'status' => VideoCall::STATUS_CONNECTED,
            ]);
            $videoCall->refresh();
        }

        Log::info('Video call answer stored', [
            'video_call_id' => $videoCall->id,
            'status' => $videoCall->status,
            'caller_type' => $videoCall->caller_type,
            'caller_id' => $videoCall->caller_id,
            'callee_type' => $videoCall->callee_type,
            'callee_id' => $videoCall->callee_id,
            'has_offer' => $videoCall->hasOffer(),
            'has_answer' => $persist ? $videoCall->hasAnswer() : true,
            'persisted' => $persist,
        ]);

        $signalPayload = $this->signalMetadataPayload($videoCall);
        if (!$persist) {
            $signalPayload['answer'] = $answer;
            $signalPayload['has_offer'] = true;
            $signalPayload['has_answer'] = true;
            $signalPayload['updated_at'] = now()->toIso8601String();
        }

        $this->dispatchSignalEvent(new WebRTCAnswerSent(
            $recipientChannels,
            $signalPayload
        ));

        return response()->json([
            'message' => 'WebRTC answer saved successfully.',
            'data' => [
                'video_call' => $persist
                    ? $this->videoCallPayload($videoCall)
                    : array_merge($this->videoCallPayload($videoCall), [
                        'answer' => $answer,
                        'has_offer' => true,
                        'has_answer' => true,
                        'updated_at' => now()->toIso8601String(),
                    ]),
            ],
        ]);
    }

    public function storeIce(SendIceCandidateRequest $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $actor = $this->assertParticipantCanAccessCall($request, $videoCall);
        $validated = $request->validated();
        $candidate = $validated['candidate'];
        $persist = $this->shouldPersistIceCandidates();
        $recipientChannels = $this->recipientChannelsForIce($videoCall, $actor['type'], $actor['id']);

        if (($validated['sender_type'] ?? null) !== $actor['type'] || (int) ($validated['sender_id'] ?? 0) !== $actor['id']) {
            throw ValidationException::withMessages([
                'sender_id' => ['The authenticated participant must match the ICE candidate sender.'],
            ]);
        }

        if ($persist) {
            $iceCandidate = VideoCallIceCandidate::firstOrCreate(
                [
                    'video_call_id' => $videoCall->id,
                    'sender_type' => $actor['type'],
                    'sender_id' => $actor['id'],
                    'candidate' => $candidate['candidate'],
                    'sdp_mid' => $candidate['sdpMid'] ?? null,
                    'sdp_mline_index' => $candidate['sdpMLineIndex'] ?? null,
                ]
            );

            $candidatePayload = [
                'id' => $iceCandidate->id,
                'sender_type' => $iceCandidate->sender_type,
                'sender_id' => $iceCandidate->sender_id,
                'candidate' => [
                    'candidate' => $iceCandidate->candidate,
                    'sdpMid' => $iceCandidate->sdp_mid,
                    'sdpMLineIndex' => $iceCandidate->sdp_mline_index,
                ],
                'created_at' => $iceCandidate->created_at?->toIso8601String(),
            ];
        } else {
            $candidatePayload = [
                'id' => null,
                'sender_type' => $actor['type'],
                'sender_id' => $actor['id'],
                'candidate' => [
                    'candidate' => $candidate['candidate'],
                    'sdpMid' => $candidate['sdpMid'] ?? null,
                    'sdpMLineIndex' => $candidate['sdpMLineIndex'] ?? null,
                ],
                'created_at' => now()->toIso8601String(),
            ];
        }

        Log::info('Video call ICE candidate stored', [
            'video_call_id' => $videoCall->id,
            'candidate_id' => $candidatePayload['id'],
            'persisted' => $persist,
        ]);

        $signalPayload = array_merge($this->signalMetadataPayload($videoCall), [
            'candidate' => $candidatePayload,
            'sender_type' => $actor['type'],
            'sender_id' => $actor['id'],
        ]);
        $this->dispatchSignalEvent(new IceCandidateSent(
            $recipientChannels,
            $signalPayload
        ));

        return response()->json([
            'message' => 'ICE candidate received successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
                'ice_candidate' => $candidatePayload,
            ],
        ]);
    }

    public function getIce(Request $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $this->assertParticipantCanAccessCall($request, $videoCall);

        if (!$this->shouldPersistIceCandidates()) {
            return response()->json([
                'message' => 'ICE candidates fetched successfully.',
                'data' => [
                    'video_call' => $this->videoCallPayload($videoCall),
                    'ice_candidates' => [],
                ],
            ]);
        }

        $iceCandidates = $videoCall->iceCandidates()
            ->orderBy('id')
            ->get()
            ->map(function (VideoCallIceCandidate $candidate) {
                return [
                    'id' => $candidate->id,
                    'sender_type' => $candidate->sender_type,
                    'sender_id' => $candidate->sender_id,
                    'candidate' => [
                        'candidate' => $candidate->candidate,
                        'sdpMid' => $candidate->sdp_mid,
                        'sdpMLineIndex' => $candidate->sdp_mline_index,
                    ],
                    'created_at' => $candidate->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'ICE candidates fetched successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
                'ice_candidates' => $iceCandidates,
            ],
        ]);
    }

    public function end(Request $request, int $id): JsonResponse
    {
        $videoCall = $this->findVideoCall($id);
        $this->assertParticipantCanAccessCall($request, $videoCall);

        $videoCall->update([
            'status' => VideoCall::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        VideoCallIceCandidate::query()
            ->where('video_call_id', $videoCall->id)
            ->delete();

        $videoCall->refresh();

        $this->dispatchSignalEvent(new CallEnded(
            $this->participantBroadcastChannels($videoCall),
            $this->signalMetadataPayload($videoCall)
        ));

        return response()->json([
            'message' => 'Video call ended successfully.',
            'data' => [
                'video_call' => $this->videoCallPayload($videoCall),
            ],
        ]);
    }

    public function storeRecording(Request $request, int $id): JsonResponse
    {
        try {
            $videoCall = $this->findVideoCall($id);
            $actor = $this->assertParticipantCanAccessCall($request, $videoCall);

            $validated = $request->validate([
                'recording' => ['required', 'file', 'max:512000'],
                'recorded_by_type' => ['nullable', 'string', 'max:32'],
                'recorded_by_id' => ['nullable', 'integer'],
            ]);

            if (
                array_key_exists('recorded_by_type', $validated)
                && $validated['recorded_by_type'] !== null
                && $validated['recorded_by_type'] !== $actor['type']
            ) {
                throw ValidationException::withMessages([
                    'recorded_by_type' => ['The authenticated participant must match the recording uploader.'],
                ]);
            }

            if (
                array_key_exists('recorded_by_id', $validated)
                && $validated['recorded_by_id'] !== null
                && (int) $validated['recorded_by_id'] !== $actor['id']
            ) {
                throw ValidationException::withMessages([
                    'recorded_by_id' => ['The authenticated participant must match the recording uploader.'],
                ]);
            }

            $file = $validated['recording'];

            Log::info('Uploading video recording', [
                'video_call_id' => $videoCall->id,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            $disk = 'public';
            $directory = "video-calls/{$videoCall->id}";
            $filenameBase = sprintf(
                'call-%d-%s',
                $videoCall->id,
                now()->format('YmdHis')
            );

            $transcoded = $this->transcodeRecordingToMp4($file->getRealPath(), $filenameBase);
            $path = Storage::disk($disk)->putFileAs($directory, $transcoded['file'], $transcoded['filename']);

            if (!$path) {
                throw new \RuntimeException('Failed to store transcoded recording file.');
            }

            @unlink($transcoded['path']);

            $recording = VideoCallRecording::create([
                'video_call_id' => $videoCall->id,
                'recorded_by_type' => $actor['type'],
                'recorded_by_id' => $actor['id'],
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'video/mp4',
                'size_bytes' => $transcoded['size_bytes'],
            ]);

            return response()->json([
                'message' => 'Video call recording saved successfully.',
                'data' => [
                    'recording' => [
                        'id' => $recording->id,
                        'video_call_id' => $recording->video_call_id,
                        'disk' => $recording->disk,
                        'path' => $recording->path,
                        'url' => Storage::disk($disk)->url($recording->path),
                        'mime_type' => $recording->mime_type,
                        'size_bytes' => $recording->size_bytes,
                        'created_at' => $recording->created_at?->toIso8601String(),
                    ],
                ],
            ], 201);
        } catch (ValidationException|AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to store video recording', [
                'video_call_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to save video call recording.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array{file:\Illuminate\Http\File,filename:string,path:string,size_bytes:int}
     */
    private function transcodeRecordingToMp4(string $inputPath, string $filenameBase): array
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'video-call-mp4-');
        if ($outputPath === false) {
            throw new \RuntimeException('Could not allocate a temporary file for MP4 conversion.');
        }

        @unlink($outputPath);
        $outputPath .= '.mp4';

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $inputPath,
            '-c:v',
            'libx264',
            '-preset',
            'veryfast',
            '-crf',
            '30',
            '-maxrate',
            '900k',
            '-bufsize',
            '1800k',
            '-vf',
            'scale=trunc(iw/2)*2:trunc(ih/2)*2',
            '-c:a',
            'aac',
            '-b:a',
            '96k',
            '-movflags',
            '+faststart',
            $outputPath,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !is_file($outputPath)) {
            @unlink($outputPath);

            throw new \RuntimeException('FFmpeg failed to convert the recording to MP4.');
        }

        return [
            'file' => new \Illuminate\Http\File($outputPath),
            'filename' => "{$filenameBase}.mp4",
            'path' => $outputPath,
            'size_bytes' => filesize($outputPath) ?: 0,
        ];
    }


    private function findVideoCall(int $id): VideoCall
    {
        return VideoCall::query()->findOrFail($id);
    }

    /**
     * @return array{type:string,id:int,model:Authenticatable}
     */
    private function resolveAuthenticatedParticipant(Request $request): array
    {
        $actor = $request->user();

        if ($actor instanceof User) {
            return [
                'type' => 'user',
                'id' => (int) $actor->id,
                'model' => $actor,
            ];
        }

        if ($actor instanceof Bus) {
            return [
                'type' => 'bus',
                'id' => (int) $actor->id,
                'model' => $actor,
            ];
        }

        throw new AuthorizationException('Unauthenticated.');
    }

    /**
     * @return array{type:string,id:int,model:Authenticatable}
     */
    private function assertParticipantCanAccessCall(Request $request, VideoCall $videoCall): array
    {
        $actor = $this->resolveAuthenticatedParticipant($request);

        $isCaller = $actor['type'] === $videoCall->caller_type && $actor['id'] === (int) $videoCall->caller_id;
        $isCallee = $actor['type'] === $videoCall->callee_type && $actor['id'] === (int) $videoCall->callee_id;

        if (!$isCaller && !$isCallee) {
            throw new AuthorizationException('You are not a participant in this video call.');
        }

        return $actor;
    }

    /**
     * @param array{type:string,id:int,model:Authenticatable} $actor
     */
    private function assertActorIsCaller(array $actor, VideoCall $videoCall): void
    {
        if ($actor['type'] !== $videoCall->caller_type || $actor['id'] !== (int) $videoCall->caller_id) {
            throw new AuthorizationException('Only the caller can perform this action.');
        }
    }

    /**
     * @param array{type:string,id:int,model:Authenticatable} $actor
     */
    private function assertActorIsCallee(array $actor, VideoCall $videoCall): void
    {
        if ($actor['type'] !== $videoCall->callee_type || $actor['id'] !== (int) $videoCall->callee_id) {
            throw new AuthorizationException('Only the callee can perform this action.');
        }
    }

    private function assertSupportedParticipantPair(string $callerType, string $calleeType): void
    {
        $supportedPairs = [
            'user:bus',
            'bus:user',
        ];

        if (!in_array("{$callerType}:{$calleeType}", $supportedPairs, true)) {
            throw ValidationException::withMessages([
                'callee_type' => ['Video calls are only supported between a user and a bus.'],
            ]);
        }
    }

    private function assertParticipantExists(string $type, int $id): void
    {
        $exists = match ($type) {
            'user' => User::query()->whereKey($id)->exists(),
            'bus' => Bus::query()->whereKey($id)->exists(),
            default => false,
        };

        if (!$exists) {
            throw ValidationException::withMessages([
                'callee_id' => ['The selected call participant does not exist.'],
            ]);
        }
    }

    private function videoCallPayload(VideoCall $videoCall): array
    {
        return [
            'id' => $videoCall->id,
            'caller_type' => $videoCall->caller_type,
            'caller_id' => $videoCall->caller_id,
            'callee_type' => $videoCall->callee_type,
            'callee_id' => $videoCall->callee_id,
            'status' => $videoCall->status,
            'offer' => $videoCall->offer,
            'answer' => $videoCall->answer,
            'has_offer' => $videoCall->hasOffer(),
            'has_answer' => $videoCall->hasAnswer(),
            'started_at' => $videoCall->started_at?->toIso8601String(),
            'accepted_at' => $videoCall->accepted_at?->toIso8601String(),
            'ended_at' => $videoCall->ended_at?->toIso8601String(),
            'created_at' => $videoCall->created_at?->toIso8601String(),
            'updated_at' => $videoCall->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function participantBroadcastChannels(VideoCall $videoCall): array
    {
        return array_values(array_filter([
            $this->participantChannel($videoCall->caller_type, (int) $videoCall->caller_id),
            $this->participantChannel($videoCall->callee_type, (int) $videoCall->callee_id),
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function recipientChannelsForOffer(VideoCall $videoCall): array
    {
        return array_values(array_filter([
            $this->participantChannel($videoCall->callee_type, (int) $videoCall->callee_id),
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function recipientChannelsForAnswer(VideoCall $videoCall): array
    {
        return array_values(array_filter([
            $this->participantChannel($videoCall->caller_type, (int) $videoCall->caller_id),
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function recipientChannelsForIce(VideoCall $videoCall, string $senderType, int $senderId): array
    {
        if ($senderType === $videoCall->caller_type && $senderId === (int) $videoCall->caller_id) {
            return $this->recipientChannelsForOffer($videoCall);
        }

        return $this->recipientChannelsForAnswer($videoCall);
    }

    private function participantChannel(string $type, int $id): ?string
    {
        if ($type === 'user') {
            return "video-calls.user.{$id}";
        }

        if ($type === 'bus') {
            return "video-calls.bus.{$id}";
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function signalMetadataPayload(VideoCall $videoCall): array
    {
        return [
            'id' => $videoCall->id,
            'call_id' => $videoCall->id,
            'caller_type' => $videoCall->caller_type,
            'caller_id' => $videoCall->caller_id,
            'callee_type' => $videoCall->callee_type,
            'callee_id' => $videoCall->callee_id,
            'status' => $videoCall->status,
            'has_offer' => $videoCall->hasOffer(),
            'has_answer' => $videoCall->hasAnswer(),
            'started_at' => $videoCall->started_at?->toIso8601String(),
            'accepted_at' => $videoCall->accepted_at?->toIso8601String(),
            'ended_at' => $videoCall->ended_at?->toIso8601String(),
            'created_at' => $videoCall->created_at?->toIso8601String(),
            'updated_at' => $videoCall->updated_at?->toIso8601String(),
        ];
    }

    private function dispatchSignalEvent(object $event): void
    {
        broadcast($event);
    }
}
