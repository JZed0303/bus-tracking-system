<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,

            'effective' => [
                'from' => $this->effective_from?->toDateString(),
                'to'   => $this->effective_to?->toDateString(),
            ],

            'bus'     => new BusResource($this->whenLoaded('bus')),
            'route'   => new RouteResource($this->whenLoaded('route')),
            'company' => [
                'id'   => $this->company?->id,
                'name' => $this->company?->name,
            ],
        ];
    }
}
