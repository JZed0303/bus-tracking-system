<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatParticipant extends Model
{
    protected $fillable = [
        'thread_id',
        'participant_type',
        'participant_id',
        'role',
        'last_read_message_id',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function participant(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'participant_type', 'participant_id');
    }
}
