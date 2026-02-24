<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Contracts\View\View;

class DriverTripController extends Controller
{
    public function index(Driver $driver): View
    {
        $trips = $driver->trips()
            ->with([
                'assignment.route',
                'assignment.bus', // or assignment.vehicle if that is your model
            ])
            ->latest()
            ->get();

        return view('admin.drivers.trips', compact('driver', 'trips'));
    }
}
