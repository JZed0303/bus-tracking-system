<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Checkin;
use App\Models\Employee;
use App\Models\Trip;
use App\Models\TripEmployeeTransfer;
use App\Support\AuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                ->with('employee.user', 'voidedByUser')
                ->orderBy('scan_time', 'asc'),
        ]);

        $today = now('Asia/Manila')->toDateString();
        $companyId = $this->companyId();
        $currentBusId = (int) optional($trip->assignment)->bus_id;

        $onboardEmployeeIds = Checkin::query()
            ->where('trip_id', $trip->id)
            ->where('scan_type', 'checkin')
            ->whereNull('voided_at')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('checkins as c2')
                    ->whereColumn('c2.trip_id', 'checkins.trip_id')
                    ->whereColumn('c2.employee_id', 'checkins.employee_id')
                    ->where('c2.scan_type', 'checkout')
                    ->whereNull('c2.voided_at');
            })
            ->pluck('employee_id')
            ->unique()
            ->values();

        $onboardEmployees = Employee::query()
            ->with('user')
            ->whereIn('id', $onboardEmployeeIds)
            ->orderBy('id')
            ->get();

        $replacementBuses = Bus::query()
            ->where('status', 'active')
            ->whereKeyNot($currentBusId)
            ->where(function ($q) use ($companyId, $today, $trip) {
                // Allow buses with no active assignment today (we will auto-create one on incident submit).
                $q->whereDoesntHave('assignments', function ($aq) use ($today) {
                    $aq->where('status', 'active')
                        ->whereDate('effective_from', '<=', $today)
                        ->where(function ($x) use ($today) {
                            $x->whereNull('effective_to')
                                ->orWhereDate('effective_to', '>=', $today);
                        });
                })->orWhereHas('assignments', function ($aq) use ($companyId, $today, $trip) {
                    $aq->where('company_id', $companyId)
                        ->where('status', 'active')
                        ->whereDate('effective_from', '<=', $today)
                        ->where(function ($x) use ($today) {
                            $x->whereNull('effective_to')
                                ->orWhereDate('effective_to', '>=', $today);
                        })
                        ->where(function ($x) use ($trip) {
                            $x->where('leg', 'both')
                                ->orWhere('leg', $trip->direction)
                                ->orWhereNull('leg');
                        });
                });
            })
            ->whereDoesntHave('trips', fn ($q) => $q->where('trips.status', 'ongoing'))
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'capacity']);

        $pendingTransferEmployees = TripEmployeeTransfer::query()
            ->where('to_trip_id', $trip->id)
            ->where('status', 'pending_confirm')
            ->with('employee.user')
            ->get();

        return view('company.trips.show', compact('trip', 'onboardEmployees', 'replacementBuses', 'pendingTransferEmployees'));
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

    public function reportIncident(Request $request, Trip $trip): RedirectResponse
    {
        $this->authorizeTrip($trip);

        if ($trip->status !== 'ongoing') {
            return back()->withErrors(['trip' => 'Incident can only be reported for ongoing trips.']);
        }

        $validated = $request->validate([
            'incident_type' => 'required|in:maintenance,breakdown,emergency',
            'reason' => 'required|string|max:255',
            'replacement_bus_id' => 'nullable|integer|exists:buses,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'override_capacity' => 'nullable|boolean',
        ]);

        $today = now('Asia/Manila')->toDateString();
        $replacementBus = null;
        $replacementAssignment = null;
        $onboardEmployeeIds = collect();

        if (!empty($validated['replacement_bus_id'])) {
            $replacementBus = Bus::query()->find((int) $validated['replacement_bus_id']);
            if (!$replacementBus || $replacementBus->status !== 'active') {
                return back()->withErrors(['replacement_bus_id' => 'Replacement bus must be active.']);
            }

            $replacementAssignment = $replacementBus->assignments()
                ->active()
                ->where('company_id', $this->companyId())
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->where(function ($q) use ($trip) {
                    $q->where('leg', 'both')
                        ->orWhere('leg', $trip->direction)
                        ->orWhereNull('leg');
                })
                ->latest('effective_from')
                ->first();

            $hasOngoingTrip = Trip::query()
                ->where('status', 'ongoing')
                ->whereHas('assignment', fn ($q) => $q->where('bus_id', $replacementBus->id))
                ->exists();

            if ($hasOngoingTrip) {
                return back()->withErrors(['replacement_bus_id' => 'Replacement bus already has an ongoing trip.']);
            }

            $onboardEmployeeIds = Checkin::query()
                ->where('trip_id', $trip->id)
                ->where('scan_type', 'checkin')
                ->whereNull('voided_at')
                ->when(!empty($validated['employee_ids']), fn ($q) => $q->whereIn('employee_id', $validated['employee_ids']))
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('checkins as c2')
                        ->whereColumn('c2.trip_id', 'checkins.trip_id')
                        ->whereColumn('c2.employee_id', 'checkins.employee_id')
                        ->where('c2.scan_type', 'checkout')
                        ->whereNull('c2.voided_at');
                })
                ->pluck('employee_id')
                ->unique()
                ->values();

            $requiredSeats = $onboardEmployeeIds->count();
            $capacity = (int) ($replacementBus->capacity ?? 0);
            $overrideRequested = (bool) ($validated['override_capacity'] ?? false);
            $canOverrideCapacity = auth()->user()?->can('update_trips') || auth()->user()?->hasRole('super_admin');

            if ($requiredSeats > $capacity && !($overrideRequested && $canOverrideCapacity)) {
                return back()->withErrors([
                    'replacement_bus_id' => "Insufficient bus capacity. Required seats: {$requiredSeats}, capacity: {$capacity}.",
                ]);
            }

            if ($requiredSeats > $capacity && $overrideRequested && !$canOverrideCapacity) {
                return back()->withErrors([
                    'override_capacity' => 'You are not allowed to override capacity limits.',
                ]);
            }
        }

        $result = DB::transaction(function () use ($validated, $trip, $replacementBus, $replacementAssignment, $onboardEmployeeIds) {
            $reason = $validated['reason'] ?? $validated['incident_type'];

            $trip->update([
                'status' => 'cancelled',
                'actual_end_time' => now(),
                'ended_reason' => $validated['incident_type'],
                'incident_reported_at' => now(),
            ]);

            if (in_array($validated['incident_type'], ['maintenance', 'breakdown'], true)) {
                $trip->assignment?->bus?->update(['status' => 'maintenance']);
            }

            if (!$replacementBus) {
                return [
                    'replacement_trip_id' => null,
                    'employee_ids' => [],
                    'transferred_count' => 0,
                ];
            }

            if (!$replacementAssignment) {
                // Auto-provision assignment for replacement bus when none exists.
                $replacementAssignment = \App\Models\Assignment::create([
                    'driver_id' => $trip->assignment->driver_id,
                    'bus_id' => $replacementBus->id,
                    'company_id' => $trip->assignment->company_id,
                    'route_id' => $trip->assignment->route_id,
                    'effective_from' => now('Asia/Manila')->toDateString(),
                    'effective_to' => null,
                    'status' => 'active',
                    'leg' => $trip->direction,
                ]);
            }

            $replacementTrip = Trip::create([
                'assignment_id' => $replacementAssignment->id,
                'transfer_from_trip_id' => $trip->id,
                'trip_date' => now()->toDateString(),
                'direction' => $trip->direction,
                'status' => 'ongoing',
                'actual_start_time' => now(),
            ]);

            foreach ($onboardEmployeeIds as $employeeId) {
                Checkin::firstOrCreate([
                    'trip_id' => $trip->id,
                    'employee_id' => $employeeId,
                    'scan_type' => 'checkout',
                ], [
                    'scan_time' => now('Asia/Manila'),
                    'scan_lat' => null,
                    'scan_lng' => null,
                    'scanned_by_employee_id' => $employeeId,
                ]);

                TripEmployeeTransfer::updateOrCreate([
                    'from_trip_id' => $trip->id,
                    'to_trip_id' => $replacementTrip->id,
                    'employee_id' => $employeeId,
                ], [
                    'status' => 'pending_confirm',
                    'reason' => $reason,
                    'transferred_at' => now(),
                ]);
            }

            return [
                'replacement_trip_id' => $replacementTrip->id,
                'employee_ids' => $onboardEmployeeIds->values()->all(),
                'transferred_count' => count($onboardEmployeeIds),
            ];
        });

        AuditTrail::log(
            event: 'trip_incident_reported',
            auditable: $trip,
            oldValues: [
                'trip_status' => 'ongoing',
                'bus_id' => $trip->assignment?->bus_id,
            ],
            newValues: [
                'trip_status' => 'cancelled',
                'incident_type' => $validated['incident_type'],
                'reason' => $validated['reason'],
                'old_bus_id' => $trip->assignment?->bus_id,
                'replacement_bus_id' => $replacementBus?->id,
                'replacement_trip_id' => $result['replacement_trip_id'],
                'affected_employee_ids' => $result['employee_ids'],
                'transferred_count' => $result['transferred_count'],
                'reported_at' => now()->toIso8601String(),
            ],
            tags: 'incident,transfer'
        );

        return back()->with('success', 'Incident has been recorded successfully.');
    }

    public function voidCheckin(Request $request, Trip $trip, Checkin $checkin): RedirectResponse
    {
        $this->authorizeTrip($trip);

        // VOID FLOW: defensive guard to prevent cross-trip voiding.
        if ((int) $checkin->trip_id !== (int) $trip->id) {
            abort(404);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if ($checkin->voided_at) {
            return back()->withErrors(['checkin' => 'This scan record is already voided.']);
        }

        $checkin->update([
            'voided_at' => now(),
            'void_reason' => $validated['reason'],
            'voided_by_user_id' => auth()->id(),
        ]);

        AuditTrail::log(
            event: 'checkin_voided',
            auditable: $checkin,
            oldValues: [
                'voided_at' => null,
                'void_reason' => null,
            ],
            newValues: [
                'trip_id' => $trip->id,
                'employee_id' => $checkin->employee_id,
                'scan_type' => $checkin->scan_type,
                'voided_at' => $checkin->voided_at?->toIso8601String(),
                'void_reason' => $checkin->void_reason,
                'voided_by_user_id' => $checkin->voided_by_user_id,
            ],
            tags: 'checkin,void'
        );

        return back()->with('success', 'Scan record was voided successfully.');
    }
}
