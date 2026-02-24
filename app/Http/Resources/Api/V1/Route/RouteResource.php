<?php

namespace App\Http\Resources\Api\V1\Route;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusContextResource extends JsonResource
{
    public function toArray($request)
    {
        $trip = $this->trips->first();

        return [
            'company' => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
            ],

            'bus' => [
                'id'           => $this->bus->id,
                'plate_number' => $this->bus->plate_number,
                'capacity'     => $this->bus->capacity,
            ],

            'driver' => new DriverResource($this->driver),

            'route' => new RouteResource($this->route),

            'active_trip' => $trip
                ? new TripResource($trip)
                : null,
        ];
    }
}
