<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusIncidentReported implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

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
        return 'bus.incident.reported';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

