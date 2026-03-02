<?php

namespace App\Http\Controllers\Api\Bus;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BusRouteController extends Controller
{
    public function show(Request $request)
    {
        $bus = $request->user();
        $today = now('Asia/Manila')->toDateString();

        $assignment = Assignment::query()
            ->with(['route.stops'])
            ->where('bus_id', $bus->id)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        if (!$assignment || !$assignment->route) {
            $expiredAssignment = Assignment::query()
                ->where('bus_id', $bus->id)
                ->where('status', 'active')
                ->whereNotNull('effective_to')
                ->whereDate('effective_to', '<', $today)
                ->latest('effective_to')
                ->first();

            if ($expiredAssignment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Assignment already expired.',
                    'data'    => [
                        'assignment_id' => $expiredAssignment->id,
                        'effective_to'  => optional($expiredAssignment->effective_to)->toDateString(),
                    ],
                ], 422);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'No valid assignment found for this bus.',
                'data'    => null,
            ], 422);
        }

        $trip = $bus->activeTrip()->with('latestLocation')->first();

        if (!$trip) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No active trip found for this bus.',
                'data'    => null,
            ], 409);
        }

        $location = $trip->latestLocation ?? $bus->latestGps;
        $arrivalRadiusMeters = 60.0;
        $arrivedStop = null;

        $stops = $assignment->route->stops->map(function ($stop) use ($location, $arrivalRadiusMeters, &$arrivedStop) {
            $distanceMeters = null;
            $isArrivedNow = false;

            if ($location && $stop->latitude !== null && $stop->longitude !== null) {
                $distanceMeters = $this->distanceMeters(
                    (float) $location->latitude,
                    (float) $location->longitude,
                    (float) $stop->latitude,
                    (float) $stop->longitude
                );

                $isArrivedNow = $distanceMeters <= $arrivalRadiusMeters;
                if ($isArrivedNow && !$arrivedStop) {
                    $arrivedStop = $stop;
                }
            }

            return [
                'id'              => $stop->id,
                'address'         => $stop->address,
                'latitude'        => (float) $stop->latitude,
                'longitude'       => (float) $stop->longitude,
                'order'           => $stop->stop_order,
                'distance_meters' => $distanceMeters !== null ? (int) round($distanceMeters) : null,
                'is_arrived_now'  => $isArrivedNow,
            ];
        })->values();

        $infoSound = [
            'play'      => false,
            'level'     => 'info',
            'message'   => null,
            'stop_id'   => null,
            'event_key' => null,
        ];

        if ($arrivedStop) {
            $infoSound = [
                'play'      => true,
                'level'     => 'info',
                'message'   => 'Arrived at stop: ' . $arrivedStop->address,
                'stop_id'   => $arrivedStop->id,
                'event_key' => 'trip:' . $trip->id . ':stop:' . $arrivedStop->id,
            ];
        }

        $osrmRoute = $this->resolveOsrmRoute($assignment);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'assignment' => [
                    'id' => $assignment->id,
                    'effective_from' => optional($assignment->effective_from)->toDateString(),
                    'effective_to' => optional($assignment->effective_to)->toDateString(),
                ],
                'trip' => [
                    'id' => $trip->id,
                    'status' => $trip->status,
                    'direction' => $trip->direction,
                    'started_at' => optional($trip->actual_start_time)->toIso8601String(),
                ],
                'route' => [
                    'id' => $assignment->route->id,
                    'name' => $assignment->route->name,
                    'path' => [
                        'start' => [
                            'name' => $assignment->route->start_location,
                            'latitude' => $assignment->route->start_lat !== null ? (float) $assignment->route->start_lat : null,
                            'longitude' => $assignment->route->start_lng !== null ? (float) $assignment->route->start_lng : null,
                        ],
                        'end' => [
                            'name' => $assignment->route->end_location,
                            'latitude' => $assignment->route->end_lat !== null ? (float) $assignment->route->end_lat : null,
                            'longitude' => $assignment->route->end_lng !== null ? (float) $assignment->route->end_lng : null,
                        ],
                        'real' => $osrmRoute,
                    ],
                ],
                'location' => $location ? [
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'tracked_at' => ($location->tracked_at ?? $location->created_at)?->toIso8601String(),
                ] : null,
                'stops' => $stops,
                'info_sound' => $infoSound,
            ],
        ]);
    }

    private function resolveOsrmRoute(Assignment $assignment): array
    {
        $route = $assignment->route;
        $waypoints = [];

        if ($route->start_lat !== null && $route->start_lng !== null) {
            $waypoints[] = [(float) $route->start_lng, (float) $route->start_lat];
        }

        foreach ($route->stops as $stop) {
            if ($stop->latitude === null || $stop->longitude === null) {
                continue;
            }
            $waypoints[] = [(float) $stop->longitude, (float) $stop->latitude];
        }

        if ($route->end_lat !== null && $route->end_lng !== null) {
            $waypoints[] = [(float) $route->end_lng, (float) $route->end_lat];
        }

        if (count($waypoints) < 2) {
            return [
                'source' => 'osrm',
                'available' => false,
                'message' => 'Insufficient coordinates for OSRM route.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        $coordinates = implode(';', array_map(
            fn (array $point) => $point[0] . ',' . $point[1],
            $waypoints
        ));

        $baseUrl = rtrim(config('services.osrm.base_url', 'https://router.project-osrm.org'), '/');
        $url = $baseUrl . '/route/v1/driving/' . $coordinates;

        try {
            $response = Http::timeout(8)->retry(1, 150)->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'false',
                'alternatives' => 'false',
                'annotations' => 'false',
            ]);
        } catch (ConnectionException $e) {
            return [
                'source' => 'osrm',
                'available' => false,
                'message' => 'OSRM is unreachable.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        if (!$response->ok()) {
            return [
                'source' => 'osrm',
                'available' => false,
                'message' => 'OSRM request failed.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        $data = $response->json();
        $firstRoute = $data['routes'][0] ?? null;

        if (!is_array($firstRoute) || empty($firstRoute['geometry'])) {
            return [
                'source' => 'osrm',
                'available' => false,
                'message' => 'OSRM route not found.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        return [
            'source' => 'osrm',
            'available' => true,
            'message' => 'OSRM route resolved.',
            'geometry' => $firstRoute['geometry'],
            'distance_meters' => isset($firstRoute['distance']) ? (float) $firstRoute['distance'] : null,
            'duration_seconds' => isset($firstRoute['duration']) ? (float) $firstRoute['duration'] : null,
        ];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
