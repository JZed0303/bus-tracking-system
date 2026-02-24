<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusChatController extends Controller
{
    public function index()
    {
        return view('admin.bus-chat.index');
    }

    public function threads(Request $request)
    {
        $user = $request->user();

        $q = ChatThread::query()
            ->where('chat_threads.context_type', 'bus_support')
            ->with(['latestMessage' => fn ($m) => $m->latest()])
            ->leftJoin('buses as b', 'b.id', '=', 'chat_threads.context_id')
            ->select([
                'chat_threads.*',

                // bus fields (based on your Bus model)
                'b.bus_code as bus_code',
                'b.plate_number as plate_number',
                'b.brand_model as brand_model',
                'b.capacity as capacity',
                'b.status as bus_status',
            ])
            ->orderByDesc('chat_threads.updated_at');

        if ($user->isCompanyAdmin()) {
            $q->where('chat_threads.company_id', $user->company_id);
        }

        // super_admin/admin can access all bus threads; others must be participant
        if (!($user->isSuperAdmin() || $user->hasRole('admin'))) {
            $q->whereHas('participants', function ($p) use ($user) {
                $p->where('participant_type', User::class)
                  ->where('participant_id', $user->id);
            });
        }

        $threads = $q->get()->map(function ($t) {
            // latestMessage is a relation with a constraint; it returns a collection
            $latest = $t->latestMessage?->first();

            return [
                'id' => $t->id,
                'company_id' => $t->company_id,
                'bus_id' => $t->context_id,

                // ✅ bus fields included for React thread list / header
                'bus_code' => $t->bus_code,
                'plate_number' => $t->plate_number,
                'brand_model' => $t->brand_model,
                'capacity' => $t->capacity,
                'bus_status' => $t->bus_status,

                'title' => $t->title,
                'latest_message' => $latest ? $this->formatMessage($latest) : null,
                'updated_at' => $t->updated_at?->toDateTimeString(),
            ];
        })->values();

        return response()->json(['data' => $threads]);
    }

    public function messages(Request $request, ChatThread $thread)
    {
        $this->guardThreadAccess($request->user(), $thread);

        $msgs = ChatMessage::query()
            ->where('thread_id', $thread->id)
            ->latest('id')
            ->paginate(50);

        $msgs->setCollection($msgs->getCollection()->reverse()->values());

        $data = $msgs->getCollection()->map(fn ($m) => $this->formatMessage($m));

        return response()->json([
            'data' => $data,
            'meta' => [
                'thread_id' => $thread->id,
                'bus_id' => $thread->context_id,
                'company_id' => $thread->company_id,
            ],
        ]);
    }

    public function sendMessage(Request $request, ChatThread $thread)
    {
        $this->guardThreadAccess($request->user(), $thread);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        // Ensure this admin is a participant (so they can subscribe to private channel)
        ChatParticipant::query()->firstOrCreate([
            'thread_id' => $thread->id,
            'participant_type' => User::class,
            'participant_id' => $user->id,
        ], [
            'role' => 'admin',
        ]);

        $msg = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_type' => User::class,
            'sender_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $thread->touch();

        $payload = $this->formatMessage($msg) + ['thread_id' => $thread->id];

        event(new ChatMessageSent($thread->id, $payload));

        return response()->json(['data' => $payload], 201);
    }

    private function guardThreadAccess(User $user, ChatThread $thread): void
    {
        abort_unless($thread->context_type === 'bus_support', 404);

        if ($user->isCompanyAdmin()) {
            abort_unless((int) $thread->company_id === (int) $user->company_id, 403);
        }

        // super_admin/admin can access all bus threads
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return;
        }

        // fallback: must be participant
        $ok = DB::table('chat_participants')
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', $user->id)
            ->exists();

        abort_unless($ok, 403);
    }

    private function formatMessage(ChatMessage $m): array
    {
        if ($m->sender_type === User::class) {
            $u = User::find($m->sender_id);
            $label = $u?->full_name ?? 'Unknown';
        } elseif ($m->sender_type === Bus::class) {
            $bus = Bus::find($m->sender_id);

            $labelParts = array_filter([
                $bus?->plate_number,
                $bus?->brand_model,
                $bus?->bus_code ? "Bus {$bus->id}" : null,
            ]);

            $label = $labelParts ? implode(' • ', $labelParts) : "Bus #{$m->sender_id}";
        } else {
            $label = "Sender #{$m->sender_id}";
        }

        return [
            'id' => $m->id,
            'body' => $m->body,
            'sender' => [
                'type' => $m->sender_type,
                'id' => $m->sender_id,
                'label' => $label,
            ],
            'created_at' => $m->created_at?->toDateTimeString(),
        ];
    }
}
