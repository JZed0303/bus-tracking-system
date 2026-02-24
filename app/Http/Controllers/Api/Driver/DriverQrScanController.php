<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\EmployeeQrcode;
use App\Models\Checkin;
use App\Models\Trip;
use Illuminate\Http\Request;

class DriverQrScanController extends Controller
{
    public function scan(Request $request)
    {
        $driver = auth()->user()->driver;

        if (!$driver) {
            return response()->json([
                'message' => 'Unauthorized driver'
            ], 403);
        }

        $request->validate([
            'qr_token' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Find active trip
        $trip = Trip::whereHas('assignment', function ($q) use ($driver) {
                $q->where('driver_id', $driver->id);
            })
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if (!$trip) {
            return response()->json([
                'message' => 'No active trip found'
            ], 422);
        }

        // Validate QR
        $qr = EmployeeQrcode::where('qr_token', $request->qr_token)
            ->where('is_active', true)
            ->first();

        if (!$qr) {
            return response()->json([
                'message' => 'Invalid or expired QR code'
            ], 404);
        }

        $employee = $qr->employee;

        // Validate company
        if ($employee->company_id !== $trip->assignment->company_id) {
            return response()->json([
                'message' => 'Employee does not belong to this trip'
            ], 403);
        }

        // Determine scan type
        $lastScan = Checkin::where('trip_id', $trip->id)
            ->where('employee_id', $employee->id)
            ->latest('scan_time')
            ->first();
        
        $scanType = $lastScan && $lastScan->scan_type === 'checkin'
            ? 'checkout'
            : 'checkin';

        // Prevent duplicate scans
        if ($lastScan && $lastScan->scan_type === $scanType) {
            return response()->json([
                'message' => 'Duplicate scan detected'
            ], 409);
        }

        // Save scan
        Checkin::create([
            'trip_id' => $trip->id,
            'employee_id' => $employee->id,
            'scan_type' => $scanType,
            'scan_time' => now(),
            'scan_lat' => $request->latitude,
            'scan_lng' => $request->longitude,
            'scanned_by_driver_id' => $driver->id,
        ]);

        return response()->json([
            'message' => ucfirst($scanType) . ' successful',
            'scan_type' => $scanType,
            'employee' => [
                'name' => $employee->user->full_name,
                'employee_code' => $employee->employee_code,
            ],
            'trip_id' => $trip->id,
        ]);
    }
}
