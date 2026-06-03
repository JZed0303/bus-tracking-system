<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\EmployeeQrCode;
use App\Models\Checkin;
use App\Models\TripEmployeeTransfer;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
class QrCheckinService
{
    // ===== NEW: Transfer-aware scan rules =====
    // Allows replacement-trip scans when there is an approved transfer record,
    // while still blocking unsafe "open on another unrelated trip" cases.
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
     * Minimum gap between two scans of the same employee on the same trip.
     * Prevents accidental double-tap and near-duplicate sync records.
     */
    private int $minScanIntervalSeconds = 15;

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

        $scanAt = Carbon::now('Asia/Manila');

        // Serialize capacity checks per trip to avoid overbooking on concurrent scans.
        Trip::query()
            ->whereKey($trip->id)
            ->lockForUpdate()
            ->value('id');

        // ✅ lock the latest scan row (prevents double-checkout)
        $last = Checkin::query()
            ->where('trip_id', $trip->id)
            ->where('employee_id', $employee->id)
            ->whereNull('voided_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
                                                                                                                                
        $this->assertScanTimeIsValid($last?->scan_time, $scanAt);
        $scanType = $this->resolveScanTypeFromLast($last);
        $this->assertBusHasAvailableCapacity($trip, $scanType);

        $checkin = Checkin::create([
            'trip_id'                => $trip->id,
            'employee_id'            => $employee->id,
            'scan_type'              => $scanType,
            'scan_time'              => $scanAt,
            'scan_lat'               => $lat,
            'scan_lng'               => $lng,
            'scanned_by_employee_id' => $employee->id,
        ]);

        if ($scanType === 'checkin') {
            // NEW FLOW: first successful check-in on replacement trip confirms transfer completion.
            TripEmployeeTransfer::query()
                ->where('to_trip_id', $trip->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'pending_confirm')
                ->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
        }

        return $checkin;
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
            ->whereNull('voided_at')
            ->whereNotExists(function ($q) use ($employeeId) {
                $q->selectRaw('1')
                  ->from('checkins as c2')
                  ->whereColumn('c2.trip_id', 'checkins.trip_id')
                  ->where('c2.employee_id', $employeeId)
                  ->where('c2.scan_type', 'checkout')
                  ->whereNull('c2.voided_at');
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
        $existing = Checkin::query()
            ->where('client_scan_id', $clientScanId)
            ->first();

        if ($existing) {
            return $existing;
        }

        // ✅ Validate ONLY active assignment
        $this->assertTripHasValidAssignment($trip);
        $this->assertTripNotEnded($trip);

        $this->assertLocationProvided($lat, $lng);

        // Offline uploads may include scans captured before a QR was rotated/deactivated.
        // Resolve by historical token to avoid false "unmatched" employee states.
        $qr = $this->getValidQrForOfflineScan($qrToken);
        $employee = $qr->employee;

        $this->assertEmployeeBelongsToTripCompany($employee->company_id, $trip);

        if ($this->requireSpecificTrip) {
            $this->assertEmployeeIsOnThisTrip($trip, $employee->id);
        } else {
            $this->assertEmployeeNotOpenOnOtherTrip($trip->id, $employee->id);
        }

        $this->assertWithinAllowedArea($trip, (float) $lat, (float) $lng);

        $scanAt = Carbon::parse($scanTime)->timezone('Asia/Manila');

        return DB::transaction(function () use ($trip, $employee, $lat, $lng, $scanAt, $clientScanId) {
            // Serialize capacity checks per trip to avoid overbooking on concurrent sync.
            Trip::query()
                ->whereKey($trip->id)
                ->lockForUpdate()
                ->value('id');

            $last = Checkin::query()
                ->where('trip_id', $trip->id)
                ->where('employee_id', $employee->id)
                ->whereNull('voided_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $this->assertScanTimeIsValid($last?->scan_time, $scanAt);
            $scanType = $this->resolveScanTypeFromLast($last);
            $this->assertBusHasAvailableCapacity($trip, $scanType);

            return Checkin::create([
                'trip_id'                => $trip->id,
                'employee_id'            => $employee->id,
                'scan_type'              => $scanType,
                'scan_time'              => $scanAt,
                'scan_lat'               => $lat,
                'scan_lng'               => $lng,

                // ✅ SCANNER = EMPLOYEE
                'scanned_by_employee_id' => $employee->id,

                'client_scan_id'         => $clientScanId,
                'is_offline'             => true,
                'synced_at'              => now(),
            ]);
        });
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
        $trip->loadMissing('assignment.bus', 'assignment.route', 'assignment.driver');

        if (!$trip->assignment || !$trip->assignment->isActive()) {
            throw ValidationException::withMessages([
                'assignment' => ['Trip assignment is not active.'],
            ]);
        }

        // NEW RULE: assignment leg must support the trip direction.
        if (!$trip->assignment->supportsDirection($trip->direction)) {
            throw ValidationException::withMessages([
                'assignment' => ['Assignment leg does not match trip direction.'],
            ]);
        }

        // NEW RULE: assignment entities must be active at scan time.
        if (
            !$trip->assignment->bus
            || !$trip->assignment->route
            || !$trip->assignment->driver
        ) {
            throw ValidationException::withMessages([
                'assignment' => ['Trip has incomplete assignment data.'],
            ]);
        }

        if ($trip->assignment->bus->status !== 'active') {
            throw ValidationException::withMessages([
                'bus' => ['Assigned bus is not active.'],
            ]);
        }

        if ($trip->assignment->driver->status !== 'active') {
            throw ValidationException::withMessages([
                'driver' => ['Assigned driver is not active.'],
            ]);
        }

        if ($trip->assignment->route->status !== 'active') {
            throw ValidationException::withMessages([
                'route' => ['Assigned route is not active.'],
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

    /**
     * Offline sync is tolerant to QR rotation: it can resolve inactive historical tokens.
     * Live scans must continue to use getValidQr() which requires an active token.
     */
    public function getValidQrForOfflineScan(string $qrToken): EmployeeQrCode
    {
        $qrToken = trim($qrToken);

        $qr = EmployeeQrCode::query()
            ->with(['employee'])
            ->where('qr_token', $qrToken)
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
        $openTripIds = Checkin::query()
            ->where('employee_id', $employeeId)
            ->where('trip_id', '!=', $currentTripId)
            ->where('scan_type', 'checkin')
            ->whereNull('voided_at')
            ->whereNotExists(function ($q) use ($employeeId) {
                $q->selectRaw('1')
                  ->from('checkins as c2')
                  ->whereColumn('c2.trip_id', 'checkins.trip_id')
                  ->where('c2.employee_id', $employeeId)
                  ->where('c2.scan_type', 'checkout')
                  ->whereNull('c2.voided_at');
            })
            ->pluck('trip_id')
            ->unique()
            ->values();

        if ($openTripIds->isEmpty()) {
            return;
        }

        // NEW RULE: allow an existing open trip only if this scan is for a mapped transfer target trip.
        $allowedTransferFromTripIds = TripEmployeeTransfer::query()
            ->where('to_trip_id', $currentTripId)
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending_confirm', 'confirmed'])
            ->pluck('from_trip_id')
            ->unique();

        $disallowedOpenTripIds = $openTripIds->diff($allowedTransferFromTripIds);

        if ($disallowedOpenTripIds->isNotEmpty()) {
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

    private function assertBusHasAvailableCapacity(Trip $trip, string $scanType): void
    {
        if ($scanType !== 'checkin') {
            return;
        }

        $busCapacity = (int) ($trip->assignment?->bus?->capacity ?? 0);
        if ($busCapacity <= 0) {
            return;
        }

        $onboardCount = $this->onboardCountForTrip((int) $trip->id);
        if ($onboardCount >= $busCapacity) {
            throw ValidationException::withMessages([
                'capacity' => ['Bus is full, No seats available.'],
            ]);
        }
    }

    private function onboardCountForTrip(int $tripId): int
    {
        return (int) Checkin::query()
            ->where('trip_id', $tripId)
            ->where('scan_type', 'checkin')
            ->whereNull('voided_at')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                  ->from('checkins as c2')
                  ->whereColumn('c2.trip_id', 'checkins.trip_id')
                  ->whereColumn('c2.employee_id', 'checkins.employee_id')
                  ->where('c2.scan_type', 'checkout')
                  ->whereNull('c2.voided_at');
            })
            ->distinct()
            ->count('employee_id');
    }

    /**
     * ✅ FAST: 1 query by reading the latest scan_type
     */
    private function resolveScanTypeFromLast(?Checkin $last): string
    {
        if ($last === null) {
            return 'checkin';
        }

        if ($last->scan_type === 'checkout') {
            throw ValidationException::withMessages([
                'scan' => ['Employee already checked out.'],
            ]);
        }

        return 'checkout';
    }

    private function assertScanTimeIsValid($lastScanTime, Carbon $currentScanTime): void
    {
        if (!$lastScanTime) {
            return;
        }

        $last = $lastScanTime instanceof Carbon
            ? $lastScanTime->copy()
            : Carbon::parse($lastScanTime);

        if ($currentScanTime->lessThanOrEqualTo($last)) {
            throw ValidationException::withMessages([
                'scan' => ['Scan time must be later than the last scan.'],
            ]);
        }

        $diffSeconds = $last->diffInSeconds($currentScanTime);
        if ($diffSeconds < $this->minScanIntervalSeconds) {
            throw ValidationException::withMessages([
                'scan' => ["Duplicate scan detected. Please wait {$this->minScanIntervalSeconds} seconds before scanning again."],
            ]);
        }
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
