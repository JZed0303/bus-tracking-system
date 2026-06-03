<?php

namespace App\Http\Controllers\Company;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Driver;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Support\TripReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'operations' => 'Daily Operations Report',
        'incidents' => 'Incident Report',
        'fleet' => 'Fleet Utilization Report',
        'attendance' => 'Driver Attendance Summary',
        'trip_detail' => 'Detailed Trip Report',
    ];

    public function index(Request $request)
    {
        [$filters, $filterOptions] = $this->resolveFilters($request);
        $report = $this->buildReport($filters);

        return view('company.reports.index', [
            'report' => $report,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'reportTypes' => self::REPORT_TYPES,
        ]);
    }

    public function export(Request $request, string $type)
    {
        [$filters] = $this->resolveFilters($request);
        $report = $this->buildReport($filters);

        if (($report['type'] ?? null) === 'trip_detail') {
            return $this->exportTripDetail($report, $type, $request->boolean('preview'));
        }

        if (in_array($type, ['excel', 'xlsx'], true)) {
            return $this->exportExcel($report);
        }

        if ($type === 'csv') {
            return $this->exportExcel($report, ExcelWriter::CSV);
        }

        if ($type === 'pdf') {
            return $this->exportPdf($report, $filters, $request->boolean('preview'));
        }

        abort(404);
    }

    public function print(Request $request)
    {
        [$filters] = $this->resolveFilters($request);
        $report = $this->buildReport($filters);

        if (($report['type'] ?? null) === 'trip_detail') {
            return app(TripReportService::class)->exportPdf($report['trip_report'], true);
        }

        return $this->exportPdf($report, $filters, true, true);
    }

    private function resolveFilters(Request $request): array
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $dateFrom = Carbon::parse($request->input('date_from', now()->subDays(6)->toDateString()))->startOfDay();
        $dateTo = Carbon::parse($request->input('date_to', now()->toDateString()))->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $reportType = (string) $request->input('report_type', 'operations');
        if (!array_key_exists($reportType, self::REPORT_TYPES)) {
            $reportType = 'operations';
        }

        $companyId = $isSuperAdmin
            ? (int) $request->integer('company_id', 0)
            : (int) ($user->company_id ?? 0);

        if (!$isSuperAdmin && $companyId <= 0) {
            abort(403, 'No company assigned to your account.');
        }

        $farePerPassenger = max(0, (float) $request->input('fare_per_passenger', 20));

        $filters = [
            'report_type' => $reportType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'company_id' => $companyId,
            'trip_id' => (int) $request->integer('trip_id', 0),
            'route_id' => (int) $request->integer('route_id', 0),
            'bus_id' => (int) $request->integer('bus_id', 0),
            'driver_id' => (int) $request->integer('driver_id', 0),
            'status' => (string) $request->input('status', ''),
            'fare_per_passenger' => $farePerPassenger,
            'is_super_admin' => $isSuperAdmin,
        ];

        $routeOptions = TransportRoute::query()
            ->select(['id', 'name'])
            ->when($companyId > 0, function (Builder $query) use ($companyId) {
                $query->where(function (Builder $inner) use ($companyId) {
                    $inner->where('company_id', $companyId)
                        ->orWhereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('company_id', $companyId));
                });
            })
            ->orderBy('name')
            ->get();

        $busOptions = Bus::query()
            ->select(['id', 'plate_number'])
            ->when($companyId > 0, function (Builder $query) use ($companyId) {
                $query->whereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('company_id', $companyId));
            })
            ->orderBy('plate_number')
            ->get();

        $driverOptions = Driver::query()
            ->select(['id', 'user_id'])
            ->with(['user:id,first_name,middle_name,last_name'])
            ->when($companyId > 0, fn (Builder $query) => $query->where('company_id', $companyId))
            ->orderBy('id')
            ->get();

        $companyOptions = $isSuperAdmin
            ? Company::query()->select(['id', 'name'])->orderBy('name')->get()
            : collect();

        $tripOptions = Trip::query()
            ->with([
                'assignment.bus:id,plate_number',
                'assignment.route:id,name',
            ])
            ->when($companyId > 0, fn (Builder $query) => $query->whereHas('assignment', fn (Builder $q) => $q->where('company_id', $companyId)))
            ->whereBetween('trip_date', [
                $dateFrom->toDateString(),
                $dateTo->toDateString(),
            ])
            ->orderByDesc('trip_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $filterOptions = [
            'companies' => $companyOptions,
            'trips' => $tripOptions,
            'routes' => $routeOptions,
            'buses' => $busOptions,
            'drivers' => $driverOptions,
            'statuses' => ['scheduled', 'ongoing', 'completed', 'cancelled'],
        ];

        return [$filters, $filterOptions];
    }

    private function buildReport(array $filters): array
    {
        return match ($filters['report_type']) {
            'operations' => $this->buildOperationsReport($filters),
            'incidents' => $this->buildIncidentReport($filters),
            'fleet' => $this->buildFleetReport($filters),
            'attendance' => $this->buildAttendanceReport($filters),
            'trip_detail' => $this->buildTripDetailReport($filters),
            default => $this->buildOperationsReport($filters),
        };
    }

    private function baseTripQuery(array $filters): Builder
    {
        $query = Trip::query()
            ->with([
                'assignment.bus:id,plate_number,capacity,status',
                'assignment.route:id,name',
                'assignment.driver.user:id,first_name,middle_name,last_name',
            ])
            ->whereBetween('trip_date', [
                $filters['date_from']->toDateString(),
                $filters['date_to']->toDateString(),
            ]);

        if ($filters['company_id'] > 0) {
            $query->whereHas('assignment', fn (Builder $q) => $q->where('company_id', $filters['company_id']));
        }

        if ($filters['route_id'] > 0) {
            $query->whereHas('assignment', fn (Builder $q) => $q->where('route_id', $filters['route_id']));
        }

        if ($filters['bus_id'] > 0) {
            $query->whereHas('assignment', fn (Builder $q) => $q->where('bus_id', $filters['bus_id']));
        }

        if ($filters['driver_id'] > 0) {
            $query->whereHas('assignment', fn (Builder $q) => $q->where('driver_id', $filters['driver_id']));
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    private function buildOperationsReport(array $filters): array
    {
        $trips = $this->baseTripQuery($filters)
            ->withCount([
                'checkins as checkins_in_count' => fn (Builder $q) => $q->where('scan_type', 'checkin')->whereNull('voided_at'),
                'checkins as checkins_out_count' => fn (Builder $q) => $q->where('scan_type', 'checkout')->whereNull('voided_at'),
            ])
            ->orderByDesc('trip_date')
            ->orderByDesc('id')
            ->get();

        $rows = $trips->map(function (Trip $trip) {
            $driverName = optional(optional(optional($trip->assignment)->driver)->user)->full_name ?? 'N/A';
            $checkinsIn = (int) ($trip->checkins_in_count ?? 0);
            $checkinsOut = (int) ($trip->checkins_out_count ?? 0);

            return [
                'trip_date' => optional($trip->trip_date)->format('Y-m-d') ?? 'N/A',
                'route' => optional(optional($trip->assignment)->route)->name ?? 'N/A',
                'bus' => optional(optional($trip->assignment)->bus)->plate_number ?? 'N/A',
                'driver' => $driverName,
                'direction' => ucfirst((string) $trip->direction),
                'status' => ucfirst((string) $trip->status),
                'start_time' => optional($trip->actual_start_time)->format('Y-m-d H:i') ?? 'N/A',
                'end_time' => optional($trip->actual_end_time)->format('Y-m-d H:i') ?? 'N/A',
                'checkins_in' => $checkinsIn,
                'checkins_out' => $checkinsOut,
                'onboard' => max(0, $checkinsIn - $checkinsOut),
            ];
        });

        return [
            'type' => 'operations',
            'title' => self::REPORT_TYPES['operations'],
            'columns' => [
                ['key' => 'trip_date', 'label' => 'Trip Date'],
                ['key' => 'route', 'label' => 'Route'],
                ['key' => 'bus', 'label' => 'Bus'],
                ['key' => 'driver', 'label' => 'Driver'],
                ['key' => 'direction', 'label' => 'Direction'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'start_time', 'label' => 'Start Time'],
                ['key' => 'end_time', 'label' => 'End Time'],
                ['key' => 'checkins_in', 'label' => 'Check-ins'],
                ['key' => 'checkins_out', 'label' => 'Check-outs'],
                ['key' => 'onboard', 'label' => 'Onboard'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Trips', 'value' => $rows->count()],
                ['label' => 'Completed Trips', 'value' => $rows->where('status', 'Completed')->count()],
                ['label' => 'Cancelled Trips', 'value' => $rows->where('status', 'Cancelled')->count()],
                ['label' => 'Total Check-ins', 'value' => $rows->sum('checkins_in')],
            ],
        ];
    }

    private function buildRevenueReport(array $filters): array
    {
        $fare = $filters['fare_per_passenger'];

        $trips = $this->baseTripQuery($filters)
            ->withCount([
                'checkins as checkins_in_count' => fn (Builder $q) => $q->where('scan_type', 'checkin')->whereNull('voided_at'),
            ])
            ->orderByDesc('trip_date')
            ->orderByDesc('id')
            ->get();

        $rows = $trips->map(function (Trip $trip) use ($fare) {
            $passengers = (int) ($trip->checkins_in_count ?? 0);
            $revenue = $passengers * $fare;

            return [
                'trip_date' => optional($trip->trip_date)->format('Y-m-d') ?? 'N/A',
                'route' => optional(optional($trip->assignment)->route)->name ?? 'N/A',
                'bus' => optional(optional($trip->assignment)->bus)->plate_number ?? 'N/A',
                'driver' => optional(optional(optional($trip->assignment)->driver)->user)->full_name ?? 'N/A',
                'passengers' => $passengers,
                'fare_per_passenger' => number_format($fare, 2),
                'estimated_revenue' => number_format($revenue, 2),
            ];
        });

        return [
            'type' => 'revenue',
            'title' => self::REPORT_TYPES['revenue'],
            'columns' => [
                ['key' => 'trip_date', 'label' => 'Trip Date'],
                ['key' => 'route', 'label' => 'Route'],
                ['key' => 'bus', 'label' => 'Bus'],
                ['key' => 'driver', 'label' => 'Driver'],
                ['key' => 'passengers', 'label' => 'Passengers'],
                ['key' => 'fare_per_passenger', 'label' => 'Fare/Passenger'],
                ['key' => 'estimated_revenue', 'label' => 'Estimated Revenue'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Trips', 'value' => $rows->count()],
                ['label' => 'Total Passengers', 'value' => $rows->sum('passengers')],
                [
                    'label' => 'Total Estimated Revenue',
                    'value' => number_format($rows->sum(fn (array $row) => (float) str_replace(',', '', $row['estimated_revenue'])), 2),
                ],
            ],
        ];
    }

    private function buildIncidentReport(array $filters): array
    {
        $trips = $this->baseTripQuery($filters)
            ->where(function (Builder $query) {
                $query->whereNotNull('incident_reported_at')
                    ->orWhereNotNull('incident_reason')
                    ->orWhereIn('ended_reason', ['incident', 'breakdown', 'emergency'])
                    ->orWhere('status', 'cancelled');
            })
            ->orderByDesc('trip_date')
            ->orderByDesc('id')
            ->get();

        $rows = $trips->map(function (Trip $trip) {
            return [
                'trip_date' => optional($trip->trip_date)->format('Y-m-d') ?? 'N/A',
                'route' => optional(optional($trip->assignment)->route)->name ?? 'N/A',
                'bus' => optional(optional($trip->assignment)->bus)->plate_number ?? 'N/A',
                'driver' => optional(optional(optional($trip->assignment)->driver)->user)->full_name ?? 'N/A',
                'status' => ucfirst((string) $trip->status),
                'ended_reason' => $trip->ended_reason ? Str::headline((string) $trip->ended_reason) : 'N/A',
                'incident_reason' => $trip->incident_reason ?: 'N/A',
                'reported_at' => optional($trip->incident_reported_at)->format('Y-m-d H:i') ?? 'N/A',
            ];
        });

        return [
            'type' => 'incidents',
            'title' => self::REPORT_TYPES['incidents'],
            'columns' => [
                ['key' => 'trip_date', 'label' => 'Trip Date'],
                ['key' => 'route', 'label' => 'Route'],
                ['key' => 'bus', 'label' => 'Bus'],
                ['key' => 'driver', 'label' => 'Driver'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'ended_reason', 'label' => 'Ended Reason'],
                ['key' => 'incident_reason', 'label' => 'Incident Reason'],
                ['key' => 'reported_at', 'label' => 'Reported At'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Incident Rows', 'value' => $rows->count()],
                ['label' => 'Cancelled Trips', 'value' => $rows->where('status', 'Cancelled')->count()],
            ],
        ];
    }

    private function buildFleetReport(array $filters): array
    {
        $rangeDays = max(1, $filters['date_from']->diffInDays($filters['date_to']) + 1);

        $rows = DB::table('buses')
            ->join('assignments', 'assignments.bus_id', '=', 'buses.id')
            ->join('trips', 'trips.assignment_id', '=', 'assignments.id')
            ->leftJoin('checkins', function ($join) {
                $join->on('checkins.trip_id', '=', 'trips.id')
                    ->where('checkins.scan_type', '=', 'checkin')
                    ->whereNull('checkins.voided_at');
            })
            ->when($filters['company_id'] > 0, fn ($query) => $query->where('assignments.company_id', $filters['company_id']))
            ->when($filters['route_id'] > 0, fn ($query) => $query->where('assignments.route_id', $filters['route_id']))
            ->when($filters['driver_id'] > 0, fn ($query) => $query->where('assignments.driver_id', $filters['driver_id']))
            ->when($filters['bus_id'] > 0, fn ($query) => $query->where('buses.id', $filters['bus_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('trips.status', $filters['status']))
            ->whereBetween('trips.trip_date', [
                $filters['date_from']->toDateString(),
                $filters['date_to']->toDateString(),
            ])
            ->groupBy('buses.id', 'buses.plate_number', 'buses.capacity', 'buses.status')
            ->orderBy('buses.plate_number')
            ->selectRaw('buses.plate_number as bus')
            ->selectRaw('buses.capacity as capacity')
            ->selectRaw('buses.status as bus_status')
            ->selectRaw('COUNT(DISTINCT trips.id) as total_trips')
            ->selectRaw("SUM(CASE WHEN trips.status = 'completed' THEN 1 ELSE 0 END) as completed_trips")
            ->selectRaw("SUM(CASE WHEN trips.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_trips")
            ->selectRaw('COUNT(DISTINCT trips.trip_date) as active_days')
            ->selectRaw('COUNT(checkins.id) as passenger_count')
            ->get()
            ->map(function ($row) use ($rangeDays) {
                $totalTrips = (int) $row->total_trips;
                $activeDays = (int) $row->active_days;
                $passengers = (int) $row->passenger_count;

                return [
                    'bus' => $row->bus,
                    'bus_status' => ucfirst((string) $row->bus_status),
                    'capacity' => (int) $row->capacity,
                    'total_trips' => $totalTrips,
                    'completed_trips' => (int) $row->completed_trips,
                    'cancelled_trips' => (int) $row->cancelled_trips,
                    'active_days' => $activeDays,
                    'utilization_percent' => number_format(($activeDays / $rangeDays) * 100, 2),
                    'avg_passengers_per_trip' => number_format($totalTrips > 0 ? $passengers / $totalTrips : 0, 2),
                ];
            });

        return [
            'type' => 'fleet',
            'title' => self::REPORT_TYPES['fleet'],
            'columns' => [
                ['key' => 'bus', 'label' => 'Bus'],
                ['key' => 'bus_status', 'label' => 'Bus Status'],
                ['key' => 'capacity', 'label' => 'Capacity'],
                ['key' => 'total_trips', 'label' => 'Total Trips'],
                ['key' => 'completed_trips', 'label' => 'Completed Trips'],
                ['key' => 'cancelled_trips', 'label' => 'Cancelled Trips'],
                ['key' => 'active_days', 'label' => 'Active Days'],
                ['key' => 'utilization_percent', 'label' => 'Utilization %'],
                ['key' => 'avg_passengers_per_trip', 'label' => 'Avg Passengers/Trip'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Buses', 'value' => $rows->count()],
                ['label' => 'Total Trips', 'value' => $rows->sum('total_trips')],
                ['label' => 'Completed Trips', 'value' => $rows->sum('completed_trips')],
            ],
        ];
    }

    private function buildAttendanceReport(array $filters): array
    {
        $rows = DB::table('drivers')
            ->join('users', 'users.id', '=', 'drivers.user_id')
            ->join('assignments', 'assignments.driver_id', '=', 'drivers.id')
            ->join('trips', 'trips.assignment_id', '=', 'assignments.id')
            ->leftJoin('checkins', function ($join) {
                $join->on('checkins.trip_id', '=', 'trips.id')
                    ->where('checkins.scan_type', '=', 'checkin')
                    ->whereNull('checkins.voided_at');
            })
            ->when($filters['company_id'] > 0, fn ($query) => $query->where('assignments.company_id', $filters['company_id']))
            ->when($filters['route_id'] > 0, fn ($query) => $query->where('assignments.route_id', $filters['route_id']))
            ->when($filters['bus_id'] > 0, fn ($query) => $query->where('assignments.bus_id', $filters['bus_id']))
            ->when($filters['driver_id'] > 0, fn ($query) => $query->where('drivers.id', $filters['driver_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('trips.status', $filters['status']))
            ->whereBetween('trips.trip_date', [
                $filters['date_from']->toDateString(),
                $filters['date_to']->toDateString(),
            ])
            ->groupBy('drivers.id', 'users.first_name', 'users.middle_name', 'users.last_name', 'drivers.status')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->selectRaw("TRIM(CONCAT(users.first_name, ' ', COALESCE(users.middle_name, ''), ' ', users.last_name)) as driver")
            ->selectRaw('drivers.status as driver_status')
            ->selectRaw('COUNT(DISTINCT trips.id) as total_trips')
            ->selectRaw("SUM(CASE WHEN trips.status = 'completed' THEN 1 ELSE 0 END) as completed_trips")
            ->selectRaw("SUM(CASE WHEN trips.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_trips")
            ->selectRaw('COUNT(checkins.id) as passenger_checkins')
            ->selectRaw('MIN(trips.actual_start_time) as first_trip_start')
            ->selectRaw('MAX(trips.actual_end_time) as last_trip_end')
            ->get()
            ->map(function ($row) {
                return [
                    'driver' => $row->driver ?: 'N/A',
                    'driver_status' => ucfirst((string) $row->driver_status),
                    'total_trips' => (int) $row->total_trips,
                    'completed_trips' => (int) $row->completed_trips,
                    'cancelled_trips' => (int) $row->cancelled_trips,
                    'passenger_checkins' => (int) $row->passenger_checkins,
                    'first_trip_start' => $row->first_trip_start ? Carbon::parse($row->first_trip_start)->format('Y-m-d H:i') : 'N/A',
                    'last_trip_end' => $row->last_trip_end ? Carbon::parse($row->last_trip_end)->format('Y-m-d H:i') : 'N/A',
                ];
            });

        return [
            'type' => 'attendance',
            'title' => self::REPORT_TYPES['attendance'],
            'columns' => [
                ['key' => 'driver', 'label' => 'Driver'],
                ['key' => 'driver_status', 'label' => 'Driver Status'],
                ['key' => 'total_trips', 'label' => 'Total Trips'],
                ['key' => 'completed_trips', 'label' => 'Completed Trips'],
                ['key' => 'cancelled_trips', 'label' => 'Cancelled Trips'],
                ['key' => 'passenger_checkins', 'label' => 'Passenger Check-ins'],
                ['key' => 'first_trip_start', 'label' => 'First Trip Start'],
                ['key' => 'last_trip_end', 'label' => 'Last Trip End'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Drivers', 'value' => $rows->count()],
                ['label' => 'Total Trips', 'value' => $rows->sum('total_trips')],
                ['label' => 'Completed Trips', 'value' => $rows->sum('completed_trips')],
            ],
        ];
    }

    private function buildTripDetailReport(array $filters): array
    {
        $tripId = (int) ($filters['trip_id'] ?? 0);
        $trip = null;

        if ($tripId > 0) {
            $trip = Trip::query()
                ->whereKey($tripId)
                ->when($filters['company_id'] > 0, fn (Builder $query) => $query->whereHas('assignment', fn (Builder $q) => $q->where('company_id', $filters['company_id'])))
                ->first();
        }

        if (!$trip) {
            return [
                'type' => 'trip_detail',
                'title' => self::REPORT_TYPES['trip_detail'],
                'columns' => [
                    ['key' => 'message', 'label' => 'Details'],
                ],
                'rows' => [],
                'summary' => [
                    ['label' => 'Selected Trip', 'value' => 'None'],
                    ['label' => 'Expected Employees', 'value' => 0],
                    ['label' => 'Scan Records', 'value' => 0],
                ],
                'trip_report' => null,
            ];
        }

        $tripReport = app(TripReportService::class)->buildReport($trip, (int) $filters['company_id']);
        $employeeRows = collect($tripReport['tables']['employees']);
        $scanRows = collect($tripReport['tables']['scans']);

        return [
            'type' => 'trip_detail',
            'title' => self::REPORT_TYPES['trip_detail'],
            'columns' => [
                ['key' => 'employee', 'label' => 'Employee'],
                ['key' => 'employee_code', 'label' => 'Code'],
                ['key' => 'pickup_stop', 'label' => 'Pickup Stop'],
                ['key' => 'expected_pickup', 'label' => 'Expected Pickup'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'checkin_time', 'label' => 'Check-in'],
                ['key' => 'checkout_time', 'label' => 'Check-out'],
            ],
            'rows' => $employeeRows,
            'summary' => [
                ['label' => 'Selected Trip', 'value' => '#' . $trip->id . ' - ' . (optional($trip->assignment?->bus)->plate_number ?? 'Bus')],
                ['label' => 'Expected Employees', 'value' => $employeeRows->count()],
                ['label' => 'Scan Records', 'value' => $scanRows->count()],
                ['label' => 'Route', 'value' => optional($trip->assignment?->route)->name ?? '—'],
            ],
            'trip_report' => $tripReport,
        ];
    }

    private function exportTripDetail(array $report, string $type, bool $preview = false)
    {
        abort_unless(!empty($report['trip_report']), 404);

        $service = app(TripReportService::class);

        if (in_array($type, ['excel', 'xlsx'], true)) {
            return $service->exportExcel($report['trip_report']);
        }

        if ($type === 'csv') {
            return $service->exportExcel($report['trip_report'], ExcelWriter::CSV);
        }

        if ($type === 'pdf') {
            return $service->exportPdf($report['trip_report'], $preview);
        }

        abort(404);
    }

    private function exportExcel(array $report, string $writerType = ExcelWriter::XLSX)
    {
        $headings = array_map(fn (array $column) => $column['label'], $report['columns']);

        $rows = collect($report['rows'])
            ->map(function (array $row) use ($report) {
                $line = [];
                foreach ($report['columns'] as $column) {
                    $line[] = (string) ($row[$column['key']] ?? '');
                }
                return $line;
            })
            ->values()
            ->all();

        $extension = $writerType === ExcelWriter::CSV ? 'csv' : 'xlsx';
        $filename = Str::slug($report['title']) . '-' . now()->format('Ymd_His') . '.' . $extension;

        return Excel::download(new ReportExport($headings, $rows), $filename, $writerType);
    }

    private function exportPdf(array $report, array $filters, bool $inline = false, bool $printMode = false)
    {
        $columns = $report['columns'];
        $columnCount = max(1, count($columns));
        $orientation = $columnCount > 7 ? 'L' : 'P';
        $pageWidth = $orientation === 'L' ? 297 : 210;
        $contentWidth = $pageWidth - 20;
        $company = $this->resolveReportCompany($filters);
        $companyName = $company?->name ?? config('app.name', 'HM Bus Company');
        $companyAddress = $company?->address ?: 'Transport Operations and Administration';
        $logoPath = $this->resolveCompanyLogoPath($company);

        // Fresh FPDF document from the service container.
        $pdf = app('fpdf');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage($orientation, 'A4');
        $pdf->SetTitle($this->pdfText($report['title']));
        $pdf->SetAuthor($this->pdfText(config('app.name', 'HM Bus Company')));

        $companyLabel = 'All Companies';
        if ((int) ($filters['company_id'] ?? 0) > 0) {
            $companyLabel = $companyName;
        }

        // Header with logo and formal heading.
        $startY = 10;
        $textX = 10;
        $logoBottomY = $startY;
        if ($logoPath !== null) {
            $logoWidth = 24;
            $pdf->Image($logoPath, 10, $startY, $logoWidth);
            $textX = 10 + $logoWidth + 4;
            $logoBottomY = $startY + 24;
        }

        $pdf->SetXY($textX, $startY);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 6, $this->pdfText($companyName), 0, 1, 'L');

        $pdf->SetX($textX);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->pdfText($companyAddress), 0, 1, 'L');

        $pdf->SetX($textX);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, $this->pdfText('Management Report'), 0, 1, 'L');

        $pdf->SetX($textX);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, $this->pdfText($report['title']), 0, 1, 'L');

        $headerEndY = max($logoBottomY, $pdf->GetY()) + 2;
        $pdf->SetY($headerEndY);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(10, $headerEndY, $pageWidth - 10, $headerEndY);
        $pdf->Ln(2);

        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFillColor(245, 247, 250);
        $pdf->SetFont('Arial', '', 9);
        $dateLine = sprintf(
            'Date range: %s to %s',
            $filters['date_from']->toDateString(),
            $filters['date_to']->toDateString()
        );
        $pdf->Cell($contentWidth / 2, 7, $this->pdfText($dateLine), 1, 0, 'L', true);
        $pdf->Cell($contentWidth / 2, 7, $this->pdfText('Generated: ' . now()->format('Y-m-d H:i:s')), 1, 1, 'L', true);
        $pdf->Ln(3);

        // Summary strip
        $summaryWidth = $contentWidth / max(1, min(3, count($report['summary'])));
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(231, 239, 255);
        foreach ($report['summary'] as $index => $item) {
            if ($index > 0 && $index % 3 === 0) {
                $pdf->Ln();
            }
            $line = sprintf('%s: %s', $item['label'], $item['value']);
            $pdf->Cell($summaryWidth, 7, $this->pdfText(Str::limit($line, 40, '')), 1, 0, 'L', true);
        }
        $pdf->Ln(10);

        $columnWidth = max(16, min(40, $contentWidth / $columnCount));
        $rowHeight = 7;

        $this->drawPdfTableHeader($pdf, $columns, $columnWidth, $rowHeight);

        $pdf->SetFont('Arial', '', 8);
        $alternate = false;
        $maxY = $orientation === 'L' ? 200 : 270;
        foreach ($report['rows'] as $row) {
            if ($pdf->GetY() + $rowHeight > $maxY) {
                $pdf->AddPage($orientation, 'A4');
                $this->drawPdfTableHeader($pdf, $columns, $columnWidth, $rowHeight);
            }

            $pdf->SetFillColor($alternate ? 250 : 255, $alternate ? 252 : 255, $alternate ? 255 : 255);
            foreach ($columns as $column) {
                $value = (string) ($row[$column['key']] ?? '');
                $pdf->Cell($columnWidth, $rowHeight, $this->pdfText(Str::limit($value, 30, '')), 1, 0, 'L', true);
            }
            $pdf->Ln();
            $alternate = !$alternate;
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 5, $this->pdfText('Prepared by: ' . config('app.name', 'HM Bus Company')), 0, 1, 'L');

        $filename = Str::slug($report['title']) . '-' . now()->format('Ymd_His') . '.pdf';
        $content = $pdf->Output('S');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline || $printMode ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
        ]);
    }

    private function drawPdfTableHeader($pdf, array $columns, float $columnWidth, float $rowHeight): void
    {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(52, 73, 94);

        foreach ($columns as $column) {
            $pdf->Cell($columnWidth, $rowHeight, $this->pdfText(Str::limit($column['label'], 24, '')), 1, 0, 'C', true);
        }

        $pdf->Ln();
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFont('Arial', '', 8);
    }

    private function resolveReportCompany(array $filters): ?Company
    {
        $companyId = (int) ($filters['company_id'] ?? 0);

        if ($companyId <= 0) {
            $authCompanyId = (int) (auth()->user()?->company_id ?? 0);
            $companyId = $authCompanyId > 0 ? $authCompanyId : 0;
        }

        return $companyId > 0 ? Company::find($companyId) : null;
    }

    private function resolveCompanyLogoPath(?Company $company): ?string
    {
        if ($company && !empty($company->logo)) {
            $storedLogo = storage_path('app/public/' . ltrim((string) $company->logo, '/'));
            if (is_file($storedLogo)) {
                return $storedLogo;
            }
        }

        $fallbacks = [
            public_path('images/logo-header.png'),
            public_path('images/sidebar-logo.png'),
            public_path('favicon.ico'),
        ];

        foreach ($fallbacks as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function pdfText(string $value): string
    {
        $normalized = trim(strip_tags($value));
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $normalized);

        return $encoded !== false ? $encoded : preg_replace('/[^\\x20-\\x7E]/', '', $normalized);
    }
}
