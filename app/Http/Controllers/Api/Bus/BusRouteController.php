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
        $recentLocations = $trip->locations()
            ->orderByDesc('tracked_at')
            ->limit(2)
            ->get();

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

        $resolvedRoute = $this->resolvePreferredRoute($assignment);
        $operationalAlerts = $this->detectOperationalAlerts($trip, $location, $recentLocations);

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
                        'real' => $resolvedRoute,
                    ],
                ],
                'location' => $location ? [
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'tracked_at' => ($location->tracked_at ?? $location->created_at)?->toIso8601String(),
                ] : null,
                'stops' => $stops,
                'info_sound' => $infoSound,
                // Explicit anomaly flags so clients can avoid false "late/off-route" assumptions.
                'operational_alerts' => $operationalAlerts,
            ],
        ]);
    }

    private function resolvePreferredRoute(Assignment $assignment): array
    {
        $waypoints = $this->buildSanitizedWaypoints($assignment);

        if (count($waypoints) < 2) {
            return [
                'source' => 'none',
                'available' => false,
                'message' => 'Insufficient coordinates for route resolution.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        $ors = $this->resolveOrsRoute($waypoints);
        if ($ors['available'] === true) {
            return $ors;
        }

        $osrm = $this->resolveOsrmRoute($waypoints);
        if ($osrm['available'] === true) {
            $osrm['fallback_reason'] = $ors['message'] ?? 'ORS unavailable';
            return $osrm;
        }

        return [
            'source' => 'none',
            'available' => false,
            'message' => 'All routing providers failed.',
            'providers' => [
                'openrouteservice' => $ors,
                'osrm' => $osrm,
            ],
            'geometry' => null,
            'distance_meters' => null,
            'duration_seconds' => null,
        ];
    }

    private function buildSanitizedWaypoints(Assignment $assignment): array
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

        // Drop consecutive near-duplicate points to reduce provider geometry mismatch.
        $sanitized = [];
        foreach ($waypoints as $point) {
            if (empty($sanitized)) {
                $sanitized[] = $point;
                continue;
            }

            $last = $sanitized[count($sanitized) - 1];
            $isDuplicate = $this->distanceMeters($last[1], $last[0], $point[1], $point[0]) < 2.0;
            if ($isDuplicate) {
                continue;
            }

            $sanitized[] = $point;
        }

        return $sanitized;
    }

    private function resolveOrsRoute(array $waypoints): array
    {
        $apiKey = (string) config('services.openrouteservice.api_key');
        if ($apiKey === '') {
            return [
                'source' => 'openrouteservice',
                'available' => false,
                'message' => 'OpenRouteService API key not configured.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        $url = rtrim((string) config('services.openrouteservice.base_url', 'https://api.openrouteservice.org'), '/')
            . '/v2/directions/driving-car/geojson';

        try {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->withHeaders([
                    'Authorization' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'coordinates' => $waypoints,
                    'radiuses' => array_fill(0, count($waypoints), 1000),
                    'instructions' => false,
                ]);
        } catch (ConnectionException $e) {
            return [
                'source' => 'openrouteservice',
                'available' => false,
                'message' => 'OpenRouteService is unreachable.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        if (!$response->ok()) {
            return [
                'source' => 'openrouteservice',
                'available' => false,
                'message' => 'OpenRouteService request failed.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
                'provider_status' => $response->status(),
                'provider_error' => $response->json('error') ?? $response->json('message'),
            ];
        }

        $feature = $response->json('features.0');
        $geometry = $feature['geometry']['coordinates'] ?? null;
        $summary = $feature['properties']['summary'] ?? [];

        if (!is_array($geometry) || count($geometry) < 2) {
            return [
                'source' => 'openrouteservice',
                'available' => false,
                'message' => 'OpenRouteService route not found.',
                'geometry' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
            ];
        }

        return [
            'source' => 'openrouteservice',
            'available' => true,
            'message' => 'OpenRouteService route resolved.',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $geometry,
            ],
            'distance_meters' => isset($summary['distance']) ? (float) $summary['distance'] : null,
            'duration_seconds' => isset($summary['duration']) ? (float) $summary['duration'] : null,
        ];
    }

    private function resolveOsrmRoute(array $waypoints): array
    {
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

    private function detectOperationalAlerts($trip, $location, $recentLocations): array
    {
        $nowManila = now('Asia/Manila');
        $staleAfter = (int) env('BUS_LOCATION_STALE_AFTER_SECONDS', 120);
        $bufferedAfter = (int) env('BUS_LOCATION_BUFFERED_AFTER_SECONDS', 900);
        $clockAheadTolerance = (int) env('BUS_LOCATION_CLOCK_AHEAD_TOLERANCE_SECONDS', 120);
        $maxSpeedKph = (float) env('BUS_SPEED_MAX_KPH', 130);

        $trackedAt = $location?->tracked_at ?? $location?->created_at;
        $ageSeconds = null;

        if ($trackedAt) {
            $ageSeconds = $trackedAt->copy()->setTimezone('Asia/Manila')->diffInSeconds($nowManila, false);
        }

        $impliedSpeedKph = null;
        if ($recentLocations->count() >= 2) {
            $latest = $recentLocations[0];
            $previous = $recentLocations[1];
            $deltaSeconds = abs($latest->tracked_at?->diffInSeconds($previous->tracked_at) ?? 0);

            if ($deltaSeconds > 0) {
                $distanceMeters = $this->distanceMeters(
                    (float) $latest->latitude,
                    (float) $latest->longitude,
                    (float) $previous->latitude,
                    (float) $previous->longitude
                );
                $impliedSpeedKph = ($distanceMeters / $deltaSeconds) * 3.6;
            }
        }

        $reportedSpeedKph = $location && $location->speed !== null ? (float) $location->speed : null;
        $tripDate = optional($trip->trip_date)?->toDateString();
        $todayManila = $nowManila->toDateString();
        $lastCheckinAt = $trip->checkins()->max('scan_time');
        $tripStartedAt = $trip->actual_start_time;

        // All flags are explicit and heuristic-based for client-side UX decisions.
        return [
            'location_stale' => $ageSeconds !== null && $ageSeconds > $staleAfter,
            'possible_offline_buffered_upload' => $ageSeconds !== null && $ageSeconds > $bufferedAfter,
            'device_clock_ahead' => $ageSeconds !== null && $ageSeconds < (-1 * $clockAheadTolerance),
            'gps_drift_suspected' => ($reportedSpeedKph !== null && $reportedSpeedKph > $maxSpeedKph)
                || ($impliedSpeedKph !== null && $impliedSpeedKph > $maxSpeedKph),
            'trip_day_rollover_risk' => $trip->status === 'ongoing'
                && $tripDate !== null
                && $tripDate < $todayManila,
            'probable_missed_checkins' => $tripStartedAt !== null
                && $tripStartedAt->copy()->setTimezone('Asia/Manila')->diffInMinutes($nowManila) >= 30
                && $lastCheckinAt === null,
            'meta' => [
                'location_age_seconds' => $ageSeconds,
                'reported_speed_kph' => $reportedSpeedKph,
                'implied_speed_kph' => $impliedSpeedKph !== null ? round($impliedSpeedKph, 2) : null,
                'trip_date' => $tripDate,
                'today_manila' => $todayManila,
            ],
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
