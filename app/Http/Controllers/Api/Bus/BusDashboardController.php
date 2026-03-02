<?php

namespace App\Http\Controllers\Api\Bus;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Bus\BusContextResource;
use Illuminate\Http\Request;

class BusDashboardController extends Controller
{
    public function show(Request $request)
    {
        $bus = $request->user();
        $today = now('Asia/Manila')->toDateString();

        $bus->load([
            'activeAssignment.driver.user',
            'activeAssignment.company',
            'activeAssignment.route.stops',
            'activeTrip',
        ]);

        if (!$bus->activeAssignment) {
            $expiredAssignment = $bus->assignments()
                ->where('status', 'active')
                ->whereNotNull('effective_to')
                ->whereDate('effective_to', '<', $today)
                ->latest('effective_to')
                ->first();

            if ($expiredAssignment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Assignment already expired',
                    'data'    => [
                        'assignment_id' => $expiredAssignment->id,
                        'effective_to'  => optional($expiredAssignment->effective_to)->toDateString(),
                    ],
                ], 422);
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => new BusContextResource($bus),
        ]);
    }
}
