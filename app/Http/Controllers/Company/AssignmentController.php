<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Driver;
use App\Models\Bus;
use App\Models\TransportRoute;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class AssignmentController extends Controller
{
    /**
     * List assignments for the authenticated user's company.
     */
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        // If the user has no company assigned, block access
        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        // Base query for this company (used to derive IDs for dropdowns)
        $base = Assignment::query()
            ->where('company_id', $companyId);

        // IDs used by this company’s assignments (for filters)
        $driverIds = (clone $base)->pluck('driver_id')->unique();
        $routeIds  = (clone $base)->pluck('route_id')->unique();
        $busIds    = (clone $base)->pluck('bus_id')->unique();

        // Dropdown data
        $drivers = Driver::with('user')
            ->whereIn('id', $driverIds)
            ->get();

        $routes = TransportRoute::whereIn('id', $routeIds)->get();

        // Optional: if you ever need a bus dropdown / display list
        $buses = Bus::whereIn('id', $busIds)->get();

        // Actual assignment list query for this company
        $query = Assignment::query()
            ->with(['driver.user', 'bus', 'route', 'company'])
            ->where('company_id', $companyId)
            ->latest('effective_from');

        // Filters (no company filter here – it is fixed to auth company)
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->integer('route_id'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        // Effective From date range
        if ($request->filled('from')) {
            $query->whereDate('effective_from', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('effective_from', '<=', $request->date('to'));
        }

        // Status filter
        if ($request->filled('status')) {
            $today = now()->startOfDay();

            if ($request->status === 'active') {
                $query->whereDate('effective_from', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('effective_to')
                          ->orWhereDate('effective_to', '>=', $today);
                    });
            } elseif ($request->status === 'upcoming') {
                $query->whereDate('effective_from', '>', $today);
            } elseif ($request->status === 'expired') {
                $query->whereNotNull('effective_to')
                      ->whereDate('effective_to', '<', $today);
            }
        }

        $assignments = $query->get();

        return view('company.assignments.index', compact(
            'assignments',
            'routes',
            'drivers',
            'buses',
        ));
    }

    /**
     * Show a single assignment (must belong to the auth user's company).
     */
    public function show(Assignment $assignment): View
    {
        $this->authorizeCompany($assignment);

        $assignment->load(['driver.user', 'bus', 'route', 'company', 'trips']);

        return view('company.assignments.show', compact('assignment'));
    }

    /**
     * Timeline view for an assignment (must belong to the auth user's company).
     */
    public function timeline(Assignment $assignment): View
    {
        $this->authorizeCompany($assignment);

        $assignment->load(['trips', 'driver.user', 'route']);

        return view('company.assignments.timeline', compact('assignment'));
    }

    /**
     * Guard that company users can't see/edit other companies' assignments.
     */
    protected function authorizeCompany(Assignment $assignment): void
    {
        $companyId = auth()->user()->company_id;

        if (!$companyId || $assignment->company_id !== $companyId) {
            abort(403, 'Unauthorized.');
        }
    }
}
