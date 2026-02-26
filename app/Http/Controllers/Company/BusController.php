<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class BusController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $query = Bus::query()
            ->with([
                'assignments' => function ($q) use ($companyId, $user) {
                    if ($companyId && !$user->hasRole('super_admin')) {
                        $q->where('company_id', $companyId);
                    }

                    $q->active()
                        ->with(['driver.user', 'route'])
                        ->latest('effective_from');
                },
            ])
            ->latest();

        if ($companyId && !$user->hasRole('super_admin')) {
            $query->whereHas('assignments', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->active();
            });
        } elseif (!$user->hasRole('super_admin')) {
            // Safety: non-super-admin users without company scope should see no buses.
            $query->whereRaw('1 = 0');
        }

        $buses = $query->get();

        return view('company.buses.index', compact('buses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate_number' => ['required', 'string', 'max:50', 'unique:buses,plate_number'],
            'bus_code'     => ['required', 'string', 'max:50'],
            'capacity'     => ['required', 'integer', 'min:1'],
            'brand_model'  => ['nullable', 'string', 'max:100'],
            'status'       => ['required', 'in:active,maintenance,inactive'],
            'photo'        => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['bus_code'] = Hash::make($validated['bus_code']);
        $bus = Bus::create(collect($validated)->except('photo')->toArray());

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/buses', $filename);
            $bus->update(['photo' => $filename]);
        }

        return redirect()
            ->route('company.buses.index')
            ->with('success', 'Bus created successfully.');
    }

    public function update(Request $request, Bus $bus): RedirectResponse
    {
        $user = auth()->user();
        $companyId = (int) ($user?->company_id ?? 0);
        $this->abortIfBusOutsideCompany($bus, $companyId, $user?->hasRole('super_admin'));

        $validated = $request->validate([
            'plate_number' => [
                'required',
                'string',
                'max:50',
                'unique:buses,plate_number,' . $bus->id,
            ],
            'bus_code'    => ['nullable', 'string', 'max:50'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'brand_model' => ['nullable', 'string', 'max:100'],
            'status'      => ['required', 'in:active,maintenance,inactive'],
            'photo'       => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->filled('bus_code')) {
            $validated['bus_code'] = Hash::make($request->input('bus_code'));
        } else {
            unset($validated['bus_code']);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $plate = strtolower((string) $request->input('plate_number'));
            $plate = preg_replace('/[^a-z0-9\-]/i', '-', $plate);
            $extension = $file->getClientOriginalExtension();
            $filename = "bus-{$plate}-" . time() . ".{$extension}";
            $file->storeAs('public/buses', $filename);

            if ($bus->photo && Storage::disk('public')->exists('buses/' . $bus->photo)) {
                Storage::disk('public')->delete('buses/' . $bus->photo);
            }

            $validated['photo'] = $filename;
        }

        $bus->update($validated);

        return redirect()
            ->route('company.buses.index')
            ->with('success', 'Bus updated successfully.');
    }

    public function json(Bus $bus): JsonResponse
    {
        $user = auth()->user();
        $companyId = (int) ($user?->company_id ?? 0);
        $this->abortIfBusOutsideCompany($bus, $companyId, $user?->hasRole('super_admin'));

        $bus->load([
            'assignments' => function ($q) use ($companyId, $user) {
                if ($companyId && !$user->hasRole('super_admin')) {
                    $q->where('company_id', $companyId)->active();
                }

                $q->with(['driver.user', 'route']);
            },
            'latestGps',
            'activeTrip.latestLocation',
        ]);

        $bus->setAttribute('photo_url', $bus->photo_url);
        $lastLocation = $bus->currentLocation();
        $bus->setAttribute('last_location', $lastLocation ? [
            'latitude' => (float) $lastLocation->latitude,
            'longitude' => (float) $lastLocation->longitude,
            'tracked_at' => optional($lastLocation->tracked_at)->toDateTimeString(),
        ] : null);

        return response()->json($bus);
    }

    private function abortIfBusOutsideCompany(Bus $bus, int $companyId, bool $isSuperAdmin): void
    {
        if ($isSuperAdmin) {
            return;
        }

        if ($companyId <= 0) {
            abort(403);
        }

        $belongsToCompany = $bus->assignments()
            ->where('company_id', $companyId)
            ->active()
            ->exists();

        if (!$belongsToCompany) {
            abort(403);
        }
    }
}
