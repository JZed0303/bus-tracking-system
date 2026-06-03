<?php

namespace App\Http\Controllers\Admin;

use App\Events\BusIncidentReported;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Checkin;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Trip;
use App\Models\TripEmployeeTransfer;
use App\Support\TripReportService;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;

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

    $onboardCounts = collect();
    if ($trips->isNotEmpty()) {
        $tripIds = $trips->pluck('id')->values();

        $onboardCounts = DB::table('checkins as c')
            ->selectRaw('c.trip_id, COUNT(DISTINCT c.employee_id) as onboard_count')
            ->whereIn('c.trip_id', $tripIds)
            ->where('c.scan_type', 'checkin')
            ->whereNull('c.voided_at')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('checkins as c2')
                    ->whereColumn('c2.trip_id', 'c.trip_id')
                    ->whereColumn('c2.employee_id', 'c.employee_id')
                    ->where('c2.scan_type', 'checkout')
                    ->whereNull('c2.voided_at');
            })
            ->groupBy('c.trip_id')
            ->pluck('onboard_count', 'trip_id');
    }

    foreach ($trips as $trip) {
        $onboardCount = (int) ($onboardCounts[$trip->id] ?? 0);
        $capacity = (int) ($trip->assignment?->bus?->capacity ?? 0);

        $trip->setAttribute('onboard_count', $onboardCount);
        $trip->setAttribute('bus_capacity', $capacity);
        $trip->setAttribute('available_capacity', max(0, $capacity - $onboardCount));
    }

    return view('admin.trips.today', compact('trips'));
}

    public function active()
    {
        $trip = Trip::where('status', 'ongoing')
            ->latest('actual_start_time')
            ->first();

        if (!$trip) {
            return redirect()
                ->route('admin.trips.today')
                ->with('info', 'No active trips.');
        }

        return redirect()
            ->route('admin.trips.show', $trip)
            ->with('info', 'Showing active trip.');
    }

    public function show(Trip $trip): View
    {
        app(TripReportService::class)->prepareTrip($trip);

        $today = now('Asia/Manila')->toDateString();
        $companyId = (int) optional($trip->assignment)->company_id;
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

        $expectedEmployees = $this->buildExpectedEmployeeStatuses($trip, $companyId);

        return view('admin.trips.show', compact('trip', 'onboardEmployees', 'replacementBuses', 'pendingTransferEmployees', 'expectedEmployees'));
    }

    public function report(Trip $trip): View
    {
        $companyId = (int) optional($trip->assignment)->company_id;
        $report = app(TripReportService::class)->buildReport($trip, $companyId);

        return view('reports.trip', [
            'report' => $report,
            'trip' => $trip,
            'scope' => 'admin',
            'routePrefix' => 'admin.trips',
            'backUrl' => route('admin.trips.show', $trip),
        ]);
    }

    public function exportReport(Request $request, Trip $trip, string $type)
    {
        $companyId = (int) optional($trip->assignment)->company_id;
        $service = app(TripReportService::class);
        $report = $service->buildReport($trip, $companyId);

        if (in_array($type, ['excel', 'xlsx'], true)) {
            return $service->exportExcel($report);
        }

        if ($type === 'csv') {
            return $service->exportExcel($report, ExcelWriter::CSV);
        }

        if ($type === 'pdf') {
            return $service->exportPdf($report, $request->boolean('preview'));
        }

        abort(404);
    }

    private function buildExpectedEmployeeStatuses(Trip $trip, int $companyId)
    {
        $routeId = (int) optional($trip->assignment)->route_id;
        $tripDate = $trip->trip_date?->toDateString();

        $scanGroups = $trip->checkins
            ->whereNull('voided_at')
            ->sortBy('scan_time')
            ->groupBy('employee_id');

        $scheduledEmployees = collect();

        if ($companyId > 0 && $routeId > 0 && $tripDate) {
            $scheduledEmployees = EmployeeSchedule::query()
                ->with(['employee.user', 'employee.pickupStop.stop'])
                ->where('company_id', $companyId)
                ->where('route_id', $routeId)
                ->whereDate('schedule_date', $tripDate)
                ->where('status', '!=', 'cancelled')
                ->orderBy('expected_pickup_time')
                ->get();
        }

        if ($scheduledEmployees->isEmpty() && $companyId > 0 && $routeId > 0) {
            return Employee::query()
                ->with(['user', 'pickupStop.stop'])
                ->where('company_id', $companyId)
                ->whereHas('pickupStop.stop', fn ($q) => $q->where('route_id', $routeId))
                ->orderBy('employee_code')
                ->get()
                ->map(function (Employee $employee) use ($scanGroups) {
                    $scans = $scanGroups->get($employee->id, collect());
                    $checkin = $scans->where('scan_type', 'checkin')->last();
                    $checkout = $scans->where('scan_type', 'checkout')->last();

                    return [
                        'employee' => $employee,
                        'expected_pickup_time' => null,
                        'pickup_stop' => $employee->pickupStop?->stop?->address,
                        'schedule_status' => 'scheduled',
                        'checkin' => $checkin,
                        'checkout' => $checkout,
                        'status' => $checkout ? 'checked_out' : ($checkin ? 'checked_in' : 'pending'),
                    ];
                })
                ->values();
        }

        return $scheduledEmployees
            ->groupBy('employee_id')
            ->map(function ($rows) use ($scanGroups) {
                $schedule = $rows->sortBy('expected_pickup_time')->first();
                $employee = $schedule->employee;
                $scans = $scanGroups->get($employee->id, collect());
                $checkin = $scans->where('scan_type', 'checkin')->last();
                $checkout = $scans->where('scan_type', 'checkout')->last();

                $status = match (true) {
                    (bool) $checkout => 'checked_out',
                    (bool) $checkin => 'checked_in',
                    $schedule->status === 'missed' => 'missed',
                    default => 'pending',
                };

                return [
                    'employee' => $employee,
                    'expected_pickup_time' => $schedule->expected_pickup_time,
                    'pickup_stop' => $employee->pickupStop?->stop?->address,
                    'schedule_status' => $schedule->status ?? 'scheduled',
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'status' => $status,
                ];
            })
            ->values()
            ->sortBy(fn ($row) => $row['expected_pickup_time'] ? $row['expected_pickup_time']->format('H:i') : '99:99')
            ->values();
    }

    public function reportIncident(Request $request, Trip $trip): RedirectResponse
    {
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

        $trip->loadMissing('assignment.bus');

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
                ->where('company_id', $trip->assignment->company_id)
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
                'incident_reason' => $reason,
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

        event(new BusIncidentReported([
            'trip_id' => (int) $trip->id,
            'bus_id' => (int) ($trip->assignment?->bus_id ?? 0),
            'plate_number' => $trip->assignment?->bus?->plate_number,
            'company_id' => (int) ($trip->assignment?->company_id ?? 0),
            'company' => $trip->assignment?->company?->name,
            'route' => $trip->assignment?->route?->name,
            'incident_type' => $trip->ended_reason,
            'reason' => $trip->incident_reason,
            'reported_at' => optional($trip->incident_reported_at)->toIso8601String(),
            'status' => $trip->status,
        ]));

        return back()->with('success', 'Incident has been recorded successfully.');
    }

    public function gpsPlayback(Trip $trip): View
    {
        $locations = $trip->locations()->orderBy('tracked_at')->get();

        return view('admin.trips.gps-playback', compact('trip', 'locations'));
    }

    public function voidCheckin(Request $request, Trip $trip, Checkin $checkin): RedirectResponse
    {
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
