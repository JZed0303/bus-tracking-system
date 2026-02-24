<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $title;
    public $body;
    public $createdAt;
    public $link; // optional

    public function __construct(int $userId, string $title, string $body, ?string $link = null)
    {
        $this->userId    = $userId;
        $this->title     = $title;
        $this->body      = $body;
        $this->createdAt = now()->toDateTimeString();
        $this->link      = $link;
    }

    public function broadcastOn()
    {
        // private channel: users.{id}
        return new PrivateChannel('users.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'NotificationCreated';
    }
}
