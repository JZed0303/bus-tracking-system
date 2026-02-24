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

        $bus->load([
            'activeAssignment.driver.user',
            'activeAssignment.company',
            'activeAssignment.route.stops',
            'activeTrip',
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => new BusContextResource($bus),
        ]);
    }
}
