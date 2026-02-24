<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class BusController extends Controller
{
    /**
     * Display a listing of buses.
     */
    public function index(): View
    {
        $buses = Bus::query()
            ->with([
                'assignments.driver.user',
                'assignments.route',
            ])
            ->latest()
            ->get();

        return view('admin.buses.index', compact('buses'));
    }

    /**
     * Store a newly created bus.
     * Used by CREATE MODAL
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate_number' => ['required', 'string', 'max:50', 'unique:buses,plate_number'],
            'bus_code'     => ['required', 'string', 'max:50'],
            'capacity'     => ['required', 'integer', 'min:1'],
            'brand_model'  => ['nullable', 'string', 'max:100'],
            'status'       => ['required', 'in:active,maintenance,inactive'],

            // ✅ photo support
            'photo'        => ['nullable', 'image', 'max:2048'], // 2MB
        ]);

        // hash bus_code
        $validated['bus_code'] = Hash::make($validated['bus_code']);

        // Create bus first (so we have an ID if you want to use it later)
        $bus = Bus::create(collect($validated)->except('photo')->toArray());

        // ✅ handle photo upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // stores in storage/app/public/buses
            $file->storeAs('public/buses', $filename);

            $bus->update(['photo' => $filename]);
        }

        return redirect()
            ->route('admin.buses.index')
            ->with('success', 'Bus created successfully.');
    }

    /**
     * Show bus as JSON
     * Used by VIEW MODAL (AJAX)
     */
    public function json(Bus $bus): JsonResponse
    {
        $bus->load([
            'assignments.driver.user',
            'assignments.route',
            'latestGps',
            'activeTrip.latestLocation',
        ]);

        // If your model has `$appends = ['photo_url']`, this is automatic.
        // If not, force include it:
        $bus->setAttribute('photo_url', $bus->photo_url);

        $lastLocation = $bus->currentLocation();
        $bus->setAttribute('last_location', $lastLocation ? [
            'latitude' => (float) $lastLocation->latitude,
            'longitude' => (float) $lastLocation->longitude,
            'tracked_at' => optional($lastLocation->tracked_at)->toDateTimeString(),
        ] : null);

        return response()->json($bus);
    }

    /**
     * Display the specified bus (FULL PAGE – optional)
     */
    public function show(Bus $bus): View
    {
        $bus->load([
            'assignments.driver.user',
            'assignments.company',
            'assignments.route',
        ]);

        return view('admin.buses.show', compact('bus'));
    }

    /**
     * Update the specified bus.
     * Used by EDIT MODAL
     */
public function update(Request $request, Bus $bus): RedirectResponse
{

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

    /**
     * ✅ BUS CODE
     * Only update if user entered a new value
     * (never re-submit hashed value)
     */
    if ($request->filled('bus_code')) {
        $validated['bus_code'] = Hash::make($request->input('bus_code'));
    } else {
        unset($validated['bus_code']);
    }

    /**
     * ✅ PHOTO UPLOAD
     */
   if ($request->hasFile('photo')) {
    $file = $request->file('photo');

    // 1️⃣ Sanitize plate number (VERY IMPORTANT)
    $plate = strtolower($request->input('plate_number'));
    $plate = preg_replace('/[^a-z0-9\-]/i', '-', $plate);

    // 2️⃣ Keep extension
    $extension = $file->getClientOriginalExtension();

    // 3️⃣ Prevent overwrite by adding timestamp
    $filename = "bus-{$plate}-" . time() . ".{$extension}";

    // 4️⃣ Store file
    $file->storeAs('public/buses', $filename);

    // 5️⃣ Delete old photo if exists
    if ($bus->photo && Storage::disk('public')->exists('buses/' . $bus->photo)) {
        Storage::disk('public')->delete('buses/' . $bus->photo);
    }

    // 6️⃣ Save new filename to DB
    $validated['photo'] = $filename;
}

    // ✅ SAVE EVERYTHING
    $bus->update($validated);

    return redirect()
        ->route('admin.buses.index')
        ->with('success', 'Bus updated successfully.');
}

}
