<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\ChatParticipant;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupChatController extends Controller
{
    public function index()
    {
        return view('admin.group-chats.index');
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->with('roles')
            ->orderBy('last_name')
            ->limit(50)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'company_id' => $u->company_id,
                'roles' => $u->roles->pluck('name')->values(),
            ])
            ->values();

        return response()->json(['data' => $users]);
    }

    /**
     * ✅ Now returns BOTH:
     * - group chats: type=group AND context_type IS NULL
     * - bus support chats: context_type=bus_support
     */
    public function threads(Request $request)
    {
        $user = $request->user();

        $q = ChatThread::query()
            ->leftJoin('buses as b', 'b.id', '=', 'chat_threads.context_id')
            ->select([
                'chat_threads.*',

                // bus fields for bus_support threads (null for group threads)
                'b.bus_code as bus_code',
                'b.plate_number as plate_number',
                'b.brand_model as brand_model',
                'b.capacity as capacity',
                'b.status as bus_status',
            ])
            ->withCount('participants')
            ->where(function ($w) {
                $w->where(function ($g) {
                    $g->where('chat_threads.type', 'group')
                      ->whereNull('chat_threads.context_type');
                })->orWhere(function ($b) {
                    $b->where('chat_threads.context_type', 'bus_support');
                });
            })
            ->orderByDesc('chat_threads.updated_at');

        // Company admin restriction (applies to bus_support threads; group chats usually company_id null)
        if ($user->isCompanyAdmin()) {
            $q->where('chat_threads.company_id', $user->company_id);
        }

        // super_admin/admin can access all; others must be participant
        if (!($user->isSuperAdmin() || $user->hasRole('admin'))) {
            $q->whereExists(function ($sub) use ($user) {
                $sub->select(DB::raw(1))
                    ->from('chat_participants')
                    ->whereColumn('chat_participants.thread_id', 'chat_threads.id')
                    ->where('chat_participants.participant_type', User::class)
                    ->where('chat_participants.participant_id', $user->id);
            });
        }

        $threads = $q->get()->map(function ($t) {
            $kind = $t->context_type === 'bus_support' ? 'bus_support' : 'group';

            return [
                'id' => $t->id,
                'kind' => $kind,

                'title' => $t->title,

                'company_id' => $t->company_id,
                'participants_count' => (int) $t->participants_count,
                'updated_at' => $t->updated_at?->toDateTimeString(),

                // Bus info (only when kind=bus_support)
                'bus_id' => $kind === 'bus_support' ? (int) $t->context_id : null,
                'bus_code' => $t->bus_code ?? null,
                'plate_number' => $t->plate_number ?? null,
                'brand_model' => $t->brand_model ?? null,
                'capacity' => $t->capacity ?? null,
                'bus_status' => $t->bus_status ?? null,
            ];
        })->values();

        return response()->json(['data' => $threads]);
    }

    /**
     * Group-only create (super-admin group chats)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $creator = $request->user();

        $thread = DB::transaction(function () use ($validated, $creator) {
            $thread = ChatThread::create([
                'company_id' => null,
                'type' => 'group',
                'title' => $validated['title'],
                'created_by_type' => User::class,
                'created_by_id' => $creator->id,
                'context_type' => null,
                'context_id' => null,
            ]);

            ChatParticipant::create([
                'thread_id' => $thread->id,
                'participant_type' => User::class,
                'participant_id' => $creator->id,
                'role' => 'owner',
            ]);

            foreach (array_unique($validated['user_ids']) as $uid) {
                ChatParticipant::firstOrCreate([
                    'thread_id' => $thread->id,
                    'participant_type' => User::class,
                    'participant_id' => $uid,
                ], [
                    'role' => 'member',
                ]);
            }

            return $thread;
        });

        return response()->json(['data' => [
            'id' => $thread->id,
            'title' => $thread->title,
        ]], 201);
    }

    /**
     * ✅ Show details for BOTH group and bus_support threads
     */
    public function show(Request $request, ChatThread $thread)
    {
        $this->guardThreadAccess($request->user(), $thread);

        $participants = ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->join('users', 'users.id', '=', 'chat_participants.participant_id')
            ->select([
                'chat_participants.id',
                'chat_participants.participant_id as user_id',
                'chat_participants.role',
                'users.first_name',
                'users.last_name',
                'users.email',
            ])
            ->orderBy('users.last_name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'user_id' => (int) $p->user_id,
                'role' => $p->role,
                'full_name' => trim($p->first_name . ' ' . $p->last_name),
                'email' => $p->email,
            ])
            ->values();

        $kind = $thread->context_type === 'bus_support' ? 'bus_support' : 'group';

        $busMeta = null;
        if ($kind === 'bus_support') {
            $bus = Bus::find($thread->context_id);
            $busMeta = $bus ? [
                'id' => $bus->id,
                'bus_code' => $bus->bus_code,
                'plate_number' => $bus->plate_number,
                'brand_model' => $bus->brand_model,
                'capacity' => $bus->capacity,
                'status' => $bus->status,
            ] : null;
        }

        return response()->json([
            'data' => [
                'id' => $thread->id,
                'kind' => $kind,
                'title' => $thread->title,
                'company_id' => $thread->company_id,
                'context_type' => $thread->context_type,
                'context_id' => $thread->context_id,
                'bus' => $busMeta,
                'participants' => $participants,
            ],
        ]);
    }

    /**
     * ✅ Add participants to BOTH group and bus_support threads
     */
    public function addParticipants(Request $request, ChatThread $thread)
    {
        $this->guardThreadAccess($request->user(), $thread);
        $this->guardParticipantManagement($request->user(), $thread);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // Determine default role per thread type
        $defaultRole = $this->isGroupThread($thread) ? 'member' : 'member';

        foreach (array_unique($validated['user_ids']) as $uid) {
            ChatParticipant::firstOrCreate([
                'thread_id' => $thread->id,
                'participant_type' => User::class,
                'participant_id' => $uid,
            ], [
                'role' => $defaultRole,
            ]);
        }

        $thread->touch();

        return response()->json(['ok' => true]);
    }

    /**
     * ✅ Remove participants from BOTH group and bus_support threads
     */
    public function removeParticipant(Request $request, ChatThread $thread, User $user)
    {
        $this->guardThreadAccess($request->user(), $thread);
        $this->guardParticipantManagement($request->user(), $thread);

        $p = ChatParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', $user->id)
            ->first();

        if (!$p) return response()->json(['ok' => true]);

        // group policy: cannot remove owner
        if ($this->isGroupThread($thread) && $p->role === 'owner') {
            return response()->json(['message' => 'Cannot remove owner.'], 422);
        }

        // bus policy: cannot remove last admin (optional but recommended)
        if ($this->isBusThread($thread) && $p->role === 'admin') {
            $adminCount = ChatParticipant::query()
                ->where('thread_id', $thread->id)
                ->where('participant_type', User::class)
                ->where('role', 'admin')
                ->count();

            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot remove the last admin from this bus chat.'], 422);
            }
        }

        $p->delete();
        $thread->touch();

        return response()->json(['ok' => true]);
    }

    /**
     * -------------------------
     * Helpers / Guards
     * -------------------------
     */

    private function isGroupThread(ChatThread $thread): bool
    {
        return $thread->type === 'group' && $thread->context_type === null;
    }

    private function isBusThread(ChatThread $thread): bool
    {
        return $thread->context_type === 'bus_support';
    }

    private function guardThreadAccess(User $user, ChatThread $thread): void
    {
        // Only allow group (context null) or bus_support
        abort_unless($this->isGroupThread($thread) || $this->isBusThread($thread), 404);

        // Company admin must match thread company (mainly for bus_support)
        if ($user->isCompanyAdmin()) {
            abort_unless((int) $thread->company_id === (int) $user->company_id, 403);
        }

        // super_admin/admin can access everything
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return;
        }

        // Otherwise must be participant
        $ok = DB::table('chat_participants')
            ->where('thread_id', $thread->id)
            ->where('participant_type', User::class)
            ->where('participant_id', $user->id)
            ->exists();

        abort_unless($ok, 403);
    }

    /**
     * Only allow privileged users to add/remove participants.
     * - group: typically owner/super/admin (here: super/admin only unless you extend)
     * - bus_support: super/admin/companyAdmin
     *
     * If you want “any participant can add”, delete this guard.
     */
    private function guardParticipantManagement(User $user, ChatThread $thread): void
    {
        // super_admin/admin always ok
        if ($user->isSuperAdmin() || $user->hasRole('admin')) return;

        // company admin can manage bus threads within company (already checked in guardThreadAccess)
        if ($this->isBusThread($thread) && $user->isCompanyAdmin()) return;

        abort(403);
    }
}
