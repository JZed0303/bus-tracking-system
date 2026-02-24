<?php

namespace App\Http\Resources\Api\V1\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveBusMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $busId = isset($this['bus_id']) ? (int) $this['bus_id'] : null;
        $tripId = isset($this['trip_id']) ? (int) $this['trip_id'] : null;

        $plateNumber = $this['plate_number'] ?? null;
        $busLabel = $this['bus'] ?? $plateNumber ?? ($busId ? "Bus #{$busId}" : 'N/A');

        return [
            'trip_id'        => $tripId,
            'status'         => (string) ($this['status'] ?? 'unknown'),
            'lat'            => isset($this['lat']) ? (float) $this['lat'] : null,
            'lng'            => isset($this['lng']) ? (float) $this['lng'] : null,
            'speed'          => isset($this['speed']) ? (float) $this['speed'] : 0.0,
            'bus_id'         => $busId,
            'plate_number'   => $plateNumber,
            'bus'            => $busLabel, // backward compatibility for existing frontend consumers
            'driver'         => (string) ($this['driver'] ?? 'N/A'),
            'company'        => (string) ($this['company'] ?? 'N/A'),
            'route'          => (string) ($this['route'] ?? 'N/A'),
            'employee_count' => isset($this['employee_count']) ? (int) $this['employee_count'] : 0,
            'updated_at'     => $this['updated_at'] ?? null,
        ];
    }
}

