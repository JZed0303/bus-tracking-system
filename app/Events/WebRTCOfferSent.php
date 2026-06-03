<?php

namespace App\Events;

class WebRTCOfferSent extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.offer.sent';
    }
}
