<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Broadcasting\BroadcastController;

use App\Http\Controllers\Api\BusAuthController;
use App\Http\Controllers\Api\BusTripController;
use App\Http\Controllers\Api\BusGpsController;
use App\Http\Controllers\Api\BusQrController;

use App\Http\Controllers\Api\BusSummaryController;
use App\Http\Controllers\Api\Bus\BusDashboardController;
use App\Http\Controllers\Api\Bus\BusChatController;
use App\Http\Controllers\Api\Bus\BusOnboardEmployeeController;

use App\Http\Resources\Api\V1\BusResource;

/*
|--------------------------------------------------------------------------
| Broadcasting Auth (MOBILE - Bearer Token)
|--------------------------------------------------------------------------
| This endpoint will be: POST /api/broadcasting/auth
| Flutter must call /api/broadcasting/auth (NOT /broadcasting/auth)
*/
Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| BUS API (Mobile Bus Application)
|--------------------------------------------------------------------------
*/
Route::prefix('bus')->name('bus.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH (PUBLIC)
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [BusAuthController::class, 'login'])->name('login');

    /*
    |--------------------------------------------------------------------------
    | AUTH (PROTECTED)
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [BusAuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | PROTECTED – BUS DEVICE (SANCTUM)
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'throttle:bus-api',
    ])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | CURRENT AUTHENTICATED BUS
        |--------------------------------------------------------------------------
        */
      Route::get('/me', function (Request $request) {
            $bus = $request->user();

            // Eager load what BusResource needs
            $bus->load([
                'activeTrip.latestLocation', // if Trip has latestLocation relation
                'latestGps',                 // used as fallback by currentLocation()
            ]);

            return BusResource::make($bus);
        })->middleware('abilities:bus:context')->name('me');

        /*
        |--------------------------------------------------------------------------
        | DEBUG (REMOVE IN PROD)
        |--------------------------------------------------------------------------
        */
        Route::get('/debug', function (Request $request) {
            return response()->json([
                'user'  => $request->user(),
                'guard' => 'sanctum',
            ]);
        })->middleware('abilities:bus:context');

        /*
        |--------------------------------------------------------------------------
        | CONTEXT & DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [BusDashboardController::class, 'show'])
            ->middleware('abilities:bus:context')
            ->name('dashboard');

        Route::get('/context', [BusAuthController::class, 'context'])
            ->middleware('abilities:bus:context')
            ->name('context');

        Route::get('/status', function (Request $request) {
            $bus = $request->user();

            return response()->json([
                'has_assignment' => (bool) $bus->activeAssignment,
                'has_trip'       => (bool) $bus->activeTrip,
                'bus_status'     => $bus->status,
            ]);
        })->middleware('abilities:bus:context')->name('status');

        /*
        |--------------------------------------------------------------------------
        | TRIP
        |--------------------------------------------------------------------------
        */
        Route::post('/trip/start', [BusTripController::class, 'start'])
            ->middleware('abilities:bus:trip');

        Route::post('/trip/end', [BusTripController::class, 'end'])
            ->middleware('abilities:bus:trip');

        /*
        |--------------------------------------------------------------------------
        | GPS
        |--------------------------------------------------------------------------
        */
        Route::post('/gps', [BusGpsController::class, 'store'])
            ->middleware('throttle:bus-gps')
            ->middleware('abilities:bus:gps');

        /*
        |--------------------------------------------------------------------------
        | QR
        |--------------------------------------------------------------------------
        */
 Route::post('/scan', [BusQrController::class, 'scan'])
    ->middleware('abilities:bus:scan');

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */
    Route::get('/employees/onboard', [BusOnboardEmployeeController::class, 'index'])
            ->middleware('abilities:bus:context');

        /*
        |--------------------------------------------------------------------------
        | BUS ACTIVE STATUS
        |--------------------------------------------------------------------------
        */
        Route::post('/toggleActive', [BusAuthController::class, 'toggleBusActiveStatus'])
            ->middleware('abilities:bus:context');

        /*
        |--------------------------------------------------------------------------
        | CHAT
        |--------------------------------------------------------------------------
        */
        Route::prefix('chat')->group(function () {
            Route::get('/thread', [BusChatController::class, 'thread'])
                ->middleware('abilities:bus:context');

            Route::get('/messages', [BusChatController::class, 'messages'])
                ->middleware('abilities:bus:context');

            Route::post('/messages', [BusChatController::class, 'send'])
                ->middleware('abilities:bus:context');
        });

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */
        Route::get('/summary', [BusSummaryController::class, 'index'])
            ->middleware('abilities:bus:context');
    });
});
