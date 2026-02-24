<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    /**
     * STEP A: List / search users
     */
    public function index(Request $request)
    {
        $roles = User::query()
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        $users = User::query()
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->where('role', $request->role);
            })
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('last_name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.permissions-index', compact('users', 'roles'));
    }

    /**
     * STEP B: Edit permissions for selected user
     */
    public function edit(User $user)
    {
        $allPermissions = Permission::pluck('name')->toArray();

        $uiModules = config('permission.ui.modules');
        $actions   = array_keys(config('permission.ui.actions'));

        $modules = collect($uiModules)->map(function ($config, $moduleKey) use ($allPermissions) {
 // Permissions NOT tied to modules (dashboards, system, etc.)

            return [
                'label' => $config['label'],
                'order' => $config['order'] ?? 999,

                // IMPORTANT: show matrix only if permission exists
                'permissions' => [
                    'view'   => in_array("view_{$moduleKey}", $allPermissions),
                    'create' => in_array("manage_{$moduleKey}", $allPermissions),
                    'update' => in_array("manage_{$moduleKey}", $allPermissions),
                    'delete' => in_array("manage_{$moduleKey}", $allPermissions),
                ],
            ];
        })->sortBy('order');

        $specialPermissions = Permission::whereIn('name', [
    'view_admin_dashboard',
    'view_company_dashboard',
    'view_live_tracking',
])->orderBy('name')->get();

        return view('admin.users.permissions', compact(
            'user',
            'modules',
            'actions',
                'specialPermissions'

        ));
    }

  public function update(Request $request, User $user)
{
    $selected = $request->permissions ?? [];
    $allPermissions = Permission::pluck('name')->toArray();

    // Allow selected
    $user->syncPermissions($selected);

    // Deny unselected
    foreach ($allPermissions as $permission) {
        if (in_array($permission, $selected)) {
            $user->allowPermission($permission);
        } else {
            $user->denyPermission($permission);
        }
    }

    return back()->with('success', 'User permissions updated.');
}

}
