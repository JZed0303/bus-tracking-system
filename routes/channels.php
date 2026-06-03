<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Bus;
use App\Models\ChatThread;

/*
|--------------------------------------------------------------------------
| Per-user private channel (global notifications / chat sounds)
|--------------------------------------------------------------------------
| Client uses:
|   Echo.private('users.' + authUser.id)
| For example:
|   window.Echo.private(`users.${userId}`).listen('.chat.message.received', ...)
*/
Broadcast::channel('users.{userId}', function (User $user, $userId) {
    // Only the owner can listen to his private notifications channel
    if ((int) $user->id !== (int) $userId) {
        return false;
    }

    return [
        'presence_key' => 'user:' . $user->id,
        'id'           => (int) $user->id,
        'type'         => 'user',
        'name'         => $user->full_name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
        'avatar_url'   => $user->avatar_url ?? null,
    ];
});

/*
|--------------------------------------------------------------------------
| Chat thread presence/private channel
|--------------------------------------------------------------------------
| Client uses: Echo.join('chat.thread.{threadId}') for presence
| Server channel name is WITHOUT the "presence-" prefix:
|   chat.thread.{threadId}
*/
Broadcast::channel('chat.thread.{threadId}', function ($actor, $threadId) {
    $thread = ChatThread::find($threadId);
    if (!$thread) {
        return false;
    }

    // ---------------------------
    // USER authorization
    // ---------------------------
    if ($actor instanceof User) {
        $allowed =
            (method_exists($actor, 'isSuperAdmin') && $actor->isSuperAdmin()) ||
            (method_exists($actor, 'hasRole') && $actor->hasRole('admin')) ||
            (method_exists($actor, 'isCompanyAdmin')
                && $actor->isCompanyAdmin()
                && $thread->context_type === 'bus_support'
                && (int) $thread->company_id === (int) $actor->company_id) ||
            DB::table('chat_participants')
                ->where('thread_id', (int) $threadId)
                ->where('participant_type', User::class)
                ->where('participant_id', (int) $actor->id)
                ->exists();

        if (!$allowed) {
            return false;
        }

        // Presence payload
        return [
            'presence_key' => 'user:' . $actor->id,
            'id'           => (int) $actor->id,
            'type'         => 'user',
            'name'         => $actor->full_name
                ?? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
            'avatar_url'   => $actor->avatar_url ?? null,
        ];
    }

    // ---------------------------
    // BUS authorization
    // ---------------------------
    if ($actor instanceof Bus) {
        $allowed = false;

        if (
            isset($thread->context_type, $thread->context_id)
            && $thread->context_type === 'bus_support'
            && (int) $thread->context_id === (int) $actor->id
        ) {
            $allowed = true;
        } else {
            $allowed = DB::table('chat_participants')
                ->where('thread_id', (int) $threadId)
                ->where('participant_type', Bus::class)
                ->where('participant_id', (int) $actor->id)
                ->exists();
        }

        if (!$allowed) {
            return false;
        }

        // Presence payload
        return [
            'presence_key' => 'bus:' . $actor->id,
            'id'           => (int) $actor->id,
            'type'         => 'bus',
            'name'         => $actor->plate_no
                ?? $actor->bus_number
                ?? ('Bus #' . $actor->id),
            'avatar_url'   => $actor->avatar_url ?? null,
        ];
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Global presence channel (online anywhere in the app)
|--------------------------------------------------------------------------
| Client uses: Echo.join('presence.app')
*/
Broadcast::channel('presence.app', function ($actor) {
    if (!($actor instanceof User)) {
        return false;
    }

    return [
        'presence_key' => 'user:' . $actor->id,
        'id'           => (int) $actor->id,
        'type'         => 'user',
        'name'         => $actor->full_name
            ?? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
        'avatar_url'   => $actor->avatar_url ?? null,
    ];
});

/*
|--------------------------------------------------------------------------
| Admin buses channel (users only)
|--------------------------------------------------------------------------
*/
Broadcast::channel('admin.buses', function ($actor) {
    if (!($actor instanceof User)) {
        return false;
    }

    if (method_exists($actor, 'isSuperAdmin') && $actor->isSuperAdmin()) {
        return true;
    }
    if (method_exists($actor, 'hasRole') && $actor->hasRole('admin')) {
        return true;
    }
    if (method_exists($actor, 'isCompanyAdmin') && $actor->isCompanyAdmin()) {
        return true;
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Company channel (users only)
|--------------------------------------------------------------------------
*/
Broadcast::channel('company.{companyId}', function ($actor, $companyId) {
    if (!($actor instanceof User)) {
        return false;
    }

    if (method_exists($actor, 'isSuperAdmin') && $actor->isSuperAdmin()) {
        return true;
    }
    if (method_exists($actor, 'hasRole') && $actor->hasRole('admin')) {
        return true;
    }

    if (method_exists($actor, 'isCompanyAdmin') && $actor->isCompanyAdmin()) {
        return (int) $actor->company_id === (int) $companyId;
    }

    return false;
});

Broadcast::channel('video-calls.user.{userId}', function ($actor, $userId) {
    if (!($actor instanceof User)) {
        return false;
    }

    return (int) $actor->id === (int) $userId;
});

Broadcast::channel('video-calls.bus.{busId}', function ($actor, $busId) {
    if (!($actor instanceof Bus)) {
        return false;
    }

    return (int) $actor->id === (int) $busId;
});
