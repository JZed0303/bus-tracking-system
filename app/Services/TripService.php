<?php

namespace App\Services;


use App\Models\Trip;
use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TripService
{
    /* ================= START TRIP ================= */

    public function startTrip(Trip $trip, int $driverId): Trip
    {
        if ($trip->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'trip' => 'Trip cannot be started.',
            ]);
        }

        if ($trip->assignment->driver_id !== $driverId) {
            throw ValidationException::withMessages([
                'driver' => 'You are not assigned to this trip.',
            ]);
        }

        // Ensure driver has no other ongoing trip
        $existing = Trip::whereHas('assignment', fn ($q) =>
            $q->where('driver_id', $driverId)
        )->where('status', 'ongoing')->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'trip' => 'You already have an ongoing trip.',
            ]);
        }

        $trip->update([
            'status' => 'ongoing',
            'actual_start_time' => Carbon::now(),
        ]);

        return $trip;
    }

    /* ================= END TRIP ================= */

    public function endTrip(Trip $trip, int $driverId): Trip
    {
        if ($trip->status !== 'ongoing') {
            throw ValidationException::withMessages([
                'trip' => 'Trip is not ongoing.',
            ]);
        }

        if ($trip->assignment->driver_id !== $driverId) {
            throw ValidationException::withMessages([
                'driver' => 'Unauthorized action.',
            ]);
        }

        $trip->update([
            'status' => 'completed',
            'actual_end_time' => Carbon::now(),
        ]);

        return $trip;
    }
}
