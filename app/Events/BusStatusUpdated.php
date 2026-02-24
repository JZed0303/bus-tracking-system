<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BusStatusUpdated implements ShouldBroadcast
{
    public function __construct(public array $payload) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.buses')];

        if (!empty($this->payload['company_id'])) {
            $channels[] = new PrivateChannel('company.' . $this->payload['company_id']);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'bus.status.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
