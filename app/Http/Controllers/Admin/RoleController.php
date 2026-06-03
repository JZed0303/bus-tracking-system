<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ]);

        $normalized = Str::of($validated['name'])
            ->lower()
            ->trim()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($normalized === '') {
            return back()->withErrors([
                'name' => 'Please enter a valid role name.',
            ])->withInput();
        }

        Role::create([
            'name' => $normalized,
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permission_name' => ['required', 'string', 'max:100'],
        ]);

        $normalized = Str::of($validated['permission_name'])
            ->lower()
            ->trim()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($normalized === '') {
            return back()->withErrors([
                'permission_name' => 'Please enter a valid permission name.',
            ])->withInput();
        }

        Permission::findOrCreate($normalized, 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Permission created successfully.');
    }
}
