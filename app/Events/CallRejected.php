<?php

namespace App\Events;

class CallRejected extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.rejected';
    }
}
