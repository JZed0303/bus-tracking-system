<?php

namespace App\Events;

class IncomingCall extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.incoming';
    }
}
