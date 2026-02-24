<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'date'      => $this->trip_date?->toDateString(),
            'direction' => $this->direction,
            'status'    => $this->status,

            'scheduled' => [
                'start' => $this->scheduled_start_time?->toDateTimeString(),
                'end'   => $this->scheduled_end_time?->toDateTimeString(),
            ],

            'actual' => [
                'start' => $this->actual_start_time?->toDateTimeString(),
                'end'   => $this->actual_end_time?->toDateTimeString(),
            ],

            'assignment' => new AssignmentResource($this->whenLoaded('assignment')),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
