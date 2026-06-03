<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallRecording extends Model
{
    protected $fillable = [
        'video_call_id',
        'recorded_by_type',
        'recorded_by_id',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
    ];

    public function videoCall(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class);
    }
}
