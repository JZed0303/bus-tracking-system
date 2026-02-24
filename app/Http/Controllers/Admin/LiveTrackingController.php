<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Bus\LiveBusMapResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;

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
                'assignment.route',
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

        $buses = $trips->map(function ($trip) {

            // Guard: skip trips that don't have all required relations
            if (
                !$trip->latestLocation ||
                !$trip->assignment ||
                !$trip->assignment->company ||
                !$trip->assignment->route ||
                !$trip->assignment->driver
            ) {
                return null;
            }

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
                'company'        => $trip->assignment->company->name ?? 'N/A',
                'route'          => $trip->assignment->route->name ?? 'N/A',

                'employee_count' => (int) ($trip->checkins_count ?? 0),
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
                        'company'        => 'ABC Manufacturing Corp',
                        'route'          => 'Carmona Morning Route',
                        'employee_count' => 18,
                        'updated_at'     => now()->toDateTimeString(),
                    ],
                   
                ]);
            } else {
                // For company_admin: just return empty list (no fake buses)
                $buses = collect();
            }
        }

        return response()->json([
            'data' => LiveBusMapResource::collection($buses)->resolve(),
        ]);
    }
}
