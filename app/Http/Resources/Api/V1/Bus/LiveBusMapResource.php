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
            'driver_user_id' => isset($this['driver_user_id']) ? (int) $this['driver_user_id'] : null,
            'company'        => (string) ($this['company'] ?? 'N/A'),
            'route'          => (string) ($this['route'] ?? 'N/A'),
            'route_start'    => isset($this['route_start']) && is_array($this['route_start']) ? [
                'label' => (string) ($this['route_start']['label'] ?? 'Start'),
                'lat' => isset($this['route_start']['lat']) ? (float) $this['route_start']['lat'] : null,
                'lng' => isset($this['route_start']['lng']) ? (float) $this['route_start']['lng'] : null,
            ] : null,
            'route_end'      => isset($this['route_end']) && is_array($this['route_end']) ? [
                'label' => (string) ($this['route_end']['label'] ?? 'End'),
                'lat' => isset($this['route_end']['lat']) ? (float) $this['route_end']['lat'] : null,
                'lng' => isset($this['route_end']['lng']) ? (float) $this['route_end']['lng'] : null,
            ] : null,
            'route_stops'    => collect($this['route_stops'] ?? [])
                ->map(function ($stop) {
                    return [
                        'id' => isset($stop['id']) ? (int) $stop['id'] : null,
                        'address' => (string) ($stop['address'] ?? 'Stop'),
                        'lat' => isset($stop['lat']) ? (float) $stop['lat'] : null,
                        'lng' => isset($stop['lng']) ? (float) $stop['lng'] : null,
                        'stop_order' => isset($stop['stop_order']) ? (int) $stop['stop_order'] : 0,
                    ];
                })
                ->values(),
            'employee_count' => isset($this['employee_count']) ? (int) $this['employee_count'] : 0,
            'onboard_count' => isset($this['onboard_count']) ? (int) $this['onboard_count'] : 0,
            'bus_capacity' => isset($this['bus_capacity']) ? (int) $this['bus_capacity'] : 0,
            'available_capacity' => isset($this['available_capacity']) ? (int) $this['available_capacity'] : 0,
            'updated_at'     => $this['updated_at'] ?? null,
        ];
    }
}
