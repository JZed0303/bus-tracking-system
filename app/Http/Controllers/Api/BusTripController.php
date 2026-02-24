<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class BusTripController extends Controller
{
    public function start(Request $request)
    {
        $validated = $request->validate([
            'direction' => 'required|in:pickup,dropoff',
        ]);

        $bus = $request->user();

        // Active assignment for this bus
        $assignment = $bus->assignments()
            ->where('status', 'active')
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'No active assignment found for this bus',
                'data'    => null,
            ], 404);
        }

        // Prevent duplicate trips
        $existingTrip = Trip::where('assignment_id', $assignment->id)
            ->where('status', 'ongoing')
            ->first();

        if ($existingTrip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip already in progress',
                'data'    => [
                    'trip_id' => $existingTrip->id,
                    'started_at' => optional($existingTrip->actual_start_time)->toIso8601String(),
                ],
            ], 409);
        }

        // Create trip
        $trip = Trip::create([
            'assignment_id'     => $assignment->id,
            'trip_date'         => now()->toDateString(),
            'direction'         => $validated['direction'],
            'status'            => 'ongoing',
            'actual_start_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip started successfully',
            'data'    => [
                'trip_id'      => $trip->id,
                'direction'    => $trip->direction,
                'status'       => $trip->status,
                'started_at'   => $trip->actual_start_time->toIso8601String(),
            ],
        ], 201);
    }

    public function end(Request $request)
    {
        $bus = $request->user();

        if (!$bus) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data'    => null,
            ], 401);
        }

        // Find ongoing trip for this bus
        $trip = Trip::where('status', 'ongoing')
            ->whereHas('assignment', function ($q) use ($bus) {
                $q->where('bus_id', $bus->id);
            })
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'No active trip to end',
                'data'    => null,
            ], 404);
        }

        $trip->update([
            'status'          => 'completed',
            'actual_end_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip ended successfully',
            'data'    => [
                'trip_id'     => $trip->id,
                'status'      => $trip->status,
                'started_at'  => optional($trip->actual_start_time)->toIso8601String(),
                'ended_at'    => $trip->actual_end_time->toIso8601String(),
            ],
        ], 200);
    }
}
