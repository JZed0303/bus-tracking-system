<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ChatThread extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'title',
        'created_by_type',
        'created_by_id',
        'context_type',
        'context_id',
    ];

    public function creator(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'created_by_type', 'created_by_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'thread_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function latestMessage(): HasMany
    {
        return $this->messages()->latest()->limit(1);
    }

    public function scopeBusSupport($query)
    {
        return $query->where('context_type', 'bus_support');
    }

    public function scopeWhereUserIsParticipant(Builder $query, User $user): Builder
    {
        return $query->whereExists(function ($sub) use ($user) {
            $sub->selectRaw('1')
                ->from('chat_participants')
                ->whereColumn('chat_participants.thread_id', 'chat_threads.id')
                ->where('chat_participants.participant_type', User::class)
                ->where('chat_participants.participant_id', $user->id);
        });
    }
    public function bus()
    {
        return $this->belongsTo(\App\Models\Bus::class, 'context_id', 'id');
    }
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isCompanyAdmin()) {
            // Company admins can see their bus_support threads or threads they participate in.
            return $query->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('context_type', 'bus_support')
                        ->where('company_id', $user->company_id);
                })->orWhereUserIsParticipant($user);
            });
        }

        // Everyone else: only threads where they participate.
        return $query->whereUserIsParticipant($user);
    }
}
