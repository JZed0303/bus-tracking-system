<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'scan_type'  => $this->scan_type,
            'scan_time'  => $this->scan_time?->toDateTimeString(),

            'location' => [
                'lat' => $this->scan_lat,
                'lng' => $this->scan_lng,
            ],

            'trip_id'     => $this->trip_id,
            'employee_id' => $this->employee_id,
        ];
    }
}
