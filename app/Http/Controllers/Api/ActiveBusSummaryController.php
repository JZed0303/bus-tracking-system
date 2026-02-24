<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\EmployeeQrCode;

class ActiveBusSummaryController extends Controller
{
    public function index()
    {
        $trips = Trip::with([
            'bus',
            'assignment.company',
            'driver',
            'employees.latestQr',
            'employees.checkins'
        ])
        ->where('status', 'ongoing')
        ->get();

        return response()->json([
            'generated_at' => now(),
            'active_bus_count' => $trips->count(),
            'buses' => $trips->map(fn ($trip) => $this->mapTrip($trip)),
        ]);
    }

    private function mapTrip($trip): array
    {
        return [
            'bus_id' => $trip->bus->id,
            'bus_number' => $trip->bus->bus_number,
            'company' => [
                'id' => $trip->assignment->company->id,
                'name' => $trip->assignment->company->name,
            ],
            'driver' => [
                'id' => $trip->driver->id,
                'name' => $trip->driver->full_name,
            ],
            'trip' => [
                'trip_id' => $trip->id,
                'direction' => $trip->direction,
                'status' => $trip->status,
                'started_at' => $trip->created_at,
            ],
            'employees' => $trip->employees->map(fn ($e) => [
                'employee_id' => $e->id,
                'name' => $e->full_name,
                'qr' => $this->qrStatus($e->latestQr),
                'scan_status' => $this->scanStatus($trip, $e),
            ]),
            'summary' => $this->summary($trip),
        ];
    }

    private function qrStatus(?EmployeeQrCode $qr): array
    {
        if (!$qr) {
            return [
                'is_active' => false,
                'is_valid' => false,
                'reason' => 'not_generated',
            ];
        }

        if (!$qr->is_active) {
            return [
                'is_active' => false,
                'is_valid' => false,
                'reason' => 'revoked',
            ];
        }

        if ($qr->generated_at < now()->subHours(24)) {
            return [
                'is_active' => true,
                'is_valid' => false,
                'reason' => 'expired',
            ];
        }

        return [
            'is_active' => true,
            'is_valid' => true,
            'generated_at' => $qr->generated_at,
            'expires_at' => $qr->generated_at->copy()->addHours(24),
        ];
    }

    private function scanStatus($trip, $employee): string
    {
        $checkins = $employee->checkins
            ->where('trip_id', $trip->id)
            ->pluck('scan_type');

        if ($checkins->contains('checkout')) {
            return 'checked_out';
        }

        if ($checkins->contains('checkin')) {
            return 'checked_in';
        }

        return 'not_scanned';
    }

    private function summary($trip): array
    {
        $employees = $trip->employees;

        return [
            'total_employees' => $employees->count(),
            'checked_in' => $employees->filter(
                fn ($e) => $this->scanStatus($trip, $e) === 'checked_in'
            )->count(),
            'checked_out' => $employees->filter(
                fn ($e) => $this->scanStatus($trip, $e) === 'checked_out'
            )->count(),
            'pending' => $employees->filter(
                fn ($e) => $this->scanStatus($trip, $e) === 'not_scanned'
            )->count(),
        ];
    }
}
