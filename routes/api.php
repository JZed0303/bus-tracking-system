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
use App\Http\Controllers\Api\Bus\BusRouteController;
use App\Http\Controllers\Api\VideoCallController;

use App\Http\Resources\Api\V1\BusResource;


/*
|--------------------------------------------------------------------------
| API Registration
|--------------------------------------------------------------------------
| Registers one API surface for both:
| - Legacy (no version prefix): /api/...
| - Explicit v1 namespace:      /api/v1/...
*/
$registerApiSurface = function (string $uriPrefix = '', string $namePrefix = ''): void {
    Route::prefix($uriPrefix)->name($namePrefix)->group(function () {
        Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])
            ->middleware('auth:sanctum');

        Route::prefix('video-calls')->middleware('auth:sanctum')->group(function () {
            Route::post('start', [VideoCallController::class, 'start']);
            Route::get('{id}', [VideoCallController::class, 'show']);
            Route::post('{id}/accept', [VideoCallController::class, 'accept']);
            Route::post('{id}/reject', [VideoCallController::class, 'reject']);
            Route::post('{id}/offer', [VideoCallController::class, 'storeOffer']);
            Route::post('{id}/answer', [VideoCallController::class, 'storeAnswer']);
            Route::post('{id}/ice', [VideoCallController::class, 'storeIce']);
            Route::get('{id}/ice', [VideoCallController::class, 'getIce']);
            Route::post('{id}/recordings', [VideoCallController::class, 'storeRecording']);
            Route::post('{id}/end', [VideoCallController::class, 'end']);
        });

        Route::prefix('bus')->name('bus.')->group(function () {
            Route::post('login', [BusAuthController::class, 'login'])->name('login');

            Route::post('logout', [BusAuthController::class, 'logout'])
                ->middleware('auth:sanctum')
                ->name('logout');

            Route::middleware([
                'auth:sanctum',
                'throttle:bus-api',
            ])->group(function () {
                Route::get('me', function (Request $request) {
                    $bus = $request->user();

                    $bus->load([
                        'activeTrip.latestLocation',
                        'latestGps',
                        'activeAssignment.route',
                        'activeAssignment.driver.user',
                    ]);

                    return BusResource::make($bus);
                })->middleware('abilities:bus:context')->name('me');

                Route::get('debug', function (Request $request) {
                    return response()->json([
                        'user'  => $request->user(),
                        'guard' => 'sanctum',
                    ]);
                })->middleware('abilities:bus:context');

                Route::get('dashboard', [BusDashboardController::class, 'show'])
                    ->middleware('abilities:bus:context')
                    ->name('dashboard');

                Route::get('context', [BusAuthController::class, 'context'])
                    ->middleware('abilities:bus:context')
                    ->name('context');

                Route::get('status', function (Request $request) {
                    $bus = $request->user();

                    return response()->json([
                        'has_assignment' => (bool) $bus->activeAssignment,
                        'has_trip'       => (bool) $bus->activeTrip,
                        'bus_status'     => $bus->status,
                    ]);
                })->middleware('abilities:bus:context')->name('status');

                Route::post('trip/start', [BusTripController::class, 'start'])
                    ->middleware('abilities:bus:trip');
                Route::post('trip/incident', [BusTripController::class, 'reportIncident'])
                    ->middleware('abilities:bus:trip');
                Route::post('trip/end', [BusTripController::class, 'end'])
                    ->middleware('abilities:bus:trip');

                Route::post('gps', [BusGpsController::class, 'store'])
                    ->middleware('throttle:bus-gps')
                    ->middleware('abilities:bus:gps');

                Route::post('scan', [BusQrController::class, 'scan'])
                    ->middleware('abilities:bus:scan');
                Route::post('scan/sync-offline', [BusQrController::class, 'syncOffline'])
                    ->middleware('abilities:bus:scan');

                Route::get('employees/onboard', [BusOnboardEmployeeController::class, 'index'])
                    ->middleware('abilities:bus:context');
                Route::get('route/details', [BusRouteController::class, 'show'])
                    ->middleware('abilities:bus:context')
                    ->name('route.details');

                Route::post('toggleActive', [BusAuthController::class, 'toggleBusActiveStatus'])
                    ->middleware('abilities:bus:context');

                Route::prefix('chat')->group(function () {
                    Route::get('thread', [BusChatController::class, 'thread'])
                        ->middleware('abilities:bus:context');
                    Route::get('messages', [BusChatController::class, 'messages'])
                        ->middleware('abilities:bus:context');
                    Route::post('messages', [BusChatController::class, 'send'])
                        ->middleware('abilities:bus:context');
                });

                Route::get('summary', [BusSummaryController::class, 'index'])
                    ->middleware('abilities:bus:context');
            });
        });
    });
};

// Backward-compatible surface for current clients.
$registerApiSurface('', '');

// Explicit lifecycle namespace for forward-compatible clients.
$registerApiSurface('v1', 'v1.');
