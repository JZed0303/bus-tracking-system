<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeTripController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $query = $employee->checkins()
            ->with([
                'trip.assignment.route',
                'trip.assignment.driver.user',
                'trip.assignment.bus',
            ])
            ->selectRaw('
                trip_id,
                MIN(CASE WHEN scan_type = \'checkin\' THEN scan_time END) as checkin_time,
                MAX(CASE WHEN scan_type = \'checkout\' THEN scan_time END) as checkout_time
            ')
            ->groupBy('trip_id')
            ->orderByRaw('MIN(scan_time) DESC');

        if ($request->filled('from')) {
            $query->whereDate('scan_time', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('scan_time', '<=', $request->to);
        }

        $trips = $query->paginate(15);

        return view('admin.employees.trips', compact(
            'employee',
            'trips'
        ));
    }
}
