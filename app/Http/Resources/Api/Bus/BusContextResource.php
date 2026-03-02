<?php

namespace App\Http\Resources\Api\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusContextResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->activeAssignment;
        $trip       = $this->activeTrip;

        return [
            'bus' => [
                'id'           => $this->id,
                'plate_number' => $this->plate_number,
                'capacity'     => $this->capacity,
                'status'       => $this->status,
            ],

            'driver' => $assignment?->driver ? [
                'id'      => $assignment->driver->id,
                'name'    => $assignment->driver->user->full_name,
                'license' => $assignment->driver->license_number,
            ] : null,

            'company' => $assignment?->company ? [
                'id'   => $assignment->company->id,
                'name' => $assignment->company->name,
            ] : null,

            'route' => $assignment?->route ? [
                'id'   => $assignment->route->id,
                'name' => $assignment->route->name,
            ] : null,

            'stops' => $assignment?->route?->stops
                ? $assignment->route->stops->map(fn ($stop) => [
                    'id'        => $stop->id,
                    'address'   => $stop->address,
                    'latitude'  => $stop->latitude,
                    'longitude' => $stop->longitude,
                    'order'     => $stop->stop_order,
                ])->values()
                : [],

            'trip' => $trip ? [
                'id'         => $trip->id,
                'status'     => $trip->status,
                'direction'  => $trip->direction,
                'started_at' => $trip->actual_start_time,
                'transfer_from_trip_id' => $trip->transfer_from_trip_id,
            ] : null,

            'assignment' => $assignment ? [
                'id' => $assignment->id,
                'leg' => $assignment->leg ?? 'both',
            ] : null,
        ];
    }
}
