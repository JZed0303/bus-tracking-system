<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        // Ensure clean scalar payload (avoid models/collections accidentally passed)
        $this->payload = $payload;
    }

    /**
     * Broadcast to:
     * - private-admin.buses (always)
     * - private-company.{company_id} (if company_id exists)
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.buses'),
        ];

        $companyId = $this->payload['company_id'] ?? null;

        if (is_numeric($companyId) && (int) $companyId > 0) {
            $channels[] = new PrivateChannel('company.' . (int) $companyId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'bus.location.updated';
    }

    public function broadcastWith(): array
    {
        // Make sure latitude/longitude keys are consistent (optional but recommended)
        // If you already send latitude/longitude from controller, keep as-is.
        return $this->payload;
    }
}
