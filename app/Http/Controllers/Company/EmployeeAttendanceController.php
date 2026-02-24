<?php
namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Employee;

class EmployeeAttendanceController extends Controller
{
    public function index(Employee $employee)
    {
        $this->authorizeCompany($employee);

        return view('company.employees.attendance', compact('employee'));
    }

    protected function authorizeCompany(Employee $employee)
    {
        abort_if(
            $employee->company_id !== auth()->user()->company_id,
            403
        );
    }
}
