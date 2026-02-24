<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusGps;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Events\BusLocationUpdated;
use Illuminate\Http\Request;

class BusGpsController extends Controller
{
    public function store(Request $request)
    {
        // Sanctum-authenticated bus (the logged-in bus user)
        $bus = $request->user();

        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed'     => 'nullable|numeric|min:0',
        ]);

        $trackedAt = now();

        /* ===============================
           1. ALWAYS STORE LATEST BUS GPS PING
           =============================== */
        BusGps::create([
            'bus_id'     => $bus->id,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'tracked_at' => $trackedAt,
        ]);

        // Keep presence fresh for web/mobile "online" status.
        $bus->forceFill(['last_seen_at' => $trackedAt])->saveQuietly();

        /* ===============================
           2. FIND ACTIVE TRIP (IF ANY)
           =============================== */
        $trip = Trip::with('assignment')
            ->whereHas('assignment', function ($q) use ($bus) {
                $q->where('bus_id', $bus->id);
            })
            ->where('status', 'ongoing') // adjust if your status value is different
            ->first();

        /* ===============================
           3. RESOLVE CURRENT COMPANY_ID
              (BASED ON ASSIGNMENT)
           =============================== */

        // 3.1 Prefer the assignment from the ongoing trip (if any)
        $activeAssignment = $trip?->assignment;

        // 3.2 If no ongoing trip assignment, fall back to this bus's "active" assignment
        if (! $activeAssignment && method_exists($bus, 'assignments')) {
            $activeAssignment = $bus->assignments()
                ->where('status', 'active') // or 'ongoing' / 'current'
                ->latest()
                ->first();
        }

        // 3.3 Get company_id from the resolved assignment
        $companyId = optional($activeAssignment)->company_id;

        // Optional fallback if you also store it on buses:
        // $companyId = $companyId ?? $bus->company_id;

        /* ===============================
           4. CREATE TRIP LOCATION (IF TRIP)
           =============================== */
        if ($trip) {
            $driverId = optional($trip->assignment)->driver_id; // safest

            TripLocation::create([
                'trip_id'    => $trip->id,
                'bus_id'     => $bus->id,
                'driver_id'  => $driverId,
                'company_id' => $companyId, // <= if TripLocation has company_id
                'latitude'   => $request->latitude,
                'longitude'  => $request->longitude,
                'speed'      => $request->speed,
                'tracked_at' => $trackedAt,
            ]);
        }

        /* ===============================
           5. BROADCAST BUS LOCATION UPDATE
           =============================== */
        if ($companyId) {
            broadcast(new BusLocationUpdated([
                'company_id' => (int) $companyId,
                'bus_id'     => (int) $bus->id,
                'trip_id'    => $trip?->id,
                'latitude'   => (float) $request->latitude,
                'longitude'  => (float) $request->longitude,
                'speed'      => $request->speed !== null ? (float) $request->speed : null,
                'status'     => $trip ? 'on_trip' : 'idle',
                'updated_at' => $trackedAt->toISOString(),
            ]))->toOthers();
        }

        /* ===============================
           6. API RESPONSE
           =============================== */
        return response()->json([
            'status'        => 'ok',
            'trip_tracking' => (bool) $trip,
            'bus_id'        => (int) $bus->id,                           // ✅ bus id
            'company_id'    => $companyId ? (int) $companyId : null,     // ✅ active company_id
        ]);
    }
}
