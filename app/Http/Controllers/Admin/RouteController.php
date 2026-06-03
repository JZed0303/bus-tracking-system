<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportRoute;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    /* =============================
     * INDEX
     * ============================= */
  public function index(Request $request): View
{
    $routes = TransportRoute::query()
        ->with(['company'])
        ->withCount([
            'stops',
            'activeTrips',
            'assignments as active_assignments_count' => fn ($q) => $q->active(),
        ])

        ->when($request->company_id, fn ($q) =>
            $q->where('company_id', $request->company_id)
        )

        ->when($request->status, fn ($q) =>
            $q->where('status', $request->status)
        )

        // ✅ HAS ACTIVE TRIPS
        ->when($request->has_trips === '1', fn ($q) =>
            $q->whereHas('activeTrips')
        )

        // ✅ NO ACTIVE TRIPS
        ->when($request->has_trips === '0', fn ($q) =>
            $q->whereDoesntHave('activeTrips')
        )

        ->orderBy('name')
        ->get();

    $companies = Company::orderBy('name')->get();

    return view('admin.routes.index', compact('routes', 'companies'));
}



    /* =============================
     * CREATE
     * ============================= */
    public function create(): View
    {
        $companies = Company::orderBy('name')->get();
        return view('admin.routes.create', compact('companies'));
    }

    /* =============================
     * STORE
     * ============================= */
    public function store(Request $request): RedirectResponse
    {


        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated, $request, &$route) {
            $route = TransportRoute::create(
                collect($validated)->except(['stops_json', 'route_geometry_json'])->toArray()
            );

            $this->syncStops($route, $request->input('stops_json'));
            $this->persistRouteGeometry($route, $request->input('route_geometry_json'));
        });

        return redirect()
            ->route('admin.routes.show', $route->id)
            ->with('success', 'Route created successfully.');
    }

    /* =============================
     * SHOW
     * ============================= */
    public function show(TransportRoute $route): View
    {
        $route->load([
            'company',
            'stops' => fn ($q) => $q->orderBy('stop_order'),
        ]);

        return view('admin.routes.show', compact('route'));
    }

    public function preview(TransportRoute $route): View
    {
        $route->load([
            'company',
            'stops' => fn ($q) => $q->orderBy('stop_order'),
        ]);

        $routeGeometryJson = $this->getRouteGeometryGeoJson($route);

        return view('admin.routes.preview', compact('route', 'routeGeometryJson'));
    }

    /* =============================
     * EDIT
     * ============================= */
    public function edit(TransportRoute $route): View
{


    $companies = Company::orderBy('name')->get();

    $route->load('stops');

    $stopsForJs = $route->stops
        ->sortBy('stop_order')
        ->map(function ($s) {
            return [
                'latitude'   => $s->latitude,
                'longitude'  => $s->longitude,
                'address'    => $s->address,
                'stop_order' => $s->stop_order,
            ];
        })
        ->values();
    $routeGeometryJson = $this->getRouteGeometryGeoJson($route);


    return view('admin.routes.edit', compact('route', 'companies', 'stopsForJs', 'routeGeometryJson'));
}

    /* =============================
     * UPDATE
     * ============================= */
    public function update(Request $request, TransportRoute $route): RedirectResponse
    {
        $request->validate([
            // Optimistic lock token to avoid silent last-write-wins conflicts.
            'route_updated_at' => ['required', 'date'],
        ]);

        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated, $request, $route) {
            // Lock row and re-check timestamp in the same transaction for race safety.
            $lockedRoute = TransportRoute::query()->whereKey($route->id)->lockForUpdate()->firstOrFail();
            $clientUpdatedAt = strtotime((string) $request->input('route_updated_at'));
            $dbUpdatedAt = optional($lockedRoute->updated_at)->timestamp;

            if ($dbUpdatedAt !== null && $clientUpdatedAt !== false && $dbUpdatedAt !== $clientUpdatedAt) {
                throw ValidationException::withMessages([
                    'route_updated_at' => 'This route was modified by another user. Refresh first to avoid overwriting changes.',
                ]);
            }

            $lockedRoute->update(
                collect($validated)->except(['stops_json', 'route_geometry_json'])->toArray()
            );

            $this->syncStops($lockedRoute, $request->input('stops_json'));
            $this->persistRouteGeometry($lockedRoute, $request->input('route_geometry_json'));
        });

        return redirect()
            ->route('admin.routes.show', $route->id)
            ->with('success', 'Route updated successfully.');
    }

    /* =============================
     * DESTROY
     * ============================= */
    public function destroy(TransportRoute $route): RedirectResponse
    {
        $hasActiveTrips = $route->activeTrips()->exists();
        $hasActiveAssignments = $route->assignments()->active()->exists();

        if ($hasActiveTrips || $hasActiveAssignments) {
            return redirect()
                ->route('admin.routes.index')
                ->with('error', 'Cannot delete route with active trip(s) or assignment(s).');
        }

        $route->delete();

        return redirect()
            ->route('admin.routes.index')
            ->with('success', 'Route deleted successfully.');
    }

    /* =============================
     * VALIDATION
     * ============================= */
    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'company_id'     => ['nullable', 'exists:companies,id'],

            'start_location' => ['required', 'string', 'max:255'],
            'start_lat'      => ['required', 'numeric'],
            'start_lng'      => ['required', 'numeric'],

            'end_location'   => ['required', 'string', 'max:255'],
            'end_lat'        => ['required', 'numeric'],
            'end_lng'        => ['required', 'numeric'],

            'description'    => ['nullable', 'string'],
            'status'         => ['required', 'in:active,inactive'],

            'stops_json'     => ['nullable', 'string'],
            'route_geometry_json' => ['nullable', 'string'],
        ]);
    }

    /* =============================
     * STOP SYNC
     * ============================= */
    protected function syncStops(TransportRoute $route, ?string $stopsJson): void
    {
        // Always reset stops
        $route->stops()->delete();

        if (!$stopsJson) {
            return;
        }

        $stops = json_decode($stopsJson, true);

        if (!is_array($stops)) {
            return;
        }

        // Normalize stop payload and drop invalid entries.
        $normalizedStops = collect($stops)
            ->filter(fn ($stop) => is_array($stop))
            ->map(function (array $stop): array {
                return [
                    'stop_order' => isset($stop['stop_order']) ? (int) $stop['stop_order'] : 0,
                    'address' => isset($stop['address']) && is_string($stop['address']) ? trim($stop['address']) : null,
                    'latitude' => isset($stop['latitude']) ? (float) $stop['latitude'] : null,
                    'longitude' => isset($stop['longitude']) ? (float) $stop['longitude'] : null,
                ];
            })
            ->filter(function (array $stop): bool {
                return $stop['latitude'] !== null
                    && $stop['longitude'] !== null
                    && $stop['latitude'] >= -90 && $stop['latitude'] <= 90
                    && $stop['longitude'] >= -180 && $stop['longitude'] <= 180;
            })
            ->sortBy('stop_order')
            ->values();

        $dedupedStops = [];
        foreach ($normalizedStops as $stop) {
            $isTooClose = collect($dedupedStops)->contains(function (array $existing) use ($stop): bool {
                // Prevent near-duplicate stop collisions that cause route geometry glitches.
                return $this->distanceMeters(
                    $existing['latitude'],
                    $existing['longitude'],
                    $stop['latitude'],
                    $stop['longitude']
                ) < 8.0;
            });

            if ($isTooClose) {
                continue;
            }

            $dedupedStops[] = $stop;
        }

        foreach (array_values($dedupedStops) as $index => $stop) {
            $route->stops()->create([
                // Reindex order to guarantee deterministic stop sequence.
                'stop_order' => $index + 1,
                'address'    => $stop['address'] ?: 'Stop ' . ($index + 1),
                'latitude'   => $stop['latitude'],
                'longitude'  => $stop['longitude'],
            ]);
        }
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

    private function getRouteGeometryGeoJson(TransportRoute $route): ?string
    {
        return DB::table('routes')
            ->where('id', $route->id)
            ->selectRaw('ST_AsGeoJSON(geom) as geojson')
            ->value('geojson');
    }

    private function persistRouteGeometry(TransportRoute $route, ?string $routeGeometryJson): void
    {
        $payload = is_string($routeGeometryJson) ? trim($routeGeometryJson) : '';

        if ($payload === '') {
            DB::table('routes')->where('id', $route->id)->update(['geom' => null]);
            return;
        }

        $decoded = json_decode($payload, true);
        $coordinates = $decoded['coordinates'] ?? null;

        if (($decoded['type'] ?? null) !== 'LineString' || !is_array($coordinates) || count($coordinates) < 2) {
            DB::table('routes')->where('id', $route->id)->update(['geom' => null]);
            return;
        }

        DB::statement(
            'UPDATE routes SET geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?',
            [$payload, $route->id]
        );
    }

}
