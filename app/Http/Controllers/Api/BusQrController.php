<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Company\EmployeeResource;
use App\Models\Trip;
use App\Services\QrCheckinService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BusQrController extends Controller
{
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
            $employeeId = (int) $qr->employee_id;

            $trip = $bus->activeTrip;

            $employeeActiveTripId = $service->employeeActiveTripIdFromEmployee($employeeId);

            if ($employeeActiveTripId !== null) {
                if (!$trip || (int) $trip->id !== (int) $employeeActiveTripId) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Employee is already on an active trip.',
                        'errors'  => [
                            'employee' => ['Employee is already on an active trip.'],
                        ],
                        'employee_active_trip_id' => $employeeActiveTripId,
                    ], 409);
                }
            }

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
        }
    }
}
