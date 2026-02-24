<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportRoute;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    /* =============================
     * INDEX
     * ============================= */
  public function index(Request $request): View
{
    $routes = TransportRoute::query()
        ->with(['company'])
        ->withCount(['stops', 'activeTrips'])

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
                collect($validated)->except('stops_json')->toArray()
            );

            $this->syncStops($route, $request->input('stops_json'));
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


    return view('admin.routes.edit', compact('route', 'companies', 'stopsForJs'));
}

    /* =============================
     * UPDATE
     * ============================= */
    public function update(Request $request, TransportRoute $route): RedirectResponse
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated, $request, $route) {
            $route->update(
                collect($validated)->except('stops_json')->toArray()
            );

            $this->syncStops($route, $request->input('stops_json'));
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

        foreach ($stops as $stop) {
            $route->stops()->create([
                'stop_order' => $stop['stop_order'],
                'address'    => $stop['address'] ?? 'Stop ' . $stop['stop_order'],
                'latitude'   => $stop['latitude'],
                'longitude'  => $stop['longitude'],
            ]);
        }
    }

}
