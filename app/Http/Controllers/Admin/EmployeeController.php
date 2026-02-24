<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Trip;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * List employees
     */
    public function index(Request $request): View
    {
        $employees = Employee::with(['user', 'company', 'qrcode'])
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->department, function ($q) use ($request) {
                $q->where('department', 'like', "%{$request->department}%");
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->get();

        $companies = Company::orderBy('name')->get();

        return view('admin.employees.index', compact('employees', 'companies'));
    }

    /**
     * Create page
     */
    public function create(): View
    {
        $companies = Company::orderBy('name')->get();

        return view('admin.employees.create', compact('companies'));
    }

    /**
     * Store employee
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'    => ['required', 'exists:companies,id'],
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'employee_code' => ['required', 'string', 'unique:employees,employee_code'],
            'department'    => ['nullable', 'string', 'max:100'],
            'status'        => ['required', 'in:active,suspended,resigned'],

            // ✅ profile photo
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $validated) {

            // Create USER
            $user = User::create([
                'first_name'  => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
                'password'    => bcrypt('password123'), // temporary
                'status'      => 'active',
                'role'        => 'employee', // ✅ keep consistent
            ]);

            // Spatie role
            $user->assignRole('employee');

            // Handle photo
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('employees/photos', 'public');
            }

            // Create EMPLOYEE
            Employee::create([
                'user_id'       => $user->id,
                'company_id'    => $validated['company_id'],
                'employee_code' => $validated['employee_code'],
                'department'    => $validated['department'] ?? null,
                'status'        => $validated['status'],
                'photo_path'    => $photoPath,
            ]);
        });

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Show employee profile
     */
public function show(Employee $employee): \Illuminate\Contracts\View\View
{
    $employee->load(['user', 'company', 'qrcode', 'checkins.trip']);

    $checkinsForMap = $employee->checkins()
        ->select('id', 'scan_type', 'scan_time', 'scan_lat', 'scan_lng', 'trip_id')
        ->whereNotNull('scan_lat')
        ->whereNotNull('scan_lng')
        ->orderBy('scan_time', 'asc')
        ->get();

    return view('admin.employees.show', [
        'employee'        => $employee,
        'checkinCount'    => $employee->checkins()->count(),
        'lastCheckin'     => $employee->checkins()->latest('scan_time')->first(),
        'checkinsForMap'  => $checkinsForMap,
    ]);
}
    /**
     * Edit page (if you still use separate edit page)
     */
    public function edit(Employee $employee): View
    {
        $companies = Company::orderBy('name')->get();

        return view('admin.employees.edit', compact('employee', 'companies'));
    }

    /**
     * Update employee (with profile photo support)
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            // user
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email,' . $employee->user_id],

            // employee
            'company_id'    => ['required', 'exists:companies,id'],
            'employee_code' => ['required', 'string', 'unique:employees,employee_code,' . $employee->id],
            'department'    => ['nullable', 'string', 'max:100'],
            'status'        => ['required', 'in:active,suspended,resigned'],

            // ✅ profile photo
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $employee, $validated) {

            // Update USER
            $employee->user->update([
                'first_name'  => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
            ]);

            // Handle photo (replace old only if new uploaded)
            $photoPath = $employee->photo_path;

            if ($request->hasFile('photo')) {

                if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }

                $photoPath = $request->file('photo')->store('employees/photos', 'public');
            }

            // Update EMPLOYEE
            $employee->update([
                'company_id'    => $validated['company_id'],
                'employee_code' => $validated['employee_code'],
                'department'    => $validated['department'] ?? null,
                'status'        => $validated['status'],
                'photo_path'    => $photoPath,
            ]);
        });

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

}
