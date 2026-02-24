<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
   public function edit(Role $role)
{
    $uiModules = config('permission.ui.modules');
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
    })->sortBy('order');

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
    if ($role->name === 'super_admin') {
        $role->syncPermissions(Permission::all());
        return back()->with('success', 'Super Admin always has full access.');
    }

    $permissions = $request->input('permissions', []);

    // Sync only selected permissions
    $role->syncPermissions($permissions);

    return back()->with('success', 'Role permissions updated successfully.');
}

}
