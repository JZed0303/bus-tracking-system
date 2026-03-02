<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Driver;
use App\Models\Bus;
use App\Models\Company;
use App\Models\TransportRoute;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        // Filter dropdown datasets (for your Blade)
        $companies = Company::query()->active()->orderBy('name')->get();
        $routes    = TransportRoute::query()->active()->orderBy('name')->get();
        $drivers   = Driver::query()->with('user')->active()->get();

        $query = Assignment::query()
            ->with(['driver.user', 'bus', 'route', 'company'])
            ->latest('effective_from');

        // Filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

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

        // Status filter (computed by dates)
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

        return view('admin.assignments.index', compact(
            'assignments',
            'companies',
            'routes',
            'drivers'
        ));
    }

    public function create(): View
    {
        $busyAssignments = Assignment::query()
            ->whereHas('trips', fn ($q) => $q->where('status', 'ongoing'))
            ->get(['driver_id', 'bus_id', 'route_id']);

        $busyDriverIds = $busyAssignments->pluck('driver_id')->filter()->unique();
        $busyBusIds = $busyAssignments->pluck('bus_id')->filter()->unique();
        $busyRouteIds = $busyAssignments->pluck('route_id')->filter()->unique();

        return view('admin.assignments.create', [
            // Hide resources currently used by an ongoing trip.
            'drivers'   => Driver::with('user')
                ->active()
                ->when($busyDriverIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $busyDriverIds))
                ->get(),
            'buses'     => Bus::active()
                ->when($busyBusIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $busyBusIds))
                ->get(),
            'routes'    => TransportRoute::active()
                ->when($busyRouteIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $busyRouteIds))
                ->get(),
            'companies' => Company::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver_id'      => ['required', 'exists:drivers,id'],
            'bus_id'         => ['required', 'exists:buses,id'],
            'route_id'       => ['required', 'exists:routes,id'], // change to transport_routes if needed
            'company_id'     => ['required', 'exists:companies,id'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'leg'            => ['nullable', 'in:pickup,dropoff,both'],
        ]);

        $this->validateConflicts($validated);

        Assignment::create([
            ...$validated,
            'status' => 'active', // keep if you store status in DB
            'leg' => $validated['leg'] ?? 'both',
        ]);

        return redirect()
            ->route('admin.assignments.index')
            ->with('success', 'Assignment created successfully.');
    }

    public function show(Assignment $assignment): View
    {
        $assignment->load(['driver.user', 'bus', 'route', 'company', 'trips']);

        return view('admin.assignments.show', compact('assignment'));
    }

    public function timeline(Assignment $assignment): View
    {
        $assignment->load(['trips', 'driver.user', 'route', 'bus', 'company']);

        return view('admin.assignments.timeline', compact('assignment'));
    }

    public function edit(Assignment $assignment): View
    {
        $assignment->load(['driver.user', 'bus', 'route', 'company']);

        return view('admin.assignments.edit', [
            'assignment' => $assignment,
            'drivers'    => Driver::with('user')->active()->get(),
            'buses'      => Bus::active()->get(),
            'routes'     => TransportRoute::active()->get(),
            'companies'  => Company::active()->get(),
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'driver_id'      => ['required', 'exists:drivers,id'],
            'bus_id'         => ['required', 'exists:buses,id'],
            'route_id'       => ['required', 'exists:routes,id'], // change if your table is transport_routes
            'company_id'     => ['required', 'exists:companies,id'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'leg'            => ['nullable', 'in:pickup,dropoff,both'],
        ]);

        $this->validateConflicts($validated, $assignment->id);

        $assignment->update([
            ...$validated,
            'leg' => $validated['leg'] ?? 'both',
        ]);

        return redirect()
            ->route('admin.assignments.index')
            ->with('success', 'Assignment updated successfully.');
    }

    /**
     * Prevent assigning driver/bus/route when they are currently on an ongoing trip.
     * Supports ignoring a specific assignment ID (for update).
     */
    protected function validateConflicts(array $data, ?int $ignoreId = null): void
    {
        // NEW: leg-aware conflict validation.
        // "both" conflicts with pickup/dropoff; same leg conflicts with same leg.
        $incomingLeg = $data['leg'] ?? 'both';

        $busyAssignments = Assignment::query()
            ->whereHas('trips', fn ($q) => $q->where('status', 'ongoing'));

        if ($ignoreId) {
            $busyAssignments->whereKeyNot($ignoreId);
        }

        $messages = [];

        if ((clone $busyAssignments)->where('driver_id', $data['driver_id'])->exists()) {
            $messages['driver_id'] = 'Selected driver is currently on an ongoing trip.';
        }

        if ((clone $busyAssignments)->where('bus_id', $data['bus_id'])->exists()) {
            $messages['bus_id'] = 'Selected bus is currently used by an ongoing trip.';
        }

        if ((clone $busyAssignments)->where('route_id', $data['route_id'])->exists()) {
            $messages['route_id'] = 'Selected route is currently used by an ongoing trip.';
        }

        // Existing protection: do not assign resources already in an ongoing trip.
        if (!empty($messages)) {
            throw ValidationException::withMessages($messages);
        }

        $from = $data['effective_from'];
        $to = $data['effective_to'] ?? '9999-12-31';

        // NEW: prevent overlapping active assignment windows for the same bus and conflicting leg.
        $overlappingAssignments = Assignment::query()
            ->where('status', 'active')
            ->where('bus_id', $data['bus_id'])
            ->whereDate('effective_from', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $from);
            })
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get(['id', 'leg']);

        $hasLegConflict = $overlappingAssignments->contains(function ($existing) use ($incomingLeg) {
            $existingLeg = $existing->leg ?? 'both';
            return $existingLeg === 'both' || $incomingLeg === 'both' || $existingLeg === $incomingLeg;
        });

        if ($hasLegConflict) {
            throw ValidationException::withMessages([
                'bus_id' => 'Selected bus already has an overlapping active assignment for the same trip leg.',
            ]);
        }
    }
}
