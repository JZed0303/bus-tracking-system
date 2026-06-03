<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BroadcastVideoCallEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<int, string>  $channels
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected array $channels,
        protected array $payload
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return collect($this->channels)
            ->filter()
            ->unique()
            ->map(fn (string $channel) => new PrivateChannel($channel))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
