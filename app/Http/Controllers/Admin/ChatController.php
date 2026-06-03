<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChatMessageSent;
use App\Events\ChatMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // If user is company_admin AND not system admin, show company page
        if ($user->isCompanyAdmin() && ! $user->isSystemAdmin()) {
            return view('company.chat.index');
        }

        // Otherwise, show admin page
        return view('admin.chat.index');
    }

    public function threads(Request $request)
    {
        $user = $request->user();

        $query = ChatThread::query();

        // If the user is a company admin
        if ($user->isCompanyAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('context_type', 'bus_support')
                       ->whereNotNull('company_id')
                       ->where('company_id', $user->company_id);
                })
                ->orWhereExists(function ($sub) use ($user) {
                    $sub->select(DB::raw(1))
                        ->from('chat_participants')
                        ->whereColumn('chat_participants.thread_id', 'chat_threads.id')
                        ->where('participant_type', User::class)
                        ->where('participant_id', $user->id);
                });
            });
        } else {
            // Normal users only see threads they participate in
            $query->whereExists(function ($sub) use ($user) {
                $sub->select(DB::raw(1))
                    ->from('chat_participants')
                    ->whereColumn('chat_participants.thread_id', 'chat_threads.id')
                    ->where('participant_type', User::class)
                    ->where('participant_id', $user->id);
            });
        }

        // Load last message using subquery (fast + no N+1)
        $threads = $query
            ->withCount([
                'participants as participants_count' => function ($q) {
                    $q->where('participant_type', User::class);
                }
            ])
            ->addSelect([
                'last_message_body' => ChatMessage::select('body')
                    ->whereColumn('thread_id', 'chat_threads.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_sender_id' => ChatMessage::select('sender_id')
                    ->whereColumn('thread_id', 'chat_threads.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_created_at' => ChatMessage::select('created_at')
                    ->whereColumn('thread_id', 'chat_threads.id')
                    ->orderByDesc('id')
                    ->limit(1),
                // 🔴 NEW: per-user unread_count
                'unread_count' => ChatParticipant::select('unread_count')
                    ->whereColumn('thread_id', 'chat_threads.id')
                    ->where('participant_type', User::class)
                    ->where('participant_id', $user->id)
                    ->limit(1),
            ])
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return response()->json(['data' => $threads]);
    }

    public function show(ChatThread $thread, Request $request)
    {
        $this->guardAccess($request->user(), $thread);

        $user = $request->user();

        // Company admin (not system admin) -> company view
        if ($user->isCompanyAdmin() && ! $user->isSystemAdmin()) {
            return view('company.chat.thread', ['threadId' => $thread->id]);
        }

        return view('admin.chat.thread', ['threadId' => $thread->id]);
    }

    public function meta(ChatThread $thread, Request $request)
    {
        $this->guardAccess($request->user(), $thread);

        $participants = ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->get()
            ->map(function ($p) {
                $u = User::find($p->participant_id);
                return [
                    'user_id'   => $p->participant_id,
                    'role'      => $p->role,
                    'full_name' => $u?->full_name ?? "User #{$p->participant_id}",
                    'email'     => $u?->email,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'id'           => $thread->id,
                'type'         => $thread->type,
                'title'        => $thread->title,
                'company_id'   => $thread->company_id,
                'context_type' => $thread->context_type,
                'context_id'   => $thread->context_id,
                'participants' => $participants,
            ],
        ]);
    }

    public function messages(ChatThread $thread, Request $request)
    {
        $this->guardAccess($request->user(), $thread);

        $msgs = ChatMessage::query()
            ->where('thread_id', $thread->id)
            ->latest('id')
            ->paginate(50);

        // return ascending for UI
        $msgs->setCollection($msgs->getCollection()->reverse()->values());

        $data = $msgs->getCollection()->map(fn (ChatMessage $m) => $this->formatMessage($m));

        return response()->json([
            'data' => $data,
            'meta' => [
                'thread_id' => $thread->id,
            ],
        ]);
    }

    public function send(ChatThread $thread, Request $request)
    {
        $this->guardAccess($request->user(), $thread);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        // ensure sender is participant (required for private channel auth)
        $senderParticipant = ChatParticipant::query()->firstOrCreate([
            'thread_id'        => $thread->id,
            'participant_type' => User::class,
            'participant_id'   => $user->id,
        ], [
            'role' => 'member',
        ]);

        $msg = ChatMessage::create([
            'thread_id'   => $thread->id,
            'sender_type' => User::class,
            'sender_id'   => $user->id,
            'body'        => $validated['body'],
        ]);

        // 🔵 READ/UNREAD LOGIC

        // Sender: consider own message as read in this thread
        ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', $user->id)
            ->update([
                'last_read_message_id' => $msg->id,
                'last_read_at'         => now(),
                'unread_count'         => 0,
            ]);

        // Other participants: increment unread_count
        ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', '!=', $user->id)
            ->update([
                'unread_count' => DB::raw('unread_count + 1'),
            ]);

        $thread->touch();

        // Common payload used for both thread + user events
        $payload = $this->formatMessage($msg) + [
            'thread_id' => $thread->id,
        ];

        // 🔊 Thread-level event (UI in chat page)
        event(new ChatMessageSent($thread->id, $payload));

        // 🔔 Per-user notification event (global sound on any page)
        $recipientIds = ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', '!=', $user->id) // exclude sender
            ->pluck('participant_id');

        foreach ($recipientIds as $recipientId) {
            event(new ChatMessageReceived(
                userId: (int) $recipientId,
                payload: $payload
            ));
        }

        return response()->json(['data' => $payload], 201);
    }

    private function guardAccess(User $user, ChatThread $thread): void
    {
        // super_admin/admin can open any thread
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return;
        }

        $isParticipant = DB::table('chat_participants')
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', $user->id)
            ->exists();

        // ✅ Auto-heal company_id for bus_support threads if missing
        // This prevents "thread has no company_id" for legacy/bug-created threads.
        $this->ensureThreadCompanyId($thread);

        // company_admin can open company bus_support thread, or any thread where they are a participant.
        if ($user->isCompanyAdmin()) {
            abort_unless(!is_null($user->company_id), 403, 'company_admin has no company_id');

            if ($thread->context_type === 'bus_support' && !is_null($thread->company_id)) {
                abort_unless((int) $thread->company_id === (int) $user->company_id, 403, 'company mismatch');
                return;
            }

            abort_unless($isParticipant, 403, 'not a participant');
            return;
        }

        // otherwise: must be participant
        abort_unless($isParticipant, 403, 'not a participant');
    }

    private function formatMessage(ChatMessage $m): array
    {
        $label = $m->sender_type === User::class
            ? optional(User::find($m->sender_id))->full_name
            : "Bus #{$m->sender_id}";

        return [
            'id'         => $m->id,
            'body'       => $m->body,
            'sender'     => [
                'type'  => $m->sender_type,
                'id'    => $m->sender_id,
                'label' => $label,
            ],
            'created_at' => $m->created_at?->toDateTimeString(),
        ];
    }

    private function resolveCompanyIdForBusSupportThread(ChatThread $thread): ?int
    {
        if ($thread->context_type !== 'bus_support' || empty($thread->context_id)) {
            return null;
        }

        // Prefer active assignment, fallback to latest assignment
        $assignment = Assignment::active()
            ->where('bus_id', $thread->context_id)
            ->orderByDesc('effective_from')
            ->first();

        if (! $assignment) {
            $assignment = Assignment::where('bus_id', $thread->context_id)
                ->orderByDesc('effective_from')
                ->first();
        }

        return $assignment?->company_id ? (int) $assignment->company_id : null;
    }

    private function ensureThreadCompanyId(ChatThread $thread): void
    {
        if (! is_null($thread->company_id)) {
            return;
        }

        // Only auto-fill for bus_support threads
        $companyId = $this->resolveCompanyIdForBusSupportThread($thread);
        if (! $companyId) {
            return;
        }

        $thread->company_id = $companyId;
        $thread->save();
    }

    // OPTIONAL: endpoint to mark a thread as read from UI
    public function markAsRead(ChatThread $thread, Request $request)
    {
        $user = $request->user();
        $this->guardAccess($user, $thread);

        $lastMessage = ChatMessage::where('thread_id', $thread->id)
            ->orderByDesc('id')
            ->first();

        ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', $user->id)
            ->update([
                'last_read_message_id' => $lastMessage?->id,
                'last_read_at'         => now(),
                'unread_count'         => 0,
            ]);

        return response()->json(['status' => 'ok']);
    }
}
