<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RouteDirectionsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Allow longer waypoint chains for routes with many pickup stops.
            'coordinates' => ['required', 'array', 'min:2', 'max:200'],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.0' => ['required', 'numeric', 'between:-180,180'],
            'coordinates.*.1' => ['required', 'numeric', 'between:-90,90'],
        ]);

        // Normalize and remove consecutive duplicate points to prevent provider errors.
        $coordinates = $this->sanitizeCoordinates($validated['coordinates']);
        if (count($coordinates) < 2) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'INVALID_COORDINATES',
                'message' => 'At least two distinct coordinates are required.',
            ], 422);
        }

        $apiKey = (string) config('services.openrouteservice.api_key');
        if ($apiKey === '') {
            return response()->json([
                'status' => 'error',
                'error_code' => 'ORS_NOT_CONFIGURED',
                'message' => 'OpenRouteService API key is not configured on server.',
            ], 503);
        }

        $baseUrl = rtrim((string) config('services.openrouteservice.base_url', 'https://api.openrouteservice.org'), '/');
        $url = $baseUrl . '/v2/directions/driving-car/geojson';

        try {
            // Retry once for transient upstream failures.
            $response = Http::timeout(10)
                ->retry(1, 400)
                ->withHeaders([
                    'Authorization' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'coordinates' => $coordinates,
                    // Give providers more room to snap slightly off-road stops.
                    'radiuses' => array_fill(0, count($coordinates), 1000),
                    'instructions' => false,
                ]);
        } catch (ConnectionException $e) {
            return $this->attemptOsrmFallback(
                $coordinates,
                [
                    'status' => 'error',
                    'error_code' => 'ORS_UNREACHABLE',
                    'message' => 'OpenRouteService is currently unreachable.',
                    'provider_error' => $e->getMessage(),
                ],
                503
            );
        }

        if (!$response->ok()) {
            $status = $response->status();
            $errorCode = match ($status) {
                401, 403 => 'ORS_AUTH_FAILED',
                429 => 'ORS_RATE_LIMITED',
                default => 'ORS_REQUEST_FAILED',
            };
            $providerBody = $response->json();

            return $this->attemptOsrmFallback(
                $coordinates,
                [
                    'status' => 'error',
                    'error_code' => $errorCode,
                    'message' => 'OpenRouteService request failed.',
                    'provider_status' => $status,
                    'provider_error' => is_array($providerBody) ? ($providerBody['error'] ?? $providerBody['message'] ?? null) : null,
                ],
                $status === 429 ? 429 : 502
            );
        }

        $feature = $response->json('features.0');
        $geometry = $feature['geometry']['coordinates'] ?? null;
        $summary = $feature['properties']['summary'] ?? [];

        if (!is_array($geometry) || count($geometry) < 2) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'ORS_EMPTY_ROUTE',
                'message' => 'No route geometry was returned by OpenRouteService.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'source' => 'openrouteservice',
                'geometry' => $geometry,
                'distance_meters' => isset($summary['distance']) ? (float) $summary['distance'] : null,
                'duration_seconds' => isset($summary['duration']) ? (float) $summary['duration'] : null,
            ],
        ]);
    }

    private function attemptOsrmFallback(array $coordinates, array $orsErrorPayload, int $orsStatus): JsonResponse
    {
        $osrm = $this->resolveOsrmRoute($coordinates);

        if ($osrm !== null) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'source' => 'osrm',
                    'geometry' => $osrm['geometry'],
                    'distance_meters' => $osrm['distance_meters'],
                    'duration_seconds' => $osrm['duration_seconds'],
                    'fallback_reason' => $orsErrorPayload,
                ],
            ]);
        }

        return response()->json($orsErrorPayload, $orsStatus);
    }

    private function resolveOsrmRoute(array $coordinates): ?array
    {
        $baseUrl = rtrim((string) config('services.osrm.base_url', 'https://router.project-osrm.org'), '/');
        $path = implode(';', array_map(
            fn (array $point) => $point[0] . ',' . $point[1],
            $coordinates
        ));

        try {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->get($baseUrl . '/route/v1/driving/' . $path, [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                    'steps' => 'false',
                    'alternatives' => 'false',
                    'annotations' => 'false',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $route = $response->json('routes.0');
        $geometry = $route['geometry']['coordinates'] ?? null;

        if (!is_array($geometry) || count($geometry) < 2) {
            return null;
        }

        return [
            'geometry' => $geometry,
            'distance_meters' => isset($route['distance']) ? (float) $route['distance'] : null,
            'duration_seconds' => isset($route['duration']) ? (float) $route['duration'] : null,
        ];
    }

    private function sanitizeCoordinates(array $coordinates): array
    {
        $sanitized = [];

        foreach ($coordinates as $point) {
            $lng = (float) $point[0];
            $lat = (float) $point[1];

            if (empty($sanitized)) {
                $sanitized[] = [$lng, $lat];
                continue;
            }

            $last = $sanitized[count($sanitized) - 1];
            // Skip points that are effectively identical (<2 meters apart).
            if ($this->distanceMeters($last[1], $last[0], $lat, $lng) < 2.0) {
                continue;
            }

            $sanitized[] = [$lng, $lat];
        }

        return $sanitized;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
