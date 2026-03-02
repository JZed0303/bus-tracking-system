<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class DriverController extends Controller
{
    private function normalizeDriverStatus(string $status): string
    {
        // Backward compatibility for legacy UI value.
        return $status === 'inactive' ? 'suspended' : $status;
    }

    /**
     * Display a listing of drivers.
     * URL: /admin/drivers
     */
    public function index(): View
    {
        $drivers = Driver::query()
            ->with([
                'user',
                'currentAssignment.bus',
                'currentAssignment.route',
                'currentAssignment.company',
            ])
            ->when(request('company_id'), function ($q) {
                $q->whereHas('currentAssignment', function ($q2) {
                    $q2->where('company_id', request('company_id'));
                });
            })
            ->when(request('status'), function ($q) {
                $status = request('status') === 'inactive' ? 'suspended' : request('status');
                $q->where('status', $status);
            })
            ->latest()
            ->get();

        $companies = Company::orderBy('name')->get();

        return view('admin.drivers.index', compact('drivers', 'companies'));
    }

    /**
     * Display the specified driver profile.
     * URL: /admin/drivers/{driver}
     */
  public function show(Driver $driver): View
{
    $driver->load([
        'user',
        'company',
        'currentAssignment.bus',
        'currentAssignment.route',
        'currentAssignment.company',
    ]);

    // Recent assignments (latest 5)
    $recentAssignments = $driver->assignments()
        ->with(['bus', 'route', 'company'])
        ->latest('effective_from')
        ->limit(5)
        ->get();

    // Recent trips (latest 10)
    $recentTrips = $driver->trips()
        ->with(['assignment.bus', 'assignment.route']) // adjust if your Trip relations differ
        ->latest('actual_start_time')                   // adjust column if needed
        ->limit(10)
        ->get();

    $companies = Company::orderBy('name')->get();

    return view('admin.drivers.show', compact(
        'driver',
        'companies',
        'recentAssignments',
        'recentTrips'
    ));
}

    /**
     * Store a newly created driver.
     * POST /admin/drivers
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'     => ['required', 'exists:companies,id'],
            'first_name'     => ['required', 'string', 'max:100'],
            'middle_name'    => ['nullable', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'license_number' => ['required', 'string', 'unique:drivers,license_number'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'status'         => ['required', 'in:active,on_leave,suspended,inactive'],

            // Optional driver photo (FilePond storeAsFile -> normal upload)
            'photo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {

                // Create user account for driver
                $user = User::create([
                    'first_name'  => $validated['first_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name'   => $validated['last_name'],
                    'email'       => $validated['email'],
                    'password'    => Hash::make('password123'), // temporary password
                    'role'        => 'driver',
                ]);

                // Photo upload (optional)
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('drivers', 'public');
                }

                // Create driver profile
                Driver::create([
                    'user_id'        => $user->id,
                    'company_id'     => $validated['company_id'],
                    'license_number' => $validated['license_number'],
                    'phone'          => $validated['phone'] ?? null,
                    'status'         => $this->normalizeDriverStatus($validated['status']),

                    // IMPORTANT: ensure your drivers table has this column (nullable)
                    'photo_path'     => $photoPath,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.drivers.index')
                ->withInput()
                ->with('error', 'Unable to save driver. Please check input and try again.');
        }

        return redirect()
            ->route('admin.drivers.index')
            ->with('success', 'Driver created successfully.');
    }

    /**
     * Update the specified driver.
     * PUT /admin/drivers/{driver}
     */
    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $driver->load('user');

        $validated = $request->validate([
            'company_id'     => ['required', 'exists:companies,id'],
            'first_name'     => ['required', 'string', 'max:100'],
            'middle_name'    => ['nullable', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'email'          => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($driver->user_id),
            ],
            'license_number' => [
                'required',
                'string',
                Rule::unique('drivers', 'license_number')->ignore($driver->id),
            ],
            'phone'          => ['nullable', 'string', 'max:20'],
            'status'         => ['required', 'in:active,on_leave,suspended,inactive'],

            // Optional replace photo
            'photo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($driver, $validated, $request) {

            // 1) Update USER
            $driver->user->update([
                'first_name'  => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
            ]);

            // 2) Update DRIVER (non-photo)
            $driver->update([
                'company_id'     => $validated['company_id'],
                'license_number' => $validated['license_number'],
                'phone'          => $validated['phone'] ?? null,
                'status'         => $this->normalizeDriverStatus($validated['status']),
            ]);

            // 3) Photo replace (optional)
            if ($request->hasFile('photo')) {

                // Delete old if exists
                if (!empty($driver->photo_path) && Storage::disk('public')->exists($driver->photo_path)) {
                    Storage::disk('public')->delete($driver->photo_path);
                }

                // Store new
                $path = $request->file('photo')->store('drivers', 'public');

                // Save new path
                $driver->update([
                    'photo_path' => $path,
                ]);
            }
        });

        return redirect()
            ->route('admin.drivers.index')
            ->with('success', 'Driver updated successfully.');
    }
}
