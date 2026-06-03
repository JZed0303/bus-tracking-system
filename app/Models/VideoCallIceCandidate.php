<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallIceCandidate extends Model
{
    protected $fillable = [
        'video_call_id',
        'sender_type',
        'sender_id',
        'candidate',
        'sdp_mid',
        'sdp_mline_index',
    ];

    public function videoCall(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class);
    }
}
