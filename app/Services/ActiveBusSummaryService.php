<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\EmployeeQrCode;

class ActiveBusSummaryService
{
public function get()
{
    return Trip::with([
        'assignment.bus',
        'assignment.company',
        'driver',
        'checkins.employee.latestQr'
    ])
    ->where('status', 'ongoing')
    ->whereHas('assignment', function ($q) {
        $q->whereNotNull('bus_id');
    })
    ->get();
}

    public function employeesForTrip($trip)
    {
        return $trip->checkins
            ->whereNull('voided_at')
            ->groupBy('employee_id')
            ->map(fn ($c) => $c->first()->employee);
    }
    public function qrStatus(?EmployeeQrCode $qr): array
    {
        if (!$qr) {
            return ['label' => 'No QR', 'color' => 'secondary'];
        }

        if (!$qr->is_active) {
            return ['label' => 'Revoked', 'color' => 'danger'];
        }

        if ($qr->generated_at < now()->subHours(24)) {
            return ['label' => 'Expired', 'color' => 'warning'];
        }

        return ['label' => 'Valid', 'color' => 'success'];
    }

    public function scanStatus($trip, $employee): string
    {
        $types = $employee->checkins
            ->where('trip_id', $trip->id)
            ->whereNull('voided_at')
            ->pluck('scan_type');

        return match (true) {
            $types->contains('checkout') => 'Checked Out',
            $types->contains('checkin')  => 'Checked In',
            default                      => 'Not Scanned',
        };
    }
}
