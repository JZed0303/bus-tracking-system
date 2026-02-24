<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TripController extends Controller
{
   public function start(Trip $trip, TripService $service)
{
    $service->startTrip($trip, auth()->user()->driver->id);

    return response()->json(['status' => 'started']);
}
public function end(Trip $trip, TripService $service)
{
    $service->endTrip($trip, auth()->user()->driver->id);

    return response()->json(['status' => 'completed']);
}


}
