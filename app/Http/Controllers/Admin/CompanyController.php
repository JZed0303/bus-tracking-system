<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// ✅ Spatie
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(Request $request)
    {
        $query = Company::query()
            ->withCount(['employees', 'routes'])
            ->orderBy('name');

        // Status filter ONLY
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Use get() for DataTables client-side
        $companies = $query->get();

        return view('admin.companies.index', [
            'companies' => $companies,
            'isArchive' => false,
        ]);
    }

    public function archive(Request $request)
    {
        $query = Company::onlyTrashed()
            ->withCount(['employees', 'routes'])
            ->orderByDesc('deleted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $companies = $query->get();

        return view('admin.companies.index', [
            'companies' => $companies,
            'isArchive' => true,
        ]);
    }

    /**
     * Show the form for creating a new company.
     */
    public function create()
    {
        return view('admin.companies.create');
    }

    /**
     * Store a newly created company + company admin user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Company fields
            'name'           => ['required', 'string', 'max:255', 'unique:companies,name'],
            'address'        => ['nullable', 'string', 'max:500'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'status'         => ['required', 'in:active,inactive'],
            'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Admin fields
            'admin_name'     => ['nullable', 'string', 'max:255'],
            'admin_email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($request, $validated) {

            // 1) Create company
            $company = Company::create([
                'name'           => $validated['name'],
                'address'        => $validated['address'] ?? null,
                'contact_person' => $validated['contact_person'],
                'contact_number' => $validated['contact_number'],
                'status'         => $validated['status'],
            ]);

            // 2) Upload logo (optional)
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('company-logos', 'public');
                $company->update(['logo' => $path]);
            }

            // 3) Determine admin name
            $adminName = trim($validated['admin_name'] ?? $validated['contact_person'] ?? 'Company Admin');
            [$firstName, $lastName] = $this->splitName($adminName);

            // 4) Create admin user
            $adminUser = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName ?: 'Admin',
                'email'      => $validated['admin_email'],
                'password'   => Hash::make($validated['admin_password']),
                'role'       => 'company_admin',   // optional legacy column
                'status'     => 'active',
                'company_id' => $company->id,
            ]);

            // ✅ 5) Ensure Spatie role exists and assign it
            // This is what makes middleware('role:company_admin|super_admin') pass.
            $companyAdminRole = Role::firstOrCreate([
                'name'       => 'company_admin',
                'guard_name' => 'web',
            ]);

            // If you want to ensure it doesn't keep old roles:
            $adminUser->syncRoles([$companyAdminRole->name]);

            // ✅ 6) Clear spatie cache (important if roles/permissions change at runtime)
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Company and admin account created successfully.');
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company)
    {
        $company->loadCount(['employees', 'routes', 'assignments']);

        $company->load([
            'assignments.driver.user',
            'assignments.bus',
            'assignments.route',
            'routes',
        ]);

        return view('admin.companies.show', compact('company'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company)
    {
        $companyAdmin = $this->resolveCompanyAdmin($company);

        return view('admin.companies.edit', compact('company', 'companyAdmin'));
    }

    /**
     * Update the specified company.
     */


public function update(Request $request, Company $company)
{
    $companyAdmin = $this->resolveCompanyAdmin($company);
    $companyAdminId = $companyAdmin?->id;

    $validated = $request->validate([
        'name'           => ['required', 'string', 'max:255', 'unique:companies,name,' . $company->id],
        'address'        => ['nullable', 'string', 'max:500'],
        'contact_person' => ['required', 'string', 'max:255'],
        'contact_number' => ['required', 'string', 'max:50'],
        'status'         => ['required', 'in:active,inactive'],
        'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'admin_email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($companyAdminId)],
        'admin_password' => [Rule::requiredIf(!$companyAdminId), 'nullable', 'string', 'min:8'],
    ]);

    DB::transaction(function () use ($request, $company, $validated, $companyAdmin) {
        if ($request->hasFile('logo')) {
            // 1) Store new logo
            $newLogoPath = $request->file('logo')->store('company-logos', 'public');

            // 2) Delete old logo if exists
            $this->deleteCompanyLogoIfExists($company);

            // 3) Add to data to be updated
            $validated['logo'] = $newLogoPath;
        }

        // 4) Update DB row
        $company->update($validated);

        // 5) Update the COMPANY ADMIN user for this company
        $adminUser = $companyAdmin;

        if (!$adminUser) {
            [$firstName, $lastName] = $this->splitName($validated['contact_person'] ?? 'Company Admin');

            $adminUser = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName ?: 'Admin',
                'email'      => $validated['admin_email'],
                'password'   => Hash::make($validated['admin_password']),
                'role'       => 'company_admin',
                'status'     => 'active',
                'company_id' => $company->id,
            ]);

            $companyAdminRole = Role::firstOrCreate([
                'name'       => 'company_admin',
                'guard_name' => 'web',
            ]);

            $adminUser->syncRoles([$companyAdminRole->name]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } else {
            $adminData = [
                'email'      => $validated['admin_email'],
                'company_id' => $company->id,
            ];

            if (!empty($validated['admin_password'])) {
                $adminData['password'] = Hash::make($validated['admin_password']);
            }

            $adminUser->update($adminData);
        }
    });

    return redirect()
        ->route('admin.companies.index')
        ->with('success', 'Company updated successfully.');
}

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company)
    {
        DB::transaction(function () use ($company) {
            User::query()
                ->where('company_id', $company->id)
                ->update(['status' => 'inactive']);

            $company->delete();
        });

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    public function restore(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($company) {
            $company->restore();

            User::query()
                ->where('company_id', $company->id)
                ->where('status', 'inactive')
                ->update(['status' => 'active']);
        });

        return redirect()
            ->route('admin.companies.archive')
            ->with('success', 'Company restored successfully.');
    }

    /**
     * Helpers
     */
    private function deleteCompanyLogoIfExists(Company $company): void
    {
        if ($company->logo && Storage::disk('public')->exists($company->logo)) {
            Storage::disk('public')->delete($company->logo);
        }
    }

    /**
     * Split full name to first/last (simple).
     */
    private function splitName(string $name): array
    {
        $name = preg_replace('/\s+/', ' ', trim($name));
        if ($name === '') return ['Company', 'Admin'];

        $parts = explode(' ', $name);
        $first = array_shift($parts);
        $last  = trim(implode(' ', $parts));

        return [$first, $last];
    }

    private function resolveCompanyAdmin(Company $company): ?User
    {
        return User::query()
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('role', 'company_admin')
                  ->orWhereHas('roles', function ($r) {
                      $r->where('name', 'company_admin');
                  });
            })
            ->orderBy('id')
            ->first();
    }
}
