<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\TripEmployeeTransfer;
use App\Models\Bus;
use App\Models\Trip;
use App\Support\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class BusTripController extends Controller
{
    // ===== NEW: Direction-aware trip start (pickup/dropoff) + active entity validation =====
    public function start(Request $request)
    {
        $validated = $request->validate([
            'direction' => 'required|in:pickup,dropoff',
        ]);

        $bus = $request->user();
        $today = now('Asia/Manila')->toDateString();

        if ($bus->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start trip: bus is not active.',
                'data' => ['bus_status' => $bus->status],
            ], 422);
        }

        // NEW RULE: assignment must match today's date window and requested trip leg.
        $assignment = $bus->assignments()
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->where(function ($q) use ($validated) {
                $q->where('leg', 'both')
                    ->orWhere('leg', $validated['direction'])
                    ->orWhereNull('leg');
            })
            ->with(['driver', 'route'])
            ->orderByDesc('effective_from')
            ->first();

        if (!$assignment) {
            $expiredAssignment = $bus->assignments()
                ->where('status', 'active')
                ->whereNotNull('effective_to')
                ->whereDate('effective_to', '<', $today)
                ->latest('effective_to')
                ->first();

            if ($expiredAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start trip: assignment already expired',
                    'data'    => [
                        'assignment_id' => $expiredAssignment->id,
                        'effective_to' => optional($expiredAssignment->effective_to)->toDateString(),
                    ],
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active assignment found for this bus',
                'data'    => null,
            ], 404);
        }

        // NEW RULE: do not allow trip start if assigned driver is inactive.
        if (!$assignment->driver || $assignment->driver->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start trip: assigned driver is not active.',
                'data' => ['assignment_id' => $assignment->id],
            ], 422);
        }

        // NEW RULE: do not allow trip start if assigned route is inactive.
        if (!$assignment->route || $assignment->route->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start trip: assigned route is not active.',
                'data' => ['assignment_id' => $assignment->id],
            ], 422);
        }

        // Prevent duplicate trips
        $existingTrip = Trip::where('assignment_id', $assignment->id)
            ->where('status', 'ongoing')
            ->first();

        if ($existingTrip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip already in progress',
                'data'    => [
                    'trip_id' => $existingTrip->id,
                    'started_at' => optional($existingTrip->actual_start_time)->toIso8601String(),
                ],
            ], 409);
        }

        // Create trip
        $trip = Trip::create([
            'assignment_id'     => $assignment->id,
            'trip_date'         => now()->toDateString(),
            'direction'         => $validated['direction'],
            'status'            => 'ongoing',
            'actual_start_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip started successfully',
            'data'    => [
                'trip_id'      => $trip->id,
                'direction'    => $trip->direction,
                'status'       => $trip->status,
                'started_at'   => $trip->actual_start_time->toIso8601String(),
            ],
        ], 201);
    }

    public function reportIncident(Request $request)
    {
        // ===== NEW: Mid-trip incident flow =====
        // Supports: maintenance/breakdown/emergency + optional replacement bus transfer.
        $validated = $request->validate([
            'incident_type' => 'required|string|in:maintenance,breakdown,emergency',
            'reason' => 'required|string|max:255',
            'replacement_bus_id' => 'nullable|integer|exists:buses,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'override_capacity' => 'nullable|boolean',
        ]);

        $bus = $request->user();

        $trip = Trip::query()
            ->where('status', 'ongoing')
            ->whereHas('assignment', function ($q) use ($bus) {
                $q->where('bus_id', $bus->id);
            })
            ->with('assignment')
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'No ongoing trip found for this bus.',
                'data' => null,
            ], 404);
        }

        // Pre-validate replacement bus before transaction to keep DB writes clean.
        $replacementBus = null;
        $replacementAssignment = null;
        $onboardEmployeeIds = collect();

        if (!empty($validated['replacement_bus_id'])) {
            $replacementBus = Bus::query()->find((int) $validated['replacement_bus_id']);

            if (!$replacementBus || $replacementBus->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Replacement bus must be active.',
                    'data' => ['replacement_bus_status' => $replacementBus?->status],
                ], 422);
            }

            $replacementAssignment = $replacementBus->assignments()
                ->active()
                ->where('company_id', $trip->assignment->company_id)
                ->where(function ($q) use ($trip) {
                    $q->where('leg', 'both')
                        ->orWhere('leg', $trip->direction)
                        ->orWhereNull('leg');
                })
                ->with(['driver', 'route'])
                ->latest('effective_from')
                ->first();

            $hasOngoingReplacementTrip = Trip::query()
                ->where('status', 'ongoing')
                ->whereHas('assignment', fn ($q) => $q->where('bus_id', $replacementBus->id))
                ->exists();

            if ($hasOngoingReplacementTrip) {
                return response()->json([
                    'success' => false,
                    'message' => 'Replacement bus already has an ongoing trip.',
                    'data' => ['replacement_bus_id' => $replacementBus->id],
                ], 409);
            }

            $onboardEmployeeIds = Checkin::query()
                ->where('trip_id', $trip->id)
                ->where('scan_type', 'checkin')
                ->when(!empty($validated['employee_ids']), function ($q) use ($validated) {
                    $q->whereIn('employee_id', $validated['employee_ids']);
                })
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('checkins as c2')
                        ->whereColumn('c2.trip_id', 'checkins.trip_id')
                        ->whereColumn('c2.employee_id', 'checkins.employee_id')
                        ->where('c2.scan_type', 'checkout');
                })
                ->pluck('employee_id')
                ->unique()
                ->values();

            $requiredSeats = $onboardEmployeeIds->count();
            $capacity = (int) ($replacementBus->capacity ?? 0);
            $overrideRequested = (bool) ($validated['override_capacity'] ?? false);

            if ($requiredSeats > $capacity) {
                if ($overrideRequested) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Capacity override is not allowed from bus device API.',
                        'data' => ['required_seats' => $requiredSeats, 'bus_capacity' => $capacity],
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Replacement bus capacity is insufficient.',
                    'data' => ['required_seats' => $requiredSeats, 'bus_capacity' => $capacity],
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($validated, $bus, $trip, $replacementBus, $replacementAssignment, $onboardEmployeeIds) {
            $reason = $validated['reason'] ?? $validated['incident_type'];

            // Close failed trip with incident metadata.
            $trip->update([
                'status' => 'cancelled',
                'actual_end_time' => now(),
                'ended_reason' => $validated['incident_type'],
                'incident_reported_at' => now(),
            ]);

            // Operational rule: mark physical bus as maintenance when breakdown/maintenance incident is reported.
            if (in_array($validated['incident_type'], ['maintenance', 'breakdown'], true)) {
                $bus->update(['status' => 'maintenance']);
            }

            $replacementTrip = null;
            $transferredCount = 0;

            if ($replacementBus) {
                if (!$replacementAssignment) {
                    $replacementAssignment = \App\Models\Assignment::create([
                        'driver_id' => $trip->assignment->driver_id,
                        'bus_id' => $replacementBus->id,
                        'company_id' => $trip->assignment->company_id,
                        'route_id' => $trip->assignment->route_id,
                        'effective_from' => now('Asia/Manila')->toDateString(),
                        'effective_to' => null,
                        'status' => 'active',
                        'leg' => $trip->direction,
                    ]);
                }

                // Create a linked continuation trip on the replacement bus.
                $replacementTrip = Trip::create([
                    'assignment_id' => $replacementAssignment->id,
                    'transfer_from_trip_id' => $trip->id,
                    'trip_date' => now()->toDateString(),
                    'direction' => $trip->direction,
                    'status' => 'ongoing',
                    'actual_start_time' => now(),
                ]);

                foreach ($onboardEmployeeIds as $employeeId) {
                    // Force-close original trip rider state so they are not "open" on two buses.
                    Checkin::firstOrCreate([
                        'trip_id' => $trip->id,
                        'employee_id' => $employeeId,
                        'scan_type' => 'checkout',
                    ], [
                        'scan_time' => now('Asia/Manila'),
                        'scan_lat' => null,
                        'scan_lng' => null,
                        'scanned_by_employee_id' => $employeeId,
                    ]);

                    // Track transfer state; replacement bus scan will mark this as confirmed.
                    TripEmployeeTransfer::updateOrCreate([
                        'from_trip_id' => $trip->id,
                        'to_trip_id' => $replacementTrip->id,
                        'employee_id' => $employeeId,
                    ], [
                        'created_by_bus_id' => $bus->id,
                        'status' => 'pending_confirm',
                        'reason' => $reason,
                        'transferred_at' => now(),
                    ]);
                }

                $transferredCount = $onboardEmployeeIds->count();
            }

            return [
                'trip' => $trip->fresh(),
                'replacement_trip' => $replacementTrip,
                'transferred_count' => $transferredCount,
                'employee_ids' => $onboardEmployeeIds->values()->all(),
            ];
        });

        AuditTrail::log(
            event: 'trip_incident_reported',
            auditable: $trip,
            oldValues: [
                'trip_status' => 'ongoing',
                'bus_id' => $trip->assignment?->bus_id,
            ],
            newValues: [
                'trip_status' => 'cancelled',
                'incident_type' => $validated['incident_type'],
                'reason' => $validated['reason'],
                'old_bus_id' => $trip->assignment?->bus_id,
                'replacement_bus_id' => $replacementBus?->id,
                'replacement_trip_id' => $result['replacement_trip']?->id,
                'affected_employee_ids' => $result['employee_ids'],
                'transferred_count' => $result['transferred_count'],
                'reported_at' => now()->toIso8601String(),
            ],
            tags: 'incident,transfer'
        );

        return response()->json([
            'success' => true,
            'message' => 'Incident handled successfully.',
            'data' => [
                'ended_trip_id' => $result['trip']->id,
                'ended_reason' => $result['trip']->ended_reason,
                'replacement_trip_id' => $result['replacement_trip']?->id,
                'transferred_count' => $result['transferred_count'],
            ],
        ]);
    }

    public function end(Request $request)
    {
        $bus = $request->user();

        if (!$bus) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data'    => null,
            ], 401);
        }

        // Find ongoing trip for this bus
        $trip = Trip::where('status', 'ongoing')
            ->whereHas('assignment', function ($q) use ($bus) {
                $q->where('bus_id', $bus->id);
            })
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'No active trip to end',
                'data'    => null,
            ], 404);
        }

        $trip->update([
            'status'          => 'completed',
            'actual_end_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip ended successfully',
            'data'    => [
                'trip_id'     => $trip->id,
                'status'      => $trip->status,
                'started_at'  => optional($trip->actual_start_time)->toIso8601String(),
                'ended_at'    => $trip->actual_end_time->toIso8601String(),
            ],
        ], 200);
    }
}
