<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeQrcode;
use Illuminate\Support\Str;

class EmployeeQrController extends Controller
{
   public function show(Employee $employee)
{
    $employee->load(['user', 'company', 'qrcode']);

    $qr = $employee->qrcode;

    if (!$qr) {
        $qrStatus = 'none';
    } elseif (!$qr->is_active) {
        $qrStatus = 'revoked';
    } elseif ($qr->generated_at < now()->subHours(24)) {
        $qrStatus = 'expired';
    } else {
        $qrStatus = 'valid';
    }

    return view('admin.employees.qr', [
        'employee' => $employee,
        'qrStatus' => $qrStatus,
    ]);
}
    public function generate(Employee $employee)
    {
        // Deactivate old QR
        EmployeeQrcode::where('employee_id', $employee->id)
            ->update(['is_active' => false]);

        // Create new QR
        EmployeeQrcode::create([
            'employee_id' => $employee->id,
            'qr_token' => Str::uuid(),
            'is_active' => true,
        ]);

        return back()->with('success', 'QR Code generated successfully.');
    }
}
