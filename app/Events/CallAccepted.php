<?php

namespace App\Events;

class CallAccepted extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.accepted';
    }
}
