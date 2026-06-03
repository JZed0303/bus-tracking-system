<?php

namespace Tests\Feature\Api;

use App\Models\VideoCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCallControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_OFFER_SDP = "v=0\r\n"
        . "o=- 1234567890 2 IN IP4 127.0.0.1\r\n"
        . "s=-\r\n"
        . "t=0 0\r\n"
        . "m=audio 9 UDP/TLS/RTP/SAVPF 111\r\n"
        . "a=mid:0\r\n";

    private const TEST_ANSWER_SDP = "v=0\r\n"
        . "o=- 9876543210 2 IN IP4 127.0.0.1\r\n"
        . "s=-\r\n"
        . "t=0 0\r\n"
        . "m=audio 9 UDP/TLS/RTP/SAVPF 111\r\n"
        . "a=mid:0\r\n";

    public function test_it_can_run_the_basic_video_call_signaling_flow(): void
    {
        $startResponse = $this->postJson('/api/video-calls/start', [
            'caller_type' => 'user',
            'caller_id' => 12,
            'callee_type' => 'bus',
            'callee_id' => 4,
        ]);

        $startResponse
            ->assertCreated()
            ->assertJsonPath('data.video_call.status', VideoCall::STATUS_RINGING)
            ->assertJsonPath('data.video_call.caller_type', 'user')
            ->assertJsonPath('data.video_call.callee_type', 'bus');

        $callId = $startResponse->json('data.video_call.id');

        $this->postJson("/api/video-calls/{$callId}/accept")
            ->assertOk()
            ->assertJsonPath('data.video_call.status', VideoCall::STATUS_ACCEPTED);

        $this->postJson("/api/video-calls/{$callId}/offer", [
            'offer' => [
                'type' => 'offer',
                'sdp' => self::TEST_OFFER_SDP,
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.video_call.has_offer', true);

        $this->postJson("/api/video-calls/{$callId}/answer", [
            'answer' => [
                'type' => 'answer',
                'sdp' => self::TEST_ANSWER_SDP,
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.video_call.status', VideoCall::STATUS_CONNECTED)
            ->assertJsonPath('data.video_call.has_answer', true);

        $this->postJson("/api/video-calls/{$callId}/ice", [
            'sender_type' => 'bus',
            'sender_id' => 4,
            'candidate' => [
                'candidate' => 'candidate:1 1 udp 2122260223 192.168.1.10 54321 typ host',
                'sdpMid' => '0',
                'sdpMLineIndex' => 0,
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.ice_candidate.sender_type', 'bus')
            ->assertJsonPath('data.ice_candidate.sender_id', 4);

        $this->getJson("/api/video-calls/{$callId}/ice")
            ->assertOk()
            ->assertJsonCount(0, 'data.ice_candidates');

        $this->postJson("/api/video-calls/{$callId}/end")
            ->assertOk()
            ->assertJsonPath('data.video_call.status', VideoCall::STATUS_ENDED);
    }

    public function test_it_returns_ephemeral_ice_candidates_without_persisting_them(): void
    {
        $videoCall = VideoCall::create([
            'caller_type' => 'user',
            'caller_id' => 20,
            'callee_type' => 'bus',
            'callee_id' => 8,
            'status' => VideoCall::STATUS_ACCEPTED,
            'started_at' => now(),
        ]);

        $payload = [
            'sender_type' => 'bus',
            'sender_id' => 8,
            'candidate' => [
                'candidate' => 'candidate:2 1 udp 2122260223 10.0.0.2 60000 typ host',
                'sdpMid' => '0',
                'sdpMLineIndex' => 0,
            ],
        ];

        $this->postJson("/api/video-calls/{$videoCall->id}/ice", $payload)
            ->assertOk()
            ->assertJsonPath('data.ice_candidate.id', null);

        $this->getJson("/api/video-calls/{$videoCall->id}/ice")
            ->assertOk()
            ->assertJsonCount(0, 'data.ice_candidates');
    }
}
