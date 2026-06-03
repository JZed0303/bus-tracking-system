<?php

namespace App\Events;

class IceCandidateSent extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.ice-candidate.sent';
    }
}
