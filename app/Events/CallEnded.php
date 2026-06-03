<?php

namespace App\Events;

class CallEnded extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.ended';
    }
}
