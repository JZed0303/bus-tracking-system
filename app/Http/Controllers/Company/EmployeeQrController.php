<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Employee;

class EmployeeQrController extends Controller
{
    public function show(Employee $employee)
    {
        $this->authorizeCompany($employee);

        return view('company.employees.qr', compact('employee'));
    }

    public function generate(Employee $employee)
    {
        $this->authorizeCompany($employee);

        // generate QR logic
        return back()->with('success', 'QR code generated.');
    }

    protected function authorizeCompany(Employee $employee)
    {
        abort_if(
            $employee->company_id !== auth()->user()->company_id,
            403
        );
    }
}
