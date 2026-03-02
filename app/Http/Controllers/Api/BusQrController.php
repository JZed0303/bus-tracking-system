<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Company\EmployeeResource;
use App\Models\Checkin;
use App\Models\Trip;
use App\Models\TripEmployeeTransfer;
use App\Services\QrCheckinService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class BusQrController extends Controller
{
    // ===== NEW: Scan response now surfaces transfer confirmation state =====
    public function scan(Request $request, QrCheckinService $service)
    {
        $request->validate([
            'qr_token'  => 'required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $bus = $request->user();

        try {
            $qr = $service->getValidQr($request->qr_token);
            $trip = $bus->activeTrip;

            if (!$trip) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No active trip for this bus/driver.',
                    'errors'  => [
                        'trip' => ['No active trip for this bus/driver.'],
                    ],
                ], 409);
            }

            $checkin = $service->scanWithQr(
                qr: $qr,
                trip: $trip,
                lat: $request->latitude,
                lng: $request->longitude
            );

            // ✅ IMPORTANT: full_name is NOT a column; it's an accessor
            $checkin->loadMissing([
                'employee.user:id,first_name,middle_name,last_name,email',
                'employee.company:id,name',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => $checkin->scan_type === 'checkin'
                    ? 'Check-in recorded.'
                    : 'Check-out recorded.',
                'data' => [
                    'trip' => [
                        'id'        => $checkin->trip_id,
                        'scan_type' => $checkin->scan_type,
                        'scan_time' => optional($checkin->scan_time)->toIso8601String(),
                        // true when employee transfer (old bus -> this bus) is confirmed by this scan.
                        'transfer_confirmed' => (bool) TripEmployeeTransfer::query()
                            ->where('to_trip_id', $checkin->trip_id)
                            ->where('employee_id', $checkin->employee_id)
                            ->where('status', 'confirmed')
                            ->exists(),
                    ],
                    'employee' => new EmployeeResource($checkin->employee),
                    'location' => [
                        'latitude'  => $checkin->scan_lat,
                        'longitude' => $checkin->scan_lng,
                    ],
                ],
            ], 201);

        } catch (ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['assignment'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Trip assignment is not active.',
                    'errors'  => $errors,
                ], 422);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $errors,
            ], 422);
        } catch (QueryException $e) {
            if (
                $e->getCode() === '23505' &&
                str_contains($e->getMessage(), 'checkins_trip_id_employee_id_scan_type_unique')
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Scan blocked by legacy checkin uniqueness constraint. Please run latest migrations.',
                    'errors'  => [
                        'database' => ['Legacy constraint checkins_trip_id_employee_id_scan_type_unique is still active.'],
                    ],
                ], 409);
            }

            throw $e;
        }
    }

    public function syncOffline(Request $request, QrCheckinService $service)
    {
        $validated = $request->validate([
            'scans' => 'required|array|min:1|max:200',
            'scans.*.client_scan_id' => 'required|uuid',
            'scans.*.qr_token' => 'required|string',
            'scans.*.scan_time' => 'required|date',
            'scans.*.latitude' => 'nullable|numeric',
            'scans.*.longitude' => 'nullable|numeric',
            'scans.*.trip_id' => 'nullable|integer',
        ]);

        $bus = $request->user();
        $scanRows = $validated['scans'];

        $existingByClientId = Checkin::query()
            ->whereIn('client_scan_id', collect($scanRows)->pluck('client_scan_id')->unique()->values())
            ->get()
            ->keyBy('client_scan_id');

        $items = [];
        $accepted = 0;
        $duplicates = 0;
        $failed = 0;

        foreach ($scanRows as $index => $row) {
            $clientScanId = $row['client_scan_id'];

            if (isset($existingByClientId[$clientScanId])) {
                $existing = $existingByClientId[$clientScanId];
                $items[] = [
                    'index' => $index,
                    'client_scan_id' => $clientScanId,
                    'status' => 'duplicate',
                    'checkin_id' => $existing->id,
                    'trip_id' => $existing->trip_id,
                    'scan_type' => $existing->scan_type,
                ];
                $duplicates++;
                continue;
            }

            $trip = null;
            if (!empty($row['trip_id'])) {
                $trip = Trip::query()
                    ->where('id', (int) $row['trip_id'])
                    ->whereHas('assignment', fn ($q) => $q->where('bus_id', $bus->id))
                    ->first();
            }

            if (!$trip) {
                $trip = $bus->activeTrip;
            }

            if (!$trip) {
                $items[] = [
                    'index' => $index,
                    'client_scan_id' => $clientScanId,
                    'status' => 'failed',
                    'error' => 'No eligible trip found for this bus.',
                ];
                $failed++;
                continue;
            }

            try {
                $checkin = $service->offlineScan(
                    trip: $trip,
                    qrToken: $row['qr_token'],
                    scanTime: $row['scan_time'],
                    lat: $row['latitude'] ?? null,
                    lng: $row['longitude'] ?? null,
                    clientScanId: $clientScanId
                );

                $status = $checkin->wasRecentlyCreated ? 'accepted' : 'duplicate';
                if ($status === 'accepted') {
                    $accepted++;
                } else {
                    $duplicates++;
                }

                $items[] = [
                    'index' => $index,
                    'client_scan_id' => $clientScanId,
                    'status' => $status,
                    'checkin_id' => $checkin->id,
                    'trip_id' => $checkin->trip_id,
                    'scan_type' => $checkin->scan_type,
                    'scan_time' => optional($checkin->scan_time)->toIso8601String(),
                ];
            } catch (ValidationException $e) {
                $items[] = [
                    'index' => $index,
                    'client_scan_id' => $clientScanId,
                    'status' => 'failed',
                    'error' => 'Validation error.',
                    'errors' => $e->errors(),
                ];
                $failed++;
            } catch (QueryException $e) {
                // If two retries race, treat unique conflict as idempotent duplicate.
                if ($e->getCode() === '23505') {
                    $existing = Checkin::query()->where('client_scan_id', $clientScanId)->first();
                    if ($existing) {
                        $items[] = [
                            'index' => $index,
                            'client_scan_id' => $clientScanId,
                            'status' => 'duplicate',
                            'checkin_id' => $existing->id,
                            'trip_id' => $existing->trip_id,
                            'scan_type' => $existing->scan_type,
                        ];
                        $duplicates++;
                        continue;
                    }

                    if (str_contains($e->getMessage(), 'checkins_trip_id_employee_id_scan_type_unique')) {
                        $items[] = [
                            'index' => $index,
                            'client_scan_id' => $clientScanId,
                            'status' => 'failed',
                            'error' => 'Scan blocked by legacy checkin uniqueness constraint. Please run latest migrations.',
                        ];
                        $failed++;
                        continue;
                    }
                }

                $items[] = [
                    'index' => $index,
                    'client_scan_id' => $clientScanId,
                    'status' => 'failed',
                    'error' => 'Database error while syncing scan.',
                ];
                $failed++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Offline sync processed.',
            'summary' => [
                'received' => count($scanRows),
                'accepted' => $accepted,
                'duplicates' => $duplicates,
                'failed' => $failed,
            ],
            'items' => $items,
        ], 200);
    }
}
