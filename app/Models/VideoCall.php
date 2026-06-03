<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoCall extends Model
{
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ENDED = 'ended';
    public const STATUS_MISSED = 'missed';

    protected $fillable = [
        'caller_type',
        'caller_id',
        'callee_type',
        'callee_id',
        'status',
        'offer',
        'answer',
        'started_at',
        'accepted_at',
        'ended_at',
    ];

    protected $casts = [
        'offer' => 'array',
        'answer' => 'array',
        'started_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function iceCandidates(): HasMany
    {
        return $this->hasMany(VideoCallIceCandidate::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(VideoCallRecording::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_RINGING,
            self::STATUS_ACCEPTED,
            self::STATUS_CONNECTED,
        ]);
    }

    public function hasOffer(): bool
    {
        return $this->hasSessionDescription($this->offer, 'offer');
    }

    public function hasAnswer(): bool
    {
        return $this->hasSessionDescription($this->answer, 'answer');
    }

    public function hasSessionDescription(mixed $description, ?string $expectedType = null): bool
    {
        if (!is_array($description)) {
            return false;
        }

        $type = trim((string) ($description['type'] ?? ''));
        $sdp = trim((string) ($description['sdp'] ?? ''));

        if ($type === '' || $sdp === '') {
            return false;
        }

        if ($expectedType !== null && $type !== $expectedType) {
            return false;
        }

        return str_contains($sdp, 'v=0')
            && str_contains($sdp, 'm=')
            && str_contains($sdp, 'a=');
    }
}
