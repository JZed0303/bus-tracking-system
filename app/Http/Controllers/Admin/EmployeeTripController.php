<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Employee;
use App\Models\Trip;
use App\Models\TransportRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeeTripController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $routeOptions = TransportRoute::query()
            ->whereHas('assignments.trips.checkins', function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = Trip::query()
            ->select('trips.*')
            ->selectSub(
                Checkin::query()
                    ->selectRaw('MIN(scan_time)')
                    ->whereColumn('checkins.trip_id', 'trips.id')
                    ->where('employee_id', $employee->id)
                    ->where('scan_type', 'checkin'),
                'checkin_time'
            )
            ->selectSub(
                Checkin::query()
                    ->selectRaw('MAX(scan_time)')
                    ->whereColumn('checkins.trip_id', 'trips.id')
                    ->where('employee_id', $employee->id)
                    ->where('scan_type', 'checkout'),
                'checkout_time'
            )
            ->selectSub(
                Checkin::query()
                    ->selectRaw('MIN(scan_time)')
                    ->whereColumn('checkins.trip_id', 'trips.id')
                    ->where('employee_id', $employee->id),
                'first_scan_time'
            )
            ->with([
                'assignment.route',
                'assignment.driver.user',
                'assignment.bus',
            ])
            ->whereHas('checkins', function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            });

        if ($request->filled('route_id')) {
            $query->whereHas('assignment', function ($q) use ($request) {
                $q->where('route_id', $request->integer('route_id'));
            });
        }

        if ($request->filled('from')) {
            $fromDateTime = Carbon::parse($request->from, 'Asia/Manila')->format('Y-m-d H:i:s');
            $query->whereHas('checkins', function ($q) use ($employee, $fromDateTime) {
                $q->where('employee_id', $employee->id)
                    ->where('scan_time', '>=', $fromDateTime);
            });
        }

        if ($request->filled('to')) {
            $toDateTime = Carbon::parse($request->to, 'Asia/Manila')->format('Y-m-d H:i:s');
            $query->whereHas('checkins', function ($q) use ($employee, $toDateTime) {
                $q->where('employee_id', $employee->id)
                    ->where('scan_time', '<=', $toDateTime);
            });
        }

        $query->orderByDesc('first_scan_time')
            ->orderByDesc('trip_date');

        $trips = $query->paginate(15);

        return view('admin.employees.trips', compact(
            'employee',
            'trips',
            'routeOptions'
        ));
    }
}
