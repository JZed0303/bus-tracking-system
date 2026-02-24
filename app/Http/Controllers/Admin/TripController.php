<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Assignment;
use Illuminate\Contracts\View\View;

class TripController extends Controller
{
   public function today(): View
{
    $today = today();

    $trips = Trip::query()
        ->with([
            'assignment.driver.user',
            'assignment.bus',
            'assignment.route',
            'assignment.company',
        ])
        ->whereDate('trip_date', $today)
        ->orderByRaw("status = 'ongoing' DESC")
        ->latest('actual_start_time')
        ->get();

    // Active assignments today keyed by BUS only (non-expired)
    $activeByBus = \App\Models\Assignment::query()
        ->with(['driver.user', 'bus', 'route', 'company'])
        ->whereDate('effective_from', '<=', $today)
        ->where(function ($q) use ($today) {
            $q->whereNull('effective_to')
              ->orWhereDate('effective_to', '>=', $today);
        })
        ->orderByDesc('effective_from')
        ->get()
        ->keyBy('bus_id');

    foreach ($trips as $trip) {
        if (!$trip->assignment) continue;

        $busId = $trip->assignment->bus_id;

        if (isset($activeByBus[$busId])) {
            // Show CURRENT assignment for that bus (ignores expired)
            $trip->setRelation('assignment', $activeByBus[$busId]);
        }
    }

    return view('admin.trips.today', compact('trips'));
}

    public function active()
    {
        $trip = Trip::where('status', 'ongoing')
            ->latest('actual_start_time')
            ->firstOrFail();

        return redirect()
            ->route('admin.trips.show', $trip)
            ->with('info', 'Showing active trip.');
    }

    public function show(Trip $trip): View
    {
        $trip->load([
            'assignment.bus',
            'assignment.route',
            'assignment.driver.user',
            'checkins' => fn ($q) => $q->with('employee.user')->orderBy('scan_time', 'asc'),
        ]);

        return view('admin.trips.show', compact('trip'));
    }

    public function gpsPlayback(Trip $trip): View
    {
        $locations = $trip->locations()->orderBy('tracked_at')->get();

        return view('admin.trips.gps-playback', compact('trip', 'locations'));
    }
}
