<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Checkin;
use App\Models\Trip;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function root()
    {
        return view('index');
    }

    public function index(Request $request)
    {
        if (view()->exists($request->path())) {
            return view($request->path());
        }
        return view('errors.404');
    }

    public function dashboard(Request $request)
    {
        $today = Carbon::today('Asia/Manila');
        $weekStart = $today->copy()->subDays(6)->startOfDay();
        $weekEnd = $today->copy()->endOfDay();
        $lastWeekStart = $today->copy()->subDays(13)->startOfDay();
        $lastWeekEnd = $today->copy()->subDays(7)->endOfDay();
        $chartDates = collect(CarbonPeriod::create($weekStart, '1 day', $today))
            ->map(fn ($date) => Carbon::instance($date))
            ->values();

        $totalEmployees = \App\Models\Employee::query()->active()->count();
        $inactiveEmployees = \App\Models\Employee::query()
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'active');
            })
            ->count();
        $activeRoutes = \App\Models\TransportRoute::query()->active()->count();

        $todayTripsQuery = Trip::query()->whereDate('trip_date', $today->toDateString());
        $todayTripsScheduled = (clone $todayTripsQuery)->where('status', 'scheduled')->count();
        $todayTripsOngoing = (clone $todayTripsQuery)->where('status', 'ongoing')->count();
        $todayTripsCompleted = (clone $todayTripsQuery)->where('status', 'completed')->count();
        $todayTripsCancelled = (clone $todayTripsQuery)->where('status', 'cancelled')->count();
        $todayTripsTotal = $todayTripsScheduled + $todayTripsOngoing + $todayTripsCompleted + $todayTripsCancelled;

        $employeesTransportedToday = Checkin::query()
            ->whereDate('scan_time', $today->toDateString())
            ->where('scan_type', 'checkin')
            ->distinct('employee_id')
            ->count('employee_id');
        $transportCoveragePercent = $totalEmployees > 0
            ? round(($employeesTransportedToday / $totalEmployees) * 100, 1)
            : 0.0;

        $activeAssignments = Assignment::query()
            ->active()
            ->with('bus:id,plate_number,capacity,status,last_seen_at')
            ->get()
            ->unique('bus_id')
            ->values();
        $assignedBusesCount = $activeAssignments->pluck('bus_id')->filter()->unique()->count();
        $onlineBusesCount = $activeAssignments
            ->filter(fn ($assignment) => (bool) ($assignment->bus?->is_online))
            ->count();

        $computeOnTime = function (Carbon $start, Carbon $end): array {
            $completed = Trip::query()
                ->where('status', 'completed')
                ->whereBetween('trip_date', [$start->toDateString(), $end->toDateString()])
                ->whereNotNull('scheduled_end_time')
                ->whereNotNull('actual_end_time')
                ->get(['scheduled_end_time', 'actual_end_time']);

            $onTime = $completed->filter(function ($trip) {
                return Carbon::parse($trip->actual_end_time)->lessThanOrEqualTo(Carbon::parse($trip->scheduled_end_time));
            })->count();

            $delayed = max(0, $completed->count() - $onTime);
            $onTimePercent = $completed->count() > 0
                ? round(($onTime / $completed->count()) * 100, 1)
                : 0.0;

            return [
                'completed' => $completed->count(),
                'on_time' => $onTime,
                'delayed' => $delayed,
                'percent' => $onTimePercent,
            ];
        };

        $thisWeek = $computeOnTime($weekStart, $weekEnd);
        $lastWeek = $computeOnTime($lastWeekStart, $lastWeekEnd);
        $onTimeDelta = round($thisWeek['percent'] - $lastWeek['percent'], 1);

        $dailyTripsRaw = Trip::query()
            ->selectRaw('trip_date, COUNT(*) as total')
            ->whereBetween('trip_date', [$weekStart->toDateString(), $today->toDateString()])
            ->groupBy('trip_date')
            ->pluck('total', 'trip_date');
        $dailyTripsSeries = $chartDates->map(fn (Carbon $date) => (int) ($dailyTripsRaw[$date->toDateString()] ?? 0))->values();

        $dailyEmployeesRaw = Checkin::query()
            ->selectRaw('DATE(scan_time) as scan_date, COUNT(DISTINCT employee_id) as total')
            ->whereBetween('scan_time', [$weekStart, $weekEnd])
            ->where('scan_type', 'checkin')
            ->groupBy('scan_date')
            ->pluck('total', 'scan_date');
        $dailyEmployeesSeries = $chartDates->map(fn (Carbon $date) => (int) ($dailyEmployeesRaw[$date->toDateString()] ?? 0))->values();

        $routeUtilization = Trip::query()
            ->whereBetween('trip_date', [$weekStart->toDateString(), $today->toDateString()])
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
            ->take(5);

        return view('dashboard.index', [
            'totalEmployees' => $totalEmployees,
            'inactiveEmployees' => $inactiveEmployees,
            'employeesTransportedToday' => $employeesTransportedToday,
            'transportCoveragePercent' => $transportCoveragePercent,
            'assignedBusesCount' => $assignedBusesCount,
            'onlineBusesCount' => $onlineBusesCount,
            'activeRoutes' => $activeRoutes,
            'todayTripsScheduled' => $todayTripsScheduled,
            'todayTripsOngoing' => $todayTripsOngoing,
            'todayTripsCompleted' => $todayTripsCompleted,
            'todayTripsCancelled' => $todayTripsCancelled,
            'todayTripsTotal' => $todayTripsTotal,
            'onTimePerformance' => $thisWeek['percent'],
            'onTimeDelta' => $onTimeDelta,
            'onTimeToday' => $thisWeek['on_time'],
            'delayedToday' => $thisWeek['delayed'],
            'chartLabels' => $chartDates->map(fn (Carbon $date) => $date->format('D'))->values(),
            'dailyTripsSeries' => $dailyTripsSeries,
            'dailyEmployeesSeries' => $dailyEmployeesSeries,
            'routeUtilizationLabels' => $routeUtilization->keys()->values(),
            'routeUtilizationSeries' => $routeUtilization->values()->values(),
        ]);
    }
}
