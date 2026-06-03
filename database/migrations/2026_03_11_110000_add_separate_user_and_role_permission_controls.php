<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['manage_user_permissions', 'manage_role_permissions'] as $permissionName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $manageRolesPermissionId = DB::table('permissions')
            ->where('name', 'manage_roles')
            ->where('guard_name', 'web')
            ->value('id');

        if (!$manageRolesPermissionId) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $manageRolesPermissionId)
            ->pluck('role_id')
            ->all();

        if (empty($roleIds)) {
            return;
        }

        $newPermissionIds = DB::table('permissions')
            ->whereIn('name', ['manage_user_permissions', 'manage_role_permissions'])
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        foreach ($roleIds as $roleId) {
            foreach ($newPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['manage_user_permissions', 'manage_role_permissions'])
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        if (!empty($permissionIds)) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            DB::table('model_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('name', ['manage_user_permissions', 'manage_role_permissions'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
