<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $threadId,
        public array $payload
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.thread.{$this->threadId}")];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
