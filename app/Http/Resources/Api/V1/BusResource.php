<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Bus $bus */
        $bus = $this->resource;

        // Assume these are eager loaded to avoid N+1.
        $trip             = $bus->activeTrip;
        $activeAssignment = $bus->activeAssignment;
        $assignedRoute    = $activeAssignment?->route;
        $tripRoute        = $trip?->route;
        $driver           = $activeAssignment?->driver;
        $driverUser       = $driver?->user;

        // Prefer dispatcher assignment route, else trip route.
        $route = $assignedRoute ?: $tripRoute;

        // IMPORTANT: this should NOT hit the DB per bus.
        // Make sure currentLocation() uses an already-loaded relation,
        // or replace it with $bus->latestLocation or similar.
        $location = $bus->currentLocation();

        return [
            'id'           => $bus->id,
            'plate_number' => $bus->plate_number,
            'capacity'     => $bus->capacity,
            'brand_model'  => $bus->brand_model,
            'status'       => $bus->status,
            'photo_url'    => $bus->photo_url,

            'active_trip' => $trip ? [
                'id'         => $trip->id,
                'direction'  => $trip->direction,
                'started_at' => $trip->actual_start_time?->toIso8601String(),
                'status'     => $trip->status,
            ] : null,

            'current_location' => $location ? [
                'latitude'   => $location->latitude,
                'longitude'  => $location->longitude,
                'tracked_at' => $location->tracked_at?->toIso8601String(),
            ] : null,

            'assigned_route' => $route ? [
                'id'          => $route->id,
                'name'        => $route->name,
                'origin'      => $route->origin,
                'destination' => $route->destination,
                'description' => $route->description,
            ] : null,

            'driver' => $driver ? [
                'id' => $driver->id,
                'user_id' => $driver->user_id,
                'name' => $driverUser?->full_name,
                'first_name' => $driverUser?->first_name,
                'last_name' => $driverUser?->last_name,
                'email' => $driverUser?->email,
                'phone' => $driver->phone,
                'license_number' => $driver->license_number,
                'status' => $driver->status,
                'photo_url' => $driver->photo_url,
            ] : null,
        ];
    }
}
