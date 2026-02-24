<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ChatMessageReceived implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $userId,
        public array $payload
    ) {}

    public function broadcastOn(): array
    {
        // private channel: users.{userId}
        return [
            new PrivateChannel("users.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        // This must match your JS listener: '.chat.message.received'
        return 'chat.message.received';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
