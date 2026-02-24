<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Models\Company;
use App\Models\Employee;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\Checkin;
use App\Models\Assignment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $selectableCompanies = collect();
        $selectedCompanyId = null;

        if ($isSuperAdmin) {
            $selectableCompanies = Company::query()
                ->orderBy('name')
                ->get(['id', 'name', 'status']);

            $requestedCompanyId = (int) $request->integer('company_id', 0);
            $sessionCompanyId = (int) $request->session()->get('company_dashboard_company_id', 0);
            $selectedCompanyId = $requestedCompanyId > 0 ? $requestedCompanyId : $sessionCompanyId;

            if ($selectedCompanyId > 0 && !$selectableCompanies->contains('id', $selectedCompanyId)) {
                $selectedCompanyId = null;
                $request->session()->forget('company_dashboard_company_id');
            }

            if (!$selectedCompanyId) {
                $selectedCompanyId = (int) optional($selectableCompanies->first())->id;
            }

            if ($selectedCompanyId) {
                $request->session()->put('company_dashboard_company_id', $selectedCompanyId);
            }

            $companyId = (int) $selectedCompanyId;
            $companyName = optional($selectableCompanies->firstWhere('id', $companyId))->name ?? 'Company';
        } else {
            $companyId = (int) $user->company_id;
            $selectedCompanyId = $companyId;
            $companyName = $user->company?->name ?? 'Company';
        }

        $today = Carbon::today('Asia/Manila');
        $rangeStart = $today->copy()->subDays(13)->startOfDay();
        $rangeEnd = $today->copy()->endOfDay();
        $dateRange = collect(CarbonPeriod::create($rangeStart, '1 day', $today))
            ->map(fn ($date) => Carbon::instance($date));

        $buildDashboardData = function () use ($companyId, $today, $rangeStart, $rangeEnd, $dateRange): array {
            if ($companyId <= 0) {
                $labels = $dateRange->map(fn (Carbon $date) => $date->format('M d'))->values();
                $zeroSeries = $dateRange->map(fn () => 0)->values();
                $emptyBuses = collect();

                return [
                    'totalEmployees' => 0,
                    'inactiveEmployees' => 0,
                    'activeRoutes' => 0,
                    'todayTrips' => 0,
                    'todayTripsOngoing' => 0,
                    'todayTripsCompleted' => 0,
                    'todayTripsCancelled' => 0,
                    'employeesTransportedToday' => 0,
                    'transportCoveragePercent' => 0.0,
                    'tripCompletionPercent' => 0.0,
                    'assignedBusesCount' => 0,
                    'onlineBusesCount' => 0,
                    'dailyTransportSummary' => $dateRange->map(fn (Carbon $date) => [
                        'date' => $date->toDateString(),
                        'total' => 0,
                    ]),
                    'buses' => $emptyBuses,
                    'companyBuses' => $emptyBuses,
                    'analytics' => [
                        'transport_trend' => [
                            'labels' => $labels,
                            'series' => $zeroSeries,
                        ],
                        'trip_status_trend' => [
                            'labels' => $labels,
                            'ongoing' => $zeroSeries,
                            'completed' => $zeroSeries,
                            'cancelled' => $zeroSeries,
                        ],
                        'department_mix' => [
                            'labels' => collect(),
                            'series' => collect(),
                        ],
                        'route_utilization' => [
                            'labels' => collect(),
                            'series' => collect(),
                        ],
                    ],
                ];
            }

            $totalEmployees = Employee::query()
                ->where('company_id', $companyId)
                ->active()
                ->count();

            $inactiveEmployees = Employee::query()
                ->where('company_id', $companyId)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhere('status', '!=', 'active');
                })
                ->count();

            $activeRoutes = TransportRoute::query()
                ->where('company_id', $companyId)
                ->active()
                ->count();

            $todayTripsCollection = Trip::query()
                ->whereHas('assignment', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereDate('trip_date', $today->toDateString())
                ->with(['assignment:id,bus_id'])
                ->withCount([
                    'checkins as checkins_count' => fn ($q) => $q->where('scan_type', 'checkin'),
                ])
                ->get();

            $todayTrips = $todayTripsCollection->count();
            $todayTripsOngoing = $todayTripsCollection->where('status', 'ongoing')->count();
            $todayTripsCompleted = $todayTripsCollection->where('status', 'completed')->count();
            $todayTripsCancelled = $todayTripsCollection->where('status', 'cancelled')->count();

            $employeesTransportedToday = Checkin::query()
                ->whereDate('scan_time', $today->toDateString())
                ->whereHas('trip.assignment', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where('scan_type', 'checkin')
                ->distinct('employee_id')
                ->count('employee_id');

            $transportCoveragePercent = $totalEmployees > 0
                ? round(($employeesTransportedToday / $totalEmployees) * 100, 1)
                : 0.0;

            $tripCompletionPercent = $todayTrips > 0
                ? round(($todayTripsCompleted / $todayTrips) * 100, 1)
                : 0.0;

            $activeAssignments = Assignment::query()
                ->where('company_id', $companyId)
                ->active()
                ->with([
                    'bus:id,plate_number,capacity,brand_model,status,last_seen_at',
                    'driver.user:id,first_name,middle_name,last_name',
                    'route:id,name',
                ])
                ->orderByDesc('effective_from')
                ->get()
                ->unique('bus_id')
                ->values();

            $assignedBusesCount = $activeAssignments->pluck('bus_id')->filter()->unique()->count();
            $onlineBusesCount = $activeAssignments
                ->filter(fn ($assignment) => (bool) ($assignment->bus?->is_online))
                ->count();

            $tripStatsByBus = $todayTripsCollection
                ->groupBy(fn ($trip) => (int) ($trip->assignment?->bus_id ?? 0))
                ->map(function (Collection $trips): array {
                    return [
                        'today_trips' => $trips->count(),
                        'ongoing' => $trips->where('status', 'ongoing')->count(),
                        'completed' => $trips->where('status', 'completed')->count(),
                        'employees_transported' => (int) $trips->sum('checkins_count'),
                    ];
                })
                ->all();

            $companyBuses = $activeAssignments->map(function ($assignment) use ($tripStatsByBus) {
                $busId = (int) $assignment->bus_id;
                $stats = $tripStatsByBus[$busId] ?? [
                    'today_trips' => 0,
                    'ongoing' => 0,
                    'completed' => 0,
                    'employees_transported' => 0,
                ];

                return [
                    'bus_id' => $busId,
                    'plate_number' => $assignment->bus?->plate_number ?? 'N/A',
                    'capacity' => (int) ($assignment->bus?->capacity ?? 0),
                    'model' => $assignment->bus?->brand_model ?? 'N/A',
                    'lifecycle_status' => $assignment->bus?->status ?? 'unknown',
                    'presence' => $assignment->bus?->presence ?? 'offline',
                    'driver_name' => $assignment->driver?->user?->full_name ?? 'Unassigned',
                    'route_name' => $assignment->route?->name ?? 'Unassigned',
                    'today_trips' => (int) $stats['today_trips'],
                    'ongoing_trips' => (int) $stats['ongoing'],
                    'completed_trips' => (int) $stats['completed'],
                    'employees_transported' => (int) $stats['employees_transported'],
                ];
            })->values();

            $dailyTransportRaw = Checkin::query()
                ->selectRaw('DATE(scan_time) as scan_date, COUNT(DISTINCT employee_id) as total')
                ->whereHas('trip.assignment', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where('scan_type', 'checkin')
                ->whereBetween('scan_time', [$rangeStart, $rangeEnd])
                ->groupBy('scan_date')
                ->pluck('total', 'scan_date');

            $dailyTransportSummary = $dateRange->map(function (Carbon $date) use ($dailyTransportRaw) {
                $dateKey = $date->toDateString();
                return [
                    'date' => $dateKey,
                    'total' => (int) ($dailyTransportRaw[$dateKey] ?? 0),
                ];
            });

            $tripStatusRaw = Trip::query()
                ->selectRaw('trip_date, status, COUNT(*) as total')
                ->whereHas('assignment', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('trip_date', [$rangeStart->toDateString(), $today->toDateString()])
                ->groupBy('trip_date', 'status')
                ->get();

            $tripStatusByDate = $tripStatusRaw
                ->groupBy(fn ($row) => Carbon::parse($row->trip_date)->toDateString());

            $tripStatusTrend = $dateRange->map(function (Carbon $date) use ($tripStatusByDate) {
                $bucket = collect($tripStatusByDate->get($date->toDateString(), []));
                return [
                    'date' => $date->toDateString(),
                    'ongoing' => (int) $bucket->where('status', 'ongoing')->sum('total'),
                    'completed' => (int) $bucket->where('status', 'completed')->sum('total'),
                    'cancelled' => (int) $bucket->where('status', 'cancelled')->sum('total'),
                ];
            });

            $departmentBreakdown = Employee::query()
                ->where('company_id', $companyId)
                ->active()
                ->get(['department'])
                ->map(function ($employee) {
                    $department = trim((string) $employee->department);
                    return $department !== '' ? $department : 'Unassigned';
                })
                ->countBy()
                ->sortDesc()
                ->take(6);

            $routeUtilization = Trip::query()
                ->whereHas('assignment', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('trip_date', [$rangeStart->toDateString(), $today->toDateString()])
                ->with(['assignment.route:id,name'])
                ->withCount([
                    'checkins as checkins_count' => fn ($q) => $q->where('scan_type', 'checkin'),
                ])
                ->get()
                ->groupBy(function ($trip) {
                    $routeName = trim((string) ($trip->assignment?->route?->name ?? ''));
                    return $routeName !== '' ? $routeName : 'Unassigned';
                })
                ->map(fn (Collection $trips) => (int) $trips->sum('checkins_count'))
                ->sortDesc()
                ->take(6);

            $analytics = [
                'transport_trend' => [
                    'labels' => $dateRange->map(fn (Carbon $date) => $date->format('M d'))->values(),
                    'series' => $dailyTransportSummary->pluck('total')->values(),
                ],
                'trip_status_trend' => [
                    'labels' => $dateRange->map(fn (Carbon $date) => $date->format('M d'))->values(),
                    'ongoing' => $tripStatusTrend->pluck('ongoing')->values(),
                    'completed' => $tripStatusTrend->pluck('completed')->values(),
                    'cancelled' => $tripStatusTrend->pluck('cancelled')->values(),
                ],
                'department_mix' => [
                    'labels' => $departmentBreakdown->keys()->values(),
                    'series' => $departmentBreakdown->values()->map(fn ($v) => (int) $v)->values(),
                ],
                'route_utilization' => [
                    'labels' => $routeUtilization->keys()->values(),
                    'series' => $routeUtilization->values()->map(fn ($v) => (int) $v)->values(),
                ],
            ];

            return [
                'totalEmployees' => $totalEmployees,
                'inactiveEmployees' => $inactiveEmployees,
                'activeRoutes' => $activeRoutes,
                'todayTrips' => $todayTrips,
                'todayTripsOngoing' => $todayTripsOngoing,
                'todayTripsCompleted' => $todayTripsCompleted,
                'todayTripsCancelled' => $todayTripsCancelled,
                'employeesTransportedToday' => $employeesTransportedToday,
                'transportCoveragePercent' => $transportCoveragePercent,
                'tripCompletionPercent' => $tripCompletionPercent,
                'assignedBusesCount' => $assignedBusesCount,
                'onlineBusesCount' => $onlineBusesCount,
                'dailyTransportSummary' => $dailyTransportSummary,
                'buses' => $companyBuses,
                'companyBuses' => $companyBuses,
                'analytics' => $analytics,
            ];
        };

        $analyticsCacheEnabled = (bool) config('transport.dashboard.analytics_cache_enabled', true);
        $analyticsCacheTtlSeconds = max(15, (int) config('transport.dashboard.analytics_cache_ttl_seconds', 60));

        $dashboardData = $analyticsCacheEnabled
            ? Cache::remember(
                "company-dashboard:v1:{$companyId}:{$today->toDateString()}",
                now()->addSeconds($analyticsCacheTtlSeconds),
                $buildDashboardData
            )
            : $buildDashboardData();

        return view('company.dashboard.index', array_merge([
            'companyName'                => $companyName,
            'isSuperAdmin'               => $isSuperAdmin,
            'selectedCompanyId'          => $selectedCompanyId,
            'selectableCompanies'        => $selectableCompanies,
        ], $dashboardData));
    }
}
