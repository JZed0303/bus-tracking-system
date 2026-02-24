<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BusSummaryController extends Controller
{
    public function index(Request $request)
    {
        $bus  = $request->user();
        $trip = $bus->activeTrip; // ✅ FIX HERE

        if (!$trip) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No active trip.',
                'data'    => [
                    'checked_in'  => 0,
                    'checked_out' => 0,
                ],
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'trip_id'      => $trip->id,
                'checked_in'   => $trip->checkins()
                                      ->where('scan_type', 'checkin')
                                      ->count(),
                'checked_out'  => $trip->checkins()
                                      ->where('scan_type', 'checkout')
                                      ->count(),
            ],
        ]);
    }
}
