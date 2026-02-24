<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QrScanController extends Controller
{
    public function scan(Request $request, Trip $trip, QrCheckinService $service)
{
    $data = $request->validate([
        'qr_token' => 'required|string',
        'lat' => 'nullable|numeric',
    'lng' => 'nullable|numeric',
    ]);

    $checkin = $service->scan(
        $trip,
        $data['qr_token'],
        auth()->user()->driver->id,
        $data['lat'] ?? null,
        $data['lng'] ?? null
    );

    return response()->json([
        'scan_type' => $checkin->scan_type,
        'employee_id' => $checkin->employee_id,
    ]);
}

}
