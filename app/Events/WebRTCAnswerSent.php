<?php

namespace App\Events;

class WebRTCAnswerSent extends BroadcastVideoCallEvent
{
    public function broadcastAs(): string
    {
        return 'video-call.answer.sent';
    }
}
