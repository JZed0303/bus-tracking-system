<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
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

        $uiModules = collect(config('permission.ui.modules'));
        $dynamicModules = collect();
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

        $modules = collect($uiModules)->map(function ($config, $moduleKey) use ($allPermissions, $actions) {
            $permissions = [];
            foreach ($actions as $action) {
                $permissions[$action] = in_array("{$action}_{$moduleKey}", $allPermissions, true);
            }

            return [
                'label' => $config['label'],
                'order' => $config['order'] ?? 999,
                'permissions' => $permissions,
            ];
        })
        ->filter(function ($module) {
            return collect($module['permissions'])->contains(true);
        })
        ->sortBy('order');

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
    $allPermissions = Permission::pluck('name')->toArray();
    $oldPermissions = $user->permissions()->pluck('name')->sort()->values()->all();
    $oldDeniedPermissions = $user->deniedPermissions()->pluck('permission')->sort()->values()->all();

    // Super admin users must always have full access with no denies.
    if ($user->hasRole('super_admin') || $user->role === 'super_admin') {
        $user->syncPermissions($allPermissions);
        $user->deniedPermissions()->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Super Admin always has full access.');
    }

    $selected = array_values(array_unique($request->input('permissions', [])));
    $denied = array_values(array_unique($request->input('denied', [])));

    // Keep only valid permission names
    $selected = array_values(array_intersect($selected, $allPermissions));
    $denied = array_values(array_intersect($denied, $allPermissions));

    // If permission is explicitly allowed, it cannot be denied at the same time.
    $denied = array_values(array_diff($denied, $selected));

    // Apply direct user permissions
    $user->syncPermissions($selected);

    // Update explicit deny overrides:
    // - selected in denied[] => deny
    // - not selected in denied[] => clear deny (no explicit override)
    foreach ($allPermissions as $permission) {
        if (in_array($permission, $denied, true)) {
            $user->denyPermission($permission);
        } else {
            $user->allowPermission($permission);
        }
    }

<<<<<<< HEAD
    $newPermissions = $user->permissions()->pluck('name')->sort()->values()->all();
    $newDeniedPermissions = $user->deniedPermissions()->pluck('permission')->sort()->values()->all();

    AuditTrail::log(
        event: 'user_permissions_updated',
        auditable: $user,
        oldValues: [
            'permissions' => $oldPermissions,
            'denied_permissions' => $oldDeniedPermissions,
        ],
        newValues: [
            'permissions' => $newPermissions,
            'denied_permissions' => $newDeniedPermissions,
        ],
        tags: 'permissions'
    );
=======
    app(PermissionRegistrar::class)->forgetCachedPermissions();
>>>>>>> origin/IBTS-v1

    return back()->with('success', 'User permissions updated.');
}

}
