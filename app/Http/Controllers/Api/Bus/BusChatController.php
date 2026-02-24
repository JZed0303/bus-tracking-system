<?php

namespace App\Http\Controllers\Api\Bus;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusChatController extends Controller
{
    public function thread(Request $request)
    {
        $bus = $request->user(); // App\Models\Bus

        $thread = $this->ensureBusSupportThread($bus);

        return response()->json([
            'data' => [
                'thread_id' => $thread->id,
                'bus_id' => $bus->id,
                'company_id' => $thread->company_id,
                'title' => $thread->title,
            ]
        ]);
    }

    public function messages(Request $request)
    {
        $bus = $request->user();
        $thread = $this->ensureBusSupportThread($bus);

        $msgs = ChatMessage::query()
            ->where('thread_id', $thread->id)
            ->latest('id')
            ->paginate(50);

        // return ascending
        $msgs->setCollection($msgs->getCollection()->reverse()->values());

        // normalize sender for mobile UI
        $data = $msgs->getCollection()->map(fn ($m) => $this->formatMessage($m));

        return response()->json([
            'data' => $data,
            'meta' => [
                'thread_id' => $thread->id,
            ]
        ]);
    }

    public function send(Request $request)
    {
        $bus = $request->user();
        $thread = $this->ensureBusSupportThread($bus);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $msg = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_type' => get_class($bus),
            'sender_id' => $bus->id,
            'body' => $validated['body'],
        ]);

        $thread->touch();

        $payload = $this->formatMessage($msg) + [
            'thread_id' => $thread->id,
        ];

        event(new ChatMessageSent($thread->id, $payload));

        return response()->json(['data' => $payload], 201);
    }

    private function ensureBusSupportThread($bus): ChatThread
    {
        return DB::transaction(function () use ($bus) {

            $companyId = $bus->activeAssignment?->company_id;

            $thread = ChatThread::query()
                ->where('context_type', 'bus_support')
                ->where('context_id', $bus->id)
                ->first();

            if ($thread) {
                if ($companyId && $thread->company_id !== $companyId) {
                    $thread->company_id = $companyId;
                    $thread->save();
                }

                // ensure bus is participant
                $this->ensureParticipant($thread->id, get_class($bus), $bus->id, 'member');

                return $thread;
            }

            // creator is bus device
            $thread = ChatThread::create([
                'company_id' => $companyId,
                'type' => 'group',
                'title' => "Bus #{$bus->id} Support",
                'created_by_type' => get_class($bus),
                'created_by_id' => $bus->id,
                'context_type' => 'bus_support',
                'context_id' => $bus->id,
            ]);

            // Add bus participant
            $this->ensureParticipant($thread->id, get_class($bus), $bus->id, 'member');

            // Add company admins
            if ($companyId) {
                $adminIds = User::query()
                    ->where('company_id', $companyId)
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['company_admin']))
                    ->pluck('id')
                    ->all();

                foreach ($adminIds as $uid) {
                    $this->ensureParticipant($thread->id, User::class, $uid, 'admin');
                }
            }

            // Add super admins (optional)
            $superAdminIds = User::role('super_admin')->pluck('id')->all();
            foreach ($superAdminIds as $uid) {
                $this->ensureParticipant($thread->id, User::class, $uid, 'admin');
            }

            return $thread;
        });
    }

    private function ensureParticipant(int $threadId, string $type, int $id, string $role): void
    {
        ChatParticipant::query()->firstOrCreate([
            'thread_id' => $threadId,
            'participant_type' => $type,
            'participant_id' => $id,
        ], [
            'role' => $role,
        ]);
    }

    private function formatMessage(ChatMessage $m): array
    {
        $senderLabel = $m->sender_type === User::class
            ? optional(User::find($m->sender_id))->full_name
            : "Bus #{$m->sender_id}";

        return [
            'id' => $m->id,
            'body' => $m->body,
            'sender' => [
                'type' => $m->sender_type,
                'id' => $m->sender_id,
                'label' => $senderLabel,
            ],
            'created_at' => $m->created_at?->toDateTimeString(),
        ];
    }
}
