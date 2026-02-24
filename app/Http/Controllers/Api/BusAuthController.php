<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusLoginRequest;
use App\Models\Bus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Events\BusStatusUpdated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BusAuthController extends Controller
{
    /**
     * Bus device login
     */
   public function login(BusLoginRequest $request): JsonResponse
{
    $validated = $request->validated();

    $bus = Bus::query()
        ->where('plate_number', $validated['plate_number'])
        ->with(['activeAssignment'])
        ->first();

    if (!$bus || !Hash::check($validated['bus_code'], $bus->bus_code)) {
        return response()->json(['message' => 'Invalid bus credentials'], 401);
    }

    // ✅ Reactivate on successful login
    $bus->markActive();

    $bus->tokens()->delete();

    $token = $bus->createToken('bus-device', [
        'bus:context',
        'bus:gps',
        'bus:trip',
        'bus:scan',
    ])->plainTextToken;

    $assignment = $bus->activeAssignment;

    return response()->json([
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'bus' => [
            'id'           => $bus->id,
            'company_id'   => $assignment?->company_id,
            'plate_number' => $bus->plate_number,
            'capacity'     => (int) $bus->capacity,
            'status'       => $bus->status,
        ],
    ]);
}



    /**
     * Return authenticated bus context
     */
    public function context(): JsonResponse
    {
        /** @var \App\Models\Bus|null $bus */
        $bus = auth('sanctum')->user();

        if (!$bus) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Load relationships only when needed
        $bus->load([
            'activeAssignment.company',
            'activeAssignment.driver.user',
            'activeAssignment.route',
            'activeTrip',
        ]);

        $assignment = $bus->activeAssignment;
        $trip       = $bus->activeTrip;

        return response()->json([
            'bus' => [
                'id'           => $bus->id,
                'company_id'   => $assignment?->company_id, // ✅ include here too
                'plate_number' => $bus->plate_number,
                'capacity'     => (int) $bus->capacity,
                'status'       => $bus->status,
            ],

            'company' => $assignment?->company ? [
                'id'   => $assignment->company->id,
                'name' => $assignment->company->name,
            ] : null,

            'driver' => $assignment?->driver ? [
                'id'   => $assignment->driver->id,
                'name' => $assignment->driver->user->full_name,
            ] : null,

            'route' => $assignment?->route ? [
                'id'   => $assignment->route->id,
                'name' => $assignment->route->name,
            ] : null,

            'trip' => $trip ? [
                'id'     => $trip->id,
                'status' => $trip->status,
            ] : null,
        ]);
    }

      public function logout(Request $request)
    {
        $bus = $request->user(); // authenticated bus
        $bus->loadMissing('activeAssignment');
        $companyId = $bus->activeAssignment?->company_id
            ?? $bus->assignments()->latest('effective_from')->value('company_id');

        // 1. Mark offline
        $bus->markOffline();

        // 2. Broadcast logout (for map removal)
        broadcast(new BusStatusUpdated([
            'type'       => 'logout',
            'bus_id'     => (int) $bus->id,
            'company_id' => $companyId ? (int) $companyId : null,
            'status'     => 'offline',
            'updated_at' => now()->toISOString(),
        ]));

        // 3. Revoke current token
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'offline',
            'bus' => [
                'id' => (int) $bus->id,
                'status' => $bus->status,
            ],
        ]);
    }
   public function toggleBusActiveStatus(Request $request): JsonResponse
{
    /** @var \App\Models\Bus|null $bus */
    $bus = auth('sanctum')->user();

    if (!$bus || !($bus instanceof \App\Models\Bus)) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $request->validate([
        'isActive' => 'required|boolean',
    ]);

    // If you don't have a column, you can just acknowledge it
    // OR update bus->status if you want:
    // $bus->update(['status' => $request->boolean('isActive') ? 'active' : 'inactive']);

    Log::info('Bus toggleActive', [
        'bus_id' => $bus->id,
        'isActive' => $request->boolean('isActive'),
    ]);

    return response()->json([
        'status' => 'ok',
        'bus_id' => $bus->id,
        'isActive' => $request->boolean('isActive'),
    ], 200);
}

}
