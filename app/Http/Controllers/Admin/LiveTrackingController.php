<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Bus\LiveBusMapResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LiveTrackingController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $query = Trip::query()
            ->where('status', 'ongoing')
            ->withCount('checkins')
            ->with([
                'latestLocation',
                'assignment.bus',
                'assignment.driver.user',
                'assignment.company',
                'assignment.route.stops',
            ]);

        /**
         * =========================================================
         * COMPANY SCOPE
         * =========================================================
         * If the logged-in user is a company_admin, we restrict
         * trips to those whose assignment belongs to his/her
         * company_id ONLY.
         */
        if ($user && $user->hasRole('company_admin') && $user->company_id) {
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $trips = $query->get();

        $onboardCounts = collect();
        if ($trips->isNotEmpty()) {
            $tripIds = $trips->pluck('id')->values();

            $onboardCounts = DB::table('checkins as c')
                ->selectRaw('c.trip_id, COUNT(DISTINCT c.employee_id) as onboard_count')
                ->whereIn('c.trip_id', $tripIds)
                ->where('c.scan_type', 'checkin')
                ->whereNull('c.voided_at')
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('checkins as c2')
                        ->whereColumn('c2.trip_id', 'c.trip_id')
                        ->whereColumn('c2.employee_id', 'c.employee_id')
                        ->where('c2.scan_type', 'checkout')
                        ->whereNull('c2.voided_at');
                })
                ->groupBy('c.trip_id')
                ->pluck('onboard_count', 'trip_id');
        }

        $buses = $trips->map(function ($trip) use ($onboardCounts) {

            // Guard: skip trips that don't have all required relations
            if (
                !$trip->latestLocation ||
                !$trip->assignment ||
                !$trip->assignment->bus ||
                !$trip->assignment->company ||
                !$trip->assignment->route ||
                !$trip->assignment->driver
            ) {
                return null;
            }

            $capacity = (int) ($trip->assignment->bus->capacity ?? 0);
            $onboardCount = (int) ($onboardCounts[$trip->id] ?? 0);
            $availableCapacity = max(0, $capacity - $onboardCount);

            return [
                'trip_id'        => $trip->id,
                'status'         => $trip->status,
                'lat'            => (float) $trip->latestLocation->latitude,
                'lng'            => (float) $trip->latestLocation->longitude,
                'speed'          => $trip->latestLocation->speed ?? 0,

                'bus_id'         => (int) $trip->assignment->bus_id,
                'plate_number'   => $trip->assignment->bus->plate_number ?? 'N/A',
                'bus'            => $trip->assignment->bus->plate_number ?? 'N/A', // backward compatibility
                'driver'         => $trip->assignment->driver->user->full_name ?? 'N/A',
                'driver_user_id' => $trip->assignment->driver->user->id ?? null,
                'company'        => $trip->assignment->company->name ?? 'N/A',
                'route'          => $trip->assignment->route->name ?? 'N/A',
                'route_start'    => (function () use ($trip) {
                    $startLat = isset($trip->assignment->route->start_lat) ? (float) $trip->assignment->route->start_lat : null;
                    $startLng = isset($trip->assignment->route->start_lng) ? (float) $trip->assignment->route->start_lng : null;

                    if (!is_finite($startLat) || !is_finite($startLng)) {
                        return null;
                    }

                    return [
                        'label' => (string) ($trip->assignment->route->start_location ?? 'Start'),
                        'lat' => $startLat,
                        'lng' => $startLng,
                    ];
                })(),
                'route_end'      => (function () use ($trip) {
                    $endLat = isset($trip->assignment->route->end_lat) ? (float) $trip->assignment->route->end_lat : null;
                    $endLng = isset($trip->assignment->route->end_lng) ? (float) $trip->assignment->route->end_lng : null;

                    if (!is_finite($endLat) || !is_finite($endLng)) {
                        return null;
                    }

                    return [
                        'label' => (string) ($trip->assignment->route->end_location ?? 'End'),
                        'lat' => $endLat,
                        'lng' => $endLng,
                    ];
                })(),
                'route_stops'    => $trip->assignment->route->stops
                    ->map(function ($stop) {
                        $lat = isset($stop->latitude) ? (float) $stop->latitude : null;
                        $lng = isset($stop->longitude) ? (float) $stop->longitude : null;

                        if (!is_finite($lat) || !is_finite($lng)) {
                            return null;
                        }

                        return [
                            'id' => (int) $stop->id,
                            'address' => (string) ($stop->address ?? 'Stop'),
                            'lat' => $lat,
                            'lng' => $lng,
                            'stop_order' => (int) ($stop->stop_order ?? 0),
                        ];
                    })
                    ->filter()
                    ->values(),

                'employee_count' => (int) ($trip->checkins_count ?? 0),
                'onboard_count' => $onboardCount,
                'bus_capacity' => $capacity,
                'available_capacity' => $availableCapacity,
                'updated_at'     => $trip->latestLocation->tracked_at->toDateTimeString(),
            ];
        })
        ->filter()      // remove nulls from skipped trips
        ->values();     // reindex

        /**
         * =========================================================
         * DEMO FALLBACK (ONLY FOR SUPER_ADMIN / LOCAL ENV)
         * =========================================================
         * To avoid showing fake buses to company admins, we limit
         * fallback to super_admin or local environment.
         */
        if ($buses->isEmpty()) {
            if ($user && $user->hasRole('super_admin')) {
                $buses = collect([
                    [
                        'trip_id'        => 1001,
                        'status'         => 'ongoing',
                        'lat'            => 14.329012,
                        'lng'            => 121.045234,
                        'speed'          => 35,
                        'bus_id'         => 1001,
                        'plate_number'   => 'ABC-1234',
                        'bus'            => 'ABC-1234',
                        'driver'         => 'Juan Dela Cruz',
                        'driver_user_id' => null,
                        'company'        => 'ABC Manufacturing Corp',
                        'route'          => 'Carmona Morning Route',
                        'route_start'    => null,
                        'route_end'      => null,
                        'route_stops'    => [],
                        'employee_count' => 18,
                        'onboard_count' => 16,
                        'bus_capacity' => 40,
                        'available_capacity' => 24,
                        'updated_at'     => now()->toDateTimeString(),
                    ],
                   
                ]);
            } else {
                // For company_admin: just return empty list (no fake buses)
                $buses = collect();
            }
        }

        $incidentQuery = Trip::query()
            ->whereNotNull('incident_reported_at')
            ->with([
                'assignment.bus',
                'assignment.company',
                'assignment.route',
            ])
            ->latest('incident_reported_at')
            ->limit(25);

        if ($user && $user->hasRole('company_admin') && $user->company_id) {
            $incidentQuery->whereHas('assignment', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $incidents = $incidentQuery->get()->map(function (Trip $trip) {
            return [
                'trip_id' => (int) $trip->id,
                'bus_id' => (int) ($trip->assignment?->bus_id ?? 0),
                'plate_number' => $trip->assignment?->bus?->plate_number,
                'company' => $trip->assignment?->company?->name,
                'route' => $trip->assignment?->route?->name,
                'incident_type' => $trip->ended_reason,
                'reason' => $trip->incident_reason,
                'reported_at' => optional($trip->incident_reported_at)?->toIso8601String(),
                'status' => $trip->status,
            ];
        })->values();

        return response()->json([
            'data' => LiveBusMapResource::collection($buses)->resolve(),
            'incidents' => $incidents,
        ]);
    }
}
