<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\EmployeeQrCode;
use App\Models\Checkin;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
class QrCheckinService
{
    /**
     * true  => employee must be assigned to THIS trip (Trip->employees pivot)
     * false => employee can scan on any trip, but cannot have an open session elsewhere
     */
    private bool $requireSpecificTrip = false;

    /**
     * Allowed radius (meters) between scanner GPS and the bus latest trip GPS.
     */
    private int $allowedRadiusMeters = 150;

    /**
     * Backward-compatible entry point (still accepts token),
     * but internally uses getValidQr() once.
     */
    public function scan(
        Trip $trip,
        string $qrToken,
        ?float $lat = null,
        ?float $lng = null
    ): Checkin {
        $qr = $this->getValidQr($qrToken);

        return $this->scanWithQr(
            qr: $qr,
            trip: $trip,
            lat: $lat,
            lng: $lng
        );
    }

    /**
     * ✅ Fast path: QR already loaded, do not hit DB again for QR.
     */
  public function scanWithQr(
    EmployeeQrCode $qr,
    Trip $trip,
    ?float $lat = null,
    ?float $lng = null
): Checkin {

    $this->assertTripHasValidAssignment($trip);
    $this->assertTripNotEnded($trip);
    $this->assertLocationProvided($lat, $lng);

    $employee = $qr->employee;
    $this->assertEmployeeBelongsToTripCompany($employee->company_id, $trip);

    if ($this->requireSpecificTrip) {
        $this->assertEmployeeIsOnThisTrip($trip, $employee->id);
    } else {
        $this->assertEmployeeNotOpenOnOtherTrip($trip->id, $employee->id);
    }

    $this->assertWithinAllowedArea($trip, (float) $lat, (float) $lng);

    return DB::transaction(function () use ($trip, $employee, $lat, $lng) {

        // ✅ lock the latest scan row (prevents double-checkout)
        $last = Checkin::query()
            ->where('trip_id', $trip->id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$last) {
            $scanType = 'checkin';
        } elseif ($last->scan_type === 'checkin') {
            $scanType = 'checkout';
        } else {
            throw ValidationException::withMessages([
                'scan' => ['Employee already checked out.'],
            ]);
        }

        return Checkin::create([
            'trip_id'                => $trip->id,
            'employee_id'            => $employee->id,
            'scan_type'              => $scanType,
            'scan_time'              => Carbon::now('Asia/Manila'),
            'scan_lat'               => $lat,
            'scan_lng'               => $lng,
            'scanned_by_employee_id' => $employee->id,
        ]);
    });
}

    /**
     * ✅ FAST: Employee-first check using EXISTS/NOT EXISTS (index-friendly)
     */
    public function employeeActiveTripIdFromEmployee(int $employeeId): ?int
    {
        return Checkin::query()
            ->where('employee_id', $employeeId)
            ->where('scan_type', 'checkin')
            ->whereNotExists(function ($q) use ($employeeId) {
                $q->selectRaw('1')
                  ->from('checkins as c2')
                  ->whereColumn('c2.trip_id', 'checkins.trip_id')
                  ->where('c2.employee_id', $employeeId)
                  ->where('c2.scan_type', 'checkout');
            })
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                  ->from('trips')
                  ->whereColumn('trips.id', 'checkins.trip_id')
                  ->where('trips.status', 'ongoing'); // keep if you need this constraint
            })
            ->orderByDesc('checkins.id')
            ->value('trip_id');
    }

    /**
     * Kept for compatibility with your controller comments/usage.
     * Now delegates to employeeActiveTripIdFromEmployee().
     */
    public function employeeActiveTripIdFromQr(string $qrToken): ?int
    {
        $qr = $this->getValidQr($qrToken);
        return $this->employeeActiveTripIdFromEmployee((int) $qr->employee_id);
    }

    public function offlineScan(
        Trip $trip,
        string $qrToken,
        string $scanTime,
        ?float $lat,
        ?float $lng,
        string $clientScanId
    ): Checkin {
        // ✅ Validate ONLY active assignment
        $this->assertTripHasValidAssignment($trip);
        $this->assertTripNotEnded($trip);

        $this->assertLocationProvided($lat, $lng);

        $qr = $this->getValidQr($qrToken);
        $employee = $qr->employee;

        $this->assertEmployeeBelongsToTripCompany($employee->company_id, $trip);

        if ($this->requireSpecificTrip) {
            $this->assertEmployeeIsOnThisTrip($trip, $employee->id);
        } else {
            $this->assertEmployeeNotOpenOnOtherTrip($trip->id, $employee->id);
        }

        $this->assertWithinAllowedArea($trip, (float) $lat, (float) $lng);

        $scanType = $this->resolveScanType($trip->id, $employee->id);

        return Checkin::create([
            'trip_id'                => $trip->id,
            'employee_id'            => $employee->id,
            'scan_type'              => $scanType,
            'scan_time'              => Carbon::parse($scanTime)->timezone('Asia/Manila'),
            'scan_lat'               => $lat,
            'scan_lng'               => $lng,

            // ✅ SCANNER = EMPLOYEE
            'scanned_by_employee_id' => $employee->id,

            'client_scan_id'         => $clientScanId,
            'is_offline'             => true,
            'synced_at'              => now(),
        ]);
    }

    /* =========================
       ASSERTIONS
       ========================= */

    private function assertTripNotEnded(Trip $trip): void
    {
        if (in_array($trip->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'trip' => ['Trip is already ended.'],
            ]);
        }
    }

    private function assertTripHasValidAssignment(Trip $trip): void
    {
        // IMPORTANT: if $trip->assignment is not eager loaded,
        // this will trigger a query. That’s OK, but you can eager load it
        // via bus->activeTrip relationship for even more speed.
        if (!$trip->assignment || !$trip->assignment->isActive()) {
            throw ValidationException::withMessages([
                'assignment' => ['Trip assignment is not active.'],
            ]);
        }

        if (!$trip->assignment->bus || !$trip->assignment->route) {
            throw ValidationException::withMessages([
                'route' => ['Trip has no valid bus or route assignment.'],
            ]);
        }
    }

    /**
     * ✅ PUBLIC so controller can fetch QR once
     * ✅ eager-load employee to avoid lazy-load queries later
     */
    public function getValidQr(string $qrToken): EmployeeQrCode
    {
        $qrToken = trim($qrToken);

        $qr = EmployeeQrCode::query()
            ->with(['employee']) // add employee.user / employee.company if you want
            ->where('qr_token', $qrToken)
            ->where('is_active', true)
            ->first();

        if (!$qr) {
            throw ValidationException::withMessages([
                'qr_token' => ['Invalid QR code.'],
            ]);
        }

        return $qr;
    }

    private function assertEmployeeBelongsToTripCompany(int $employeeCompanyId, Trip $trip): void
    {
        $assignment = $trip->assignment;

        if (!$assignment || $employeeCompanyId !== (int) $assignment->company_id) {
            throw ValidationException::withMessages([
                'company' => ['Employee does not belong to the assigned company.'],
            ]);
        }
    }

    private function assertEmployeeIsOnThisTrip(Trip $trip, int $employeeId): void
    {
        $allowed = $trip->employees()
            ->where('employees.id', $employeeId)
            ->exists();

        if (!$allowed) {
            throw ValidationException::withMessages([
                'employee' => ['Employee is not assigned to this trip/bus.'],
            ]);
        }
    }

    /**
     * ✅ FAST: EXISTS/NOT EXISTS instead of GROUP/HAVING
     */
    private function assertEmployeeNotOpenOnOtherTrip(int $currentTripId, int $employeeId): void
    {
        $hasOpenElsewhere = Checkin::query()
            ->where('employee_id', $employeeId)
            ->where('trip_id', '!=', $currentTripId)
            ->where('scan_type', 'checkin')
            ->whereNotExists(function ($q) use ($employeeId) {
                $q->selectRaw('1')
                  ->from('checkins as c2')
                  ->whereColumn('c2.trip_id', 'checkins.trip_id')
                  ->where('c2.employee_id', $employeeId)
                  ->where('c2.scan_type', 'checkout');
            })
            ->exists();

        if ($hasOpenElsewhere) {
            throw ValidationException::withMessages([
                'employee' => ['Employee is already checked-in on another trip/bus.'],
            ]);
        }
    }

    private function assertLocationProvided(?float $lat, ?float $lng): void
    {
        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages([
                'location' => ['Latitude and longitude are required.'],
            ]);
        }
    }

    private function assertWithinAllowedArea(Trip $trip, float $lat, float $lng): void
    {
        // This may do 1 query if not eager-loaded.
        // For max speed, eager-load latestLocation on the activeTrip relationship.
        $latest = $trip->latestLocation()->first();

        if (!$latest) {
            // No GPS from bus yet; do not block.
            return;
        }

        $distance = $this->distanceMeters(
            $lat,
            $lng,
            (float) $latest->latitude,
            (float) $latest->longitude
        );

        if ($distance > $this->allowedRadiusMeters) {
            throw ValidationException::withMessages([
                'location' => ["Too far from bus ({$distance}m)."],
            ]);
        }
    }

    /**
     * ✅ FAST: 1 query by reading the latest scan_type
     */
    private function resolveScanType(int $tripId, int $employeeId): string
    {
        $lastType = Checkin::query()
            ->where('trip_id', $tripId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('id')
            ->value('scan_type');

        if ($lastType === null) {
            return 'checkin';
        }

        if ($lastType === 'checkout') {
            throw ValidationException::withMessages([
                'scan' => ['Employee already checked out.'],
            ]);
        }

        return 'checkout';
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }
}
