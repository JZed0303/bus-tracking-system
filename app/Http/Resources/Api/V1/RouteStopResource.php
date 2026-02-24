<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'sequence' => $this->sequence,

            'location' => [
                'lat' => $this->latitude,
                'lng' => $this->longitude,
            ],
        ];
    }
}
