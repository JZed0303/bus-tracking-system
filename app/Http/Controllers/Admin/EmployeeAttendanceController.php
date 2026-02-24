<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $query = $employee->checkins()
            ->with([
                'trip.assignment.route',
                'trip.assignment.driver.user',
            ])
            ->orderBy('scan_time', 'desc');

        // Date filters
        if ($request->filled('from')) {
            $query->whereDate('scan_time', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('scan_time', '<=', $request->to);
        }

        $checkins = $query->paginate(20);

        return view('admin.employees.attendance', compact(
            'employee',
            'checkins'
        ));
    }
}
