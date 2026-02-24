<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Assignment;
use App\Models\Bus;
use App\Models\TransportRoute;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;


class DriverAssignmentController extends Controller
{
    /**
     * Show driver assignment page
     * GET /admin/drivers/{driver}/assignment
     */
   public function show(Driver $driver): View
{
    $driver->load([
        'user',
        'currentAssignment.bus',
        'currentAssignment.route',
        'currentAssignment.company',
    ]);

    return view('admin.drivers.assignment', compact('driver'));
}

    /**
     * Store assignment for a specific driver
     * POST /admin/drivers/{driver}/assignment
     */
    public function store(Request $request, Driver $driver): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'         => ['required', 'exists:buses,id'],
            'route_id'       => ['required', 'exists:routes,id'],
            'company_id'     => ['required', 'exists:companies,id'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        // End previous active assignment
        Assignment::where('driver_id', $driver->id)
            ->where('status', 'active')
            ->update([
                'status'        => 'inactive',
                'effective_to'  => now(),
            ]);

        Assignment::create([
            'driver_id'      => $driver->id,
            'bus_id'         => $validated['bus_id'],
            'route_id'       => $validated['route_id'],
            'company_id'     => $validated['company_id'],
            'effective_from' => $validated['effective_from'],
            'effective_to'   => $validated['effective_to'],
            'status'         => 'active',
        ]);

        return redirect()
            ->route('admin.drivers.assignment', $driver->id)
            ->with('success', 'Driver assignment updated successfully.');
    }
}
