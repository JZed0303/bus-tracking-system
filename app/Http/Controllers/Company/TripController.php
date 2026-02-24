<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    /**
     * Optional: keep compatibility with routes expecting index()
     */
    public function index()
    {
        // Either redirect to "today" route:
        return redirect()->route('company.trips.today');

        // OR if you prefer to render directly:
        // return $this->today();
    }

    /**
     * Helper: current company id
     */
    protected function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    /**
     * Helper: ensure trip belongs to this company
     */
    protected function authorizeTrip(Trip $trip): void
    {
        $companyId = $this->companyId();
        $tripCompanyId = optional(optional($trip->assignment)->company)->id;

        abort_unless($tripCompanyId === $companyId, 404);
    }

    /**
     * Today’s Trips for this company only
     */
    public function today(): View
    {
        $companyId = $this->companyId();

        $trips = Trip::query()
            ->with([
                'assignment.driver.user',
                'assignment.bus',
                'assignment.route',
                'assignment.company',
            ])
            ->whereDate('trip_date', today())
            ->whereHas('assignment', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderByRaw("status = 'ongoing' DESC")
            ->latest('actual_start_time')
            ->get();

        return view('company.trips.today', compact('trips'));
    }

    /**
     * Redirect to ACTIVE trip of this company only
     */
    public function active()
    {
        $companyId = $this->companyId();

        $trip = Trip::query()
            ->where('status', 'ongoing')
            ->whereHas('assignment', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->latest('actual_start_time')
            ->firstOrFail();

        return redirect()
            ->route('company.trips.show', $trip)
            ->with('info', 'Showing active trip.');
    }

    /**
     * Trip details (timeline + checkins + gps tabs) for this company
     */
    public function show(Trip $trip): View
    {
        $this->authorizeTrip($trip);

        $trip->load([
            'assignment.bus',
            'assignment.route',
            'assignment.driver.user',
            'checkins' => fn ($q) => $q
                ->with('employee.user')
                ->orderBy('scan_time', 'asc'),
        ]);

        return view('company.trips.show', compact('trip'));
    }

    /**
     * GPS playback (map replay) for this company
     */
    public function gpsPlayback(Trip $trip): View
    {
        $this->authorizeTrip($trip);

        $locations = $trip->locations()
            ->orderBy('tracked_at')
            ->get();

        return view('company.trips.gps-playback', compact('trip', 'locations'));
    }
}
