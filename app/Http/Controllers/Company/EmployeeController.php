<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * List employees for the authenticated user's company.
     */
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        $employees = Employee::with(['user', 'company', 'qrcode'])
            ->where('company_id', $companyId)
            ->when($request->department, function ($q) use ($request) {
                $q->where('department', 'like', "%{$request->department}%");
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->get();

        // Optional: you can still load the single company if the view needs it
        $company = Company::find($companyId);

        return view('company.employees.index', [
            'employees' => $employees,
            'company'   => $company,
        ]);
    }

    /**
     * Create page (employee under this company only).
     */
    public function create(): View
    {
        $companyId = auth()->user()->company_id;

        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        $company = Company::findOrFail($companyId);

        return view('company.employees.create', [
            'company' => $company,
        ]);
    }

    /**
     * Store employee for this company.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()->company_id;

        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        $validated = $request->validate([
            // company_id removed from request, forced from auth()
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'employee_code' => ['required', 'string', 'unique:employees,employee_code'],
            'department'    => ['nullable', 'string', 'max:100'],
            'status'        => ['required', 'in:active,suspended,resigned'],

            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $validated, $companyId) {

            // Create USER
            $user = User::create([
                'first_name'  => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
                'password'    => bcrypt('password123'), // temporary
                'status'      => 'active',
                'role'        => 'employee',
                'company_id'  => $companyId, // optional but useful if your User has company_id
            ]);

            // Spatie role
            $user->assignRole('employee');

            // Handle photo
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('employees/photos', 'public');
            }

            // Create EMPLOYEE under this company
            Employee::create([
                'user_id'       => $user->id,
                'company_id'    => $companyId,
                'employee_code' => $validated['employee_code'],
                'department'    => $validated['department'] ?? null,
                'status'        => $validated['status'],
                'photo_path'    => $photoPath,
            ]);
        });

        return redirect()
            ->route('company.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Show employee profile (must belong to this company).
     */
    public function show(Request $request, Employee $employee): View
    {
        $this->authorizeCompany($employee);

        $employee->load(['user', 'company', 'qrcode']);

        $validated = $request->validate([
            'scan_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $selectedScanDate = $validated['scan_date'] ?? null;

        $checkinsQuery = $employee->checkins()
            ->with('trip')
            ->when($selectedScanDate, function ($q) use ($selectedScanDate) {
                $q->whereDate('scan_time', $selectedScanDate);
            });

        $checkinsForMap = (clone $checkinsQuery)
            ->select('id', 'scan_type', 'scan_time', 'scan_lat', 'scan_lng', 'trip_id')
            ->whereNotNull('scan_lat')
            ->whereNotNull('scan_lng')
            ->orderBy('scan_time', 'asc')
            ->get();

        $checkins = (clone $checkinsQuery)
            ->orderByDesc('scan_time')
            ->get();

        return view('company.employees.show', [
            'employee'       => $employee,
            'selectedScanDate' => $selectedScanDate,
            'checkins'       => $checkins,
            'checkinCount'   => $checkins->count(),
            'checkinOnlyCount' => $checkins->where('scan_type', 'checkin')->count(),
            'checkoutOnlyCount' => $checkins->where('scan_type', 'checkout')->count(),
            'tripCount'      => $checkins->pluck('trip_id')->filter()->unique()->count(),
            'lastCheckin'    => $checkins->first(),
            'checkinsForMap' => $checkinsForMap,
        ]);
    }

    /**
     * Edit page (only if employee is in this company).
     */
    public function edit(Employee $employee): View
    {
        $this->authorizeCompany($employee);

        // Company is fixed; no dropdown
        $company = $employee->company;

        return view('company.employees.edit', [
            'employee' => $employee,
            'company'  => $company,
        ]);
    }

    /**
     * Update employee (within same company).
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeCompany($employee);

        $validated = $request->validate([
            // user
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email,' . $employee->user_id],

            // employee
            'employee_code' => ['required', 'string', 'unique:employees,employee_code,' . $employee->id],
            'department'    => ['nullable', 'string', 'max:100'],
            'status'        => ['required', 'in:active,suspended,resigned'],

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

            // Update EMPLOYEE (company_id stays the same)
            $employee->update([
                'employee_code' => $validated['employee_code'],
                'department'    => $validated['department'] ?? null,
                'status'        => $validated['status'],
                'photo_path'    => $photoPath,
            ]);
        });

        return redirect()
            ->route('company.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Ensure employee belongs to the authenticated user's company.
     */
    protected function authorizeCompany(Employee $employee): void
    {
        $companyId = auth()->user()->company_id;

        if (!$companyId || $employee->company_id !== $companyId) {
            abort(403, 'Unauthorized.');
        }
    }
}
