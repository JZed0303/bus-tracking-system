<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditTrail;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
public function edit(Role $role)
{
    if ($role->name === 'super_admin') {
        $role->syncPermissions(Permission::pluck('name')->toArray());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    $uiModules = collect(config('permission.ui.modules'));
    $dynamicModules = collect();
    $allPermissions = Permission::pluck('name')->toArray();

    if (Schema::hasTable('modules')) {
        $dynamicQuery = Module::query()
            ->orderBy('sort_order')
            ->orderBy('title');

        // Only link modules are permission-bearing items.
        if (Schema::hasColumn('modules', 'menu_type')) {
            $dynamicQuery->where('menu_type', 'link');
        }

        $dynamicModules = $dynamicQuery
            ->get()
            ->filter(function ($module) use ($allPermissions) {
                foreach (['view', 'create', 'update', 'delete'] as $action) {
                    if (in_array("{$action}_{$module->slug}", $allPermissions, true)) {
                        return true;
                    }
                }

                return false;
            })
            ->mapWithKeys(fn ($module) => [
                $module->slug => [
                    'label' => $module->title,
                    'order' => 1000 + (int) $module->sort_order,
                ],
            ]);
    }

    $uiModules = $uiModules->merge($dynamicModules);
    $actions   = array_keys(config('permission.ui.actions'));

    $modules = collect($uiModules)->map(function ($config, $moduleKey) use ($actions) {

        $permissions = [];

        foreach ($actions as $action) {
            $permissions[$action] = "{$action}_{$moduleKey}";
        }

        return [
            'label' => $config['label'],
            'order' => $config['order'] ?? 999,
            'permissions' => $permissions,
        ];
    })
    ->filter(function ($module) use ($allPermissions) {
        foreach ($module['permissions'] as $permissionName) {
            if (in_array($permissionName, $allPermissions, true)) {
                return true;
            }
        }

        return false;
    })
    ->sortBy('order');

    // Permissions NOT tied to modules (dashboards, system, etc.)
$specialPermissions = Permission::whereIn('name', [
    'view_admin_dashboard',
    'view_company_dashboard',
    'view_live_tracking',
])->orderBy('name')->get();

    return view('admin.roles.permissions', compact(
        'role',
        'modules',
        'actions',
        'specialPermissions'
    ));
}

    public function update(Request $request, Role $role)
{
    $oldPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

    if ($role->name === 'super_admin') {
        $role->syncPermissions(Permission::all());

        $newPermissions = $role->permissions()->pluck('name')->sort()->values()->all();
        AuditTrail::log(
            event: 'role_permissions_updated',
            auditable: $role,
            oldValues: ['permissions' => $oldPermissions],
            newValues: ['permissions' => $newPermissions],
            tags: 'permissions'
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('success', 'Super Admin always has full access.');
    }

    $permissions = $request->input('permissions', []);

    // Sync only selected permissions
    $role->syncPermissions($permissions);
    $newPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

    AuditTrail::log(
        event: 'role_permissions_updated',
        auditable: $role,
        oldValues: ['permissions' => $oldPermissions],
        newValues: ['permissions' => $newPermissions],
        tags: 'permissions'
    );
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return back()->with('success', 'Role permissions updated successfully.');
}

}
