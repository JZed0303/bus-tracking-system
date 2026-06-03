<?php

namespace App\Http\Controllers\Api\Bus;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Bus\EmployeeTripStatusResource;
use App\Models\Assignment;
use App\Models\Employee;
use App\Models\TripEmployeeTransfer;
use Illuminate\Http\Request;

class BusOnboardEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $bus  = $request->user();
        $trip = $bus->activeTrip; // may be null

        $today = today();

        // Always resolve active assignment today (non-expired) for this bus
        $assignment = Assignment::query()
            ->with('bus:id,capacity')
            ->where('bus_id', $bus->id)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        if (!$assignment || !$assignment->company_id) {
            // No valid assignment -> we cannot know which employees to show
            return response()->json([
                'status'  => 'error',
                'message' => 'No active (non-expired) assignment found for this bus today.',
                'debug'   => [
                    'bus_id' => $bus->id,
                    'trip_id' => $trip?->id,
                ],
            ], 422);
        }

        $companyId = (int) $assignment->company_id;

        // 1) Get employees for the assigned company
        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->with(['user', 'company'])
            ->get();

        // 2) If there is an active trip, attach ONLY that trip's scans
        if ($trip) {
            $employees->load([
                'checkins' => function ($q) use ($trip) {
                    $q->where('trip_id', $trip->id)
                      ->whereNull('voided_at')
                      ->orderBy('created_at');
                }
            ]);
        }

        // NEW: include transfer rows so replacement buses can show pending transfer riders.
        $transferRows = collect();
        if ($trip) {
            $transferRows = TripEmployeeTransfer::query()
                ->where('to_trip_id', $trip->id)
                ->whereIn('status', ['pending_confirm', 'confirmed'])
                ->get()
                ->keyBy('employee_id');
        }

        // 3) Build rows
        $rows = $employees->map(function ($employee) use ($trip, $transferRows) {
            $checkin = null;
            $checkout = null;

            if ($trip) {
                $checkin  = $employee->checkins->where('scan_type', 'checkin')->last();
                $checkout = $employee->checkins->where('scan_type', 'checkout')->last();
            }

            $status = 'not_scanned';
            if ($trip) {
                $transfer = $transferRows->get($employee->id);

                if ($checkout) $status = 'dropped';
                elseif ($checkin) $status = 'onboard';
                // NEW STATUS: transferred from failed bus but not yet re-scanned on replacement bus.
                elseif ($transfer && $transfer->status === 'pending_confirm') $status = 'transferred_pending';
            }

            return [
                'employee' => $employee,
                'checkin'  => $checkin,
                'checkout' => $checkout,
                'status'   => $status,
            ];
        });

        $statusCounts = $rows->countBy('status');
        $onboardCount = (int) ($statusCounts->get('onboard', 0));
        $busCapacity = (int) ($assignment->bus->capacity ?? 0);
        $availableCapacity = max(0, $busCapacity - $onboardCount);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'trip' => [
                    'has_active_trip' => (bool) $trip,
                    'trip_id'         => $trip?->id,
                    'status'          => $trip?->status,
                ],
                'assignment' => [
                    'assignment_id' => $assignment->id,
                    'company_id'    => $companyId,
                ],
                'capacity' => [
                    'bus_capacity' => $busCapacity,
                    'onboard_count' => $onboardCount,
                    'available_capacity' => $availableCapacity,
                ],
                'status_counts' => [
                    'onboard' => $onboardCount,
                    'dropped' => (int) ($statusCounts->get('dropped', 0)),
                    'not_scanned' => (int) ($statusCounts->get('not_scanned', 0)),
                    'transferred_pending' => (int) ($statusCounts->get('transferred_pending', 0)),
                ],
                'count'     => $rows->count(),
                'employees' => EmployeeTripStatusResource::collection($rows),
            ],
        ]);
    }
}
