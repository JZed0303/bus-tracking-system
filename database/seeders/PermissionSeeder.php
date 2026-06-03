<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DASHBOARD PERMISSIONS
        |--------------------------------------------------------------------------
        */
        $dashboardPermissions = [
            'view_admin_dashboard',
            'view_company_dashboard',
        ];


        
        /*
        |--------------------------------------------------------------------------
        | MODULE PERMISSIONS (FULL CRUD)
        |--------------------------------------------------------------------------
        */
        $modules = [
            'companies',
            'employees',
            'drivers',
            'buses',
            'routes',
            'assignments',
            'trips',
            'reports',
            'notifications',
            'schedules',
            'settings',
        ];

        $crudActions = ['view', 'create', 'update', 'delete'];

        $modulePermissions = [];

        foreach ($modules as $module) {
            foreach ($crudActions as $action) {
                $modulePermissions[] = "{$action}_{$module}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CHAT PERMISSIONS
        |--------------------------------------------------------------------------
        */
      $chatPermissions = [
    // Visibility
       'create_chat',
       'update_chat',

       'delete_chat',

    'view_chat',
    'view_chat_bus',
    'view_chat_group',
    'view_chat_personal',

    // Thread actions
    'create_chat_thread',   // create new group/personal thread (if your app allows)
    'delete_chat_thread',   // delete a thread (rare; usually admin-only)

    // Message actions
    'create_chat_message',
    'delete_chat_message',

    // Participants
    'manage_chat_users',
];



        /*
        |--------------------------------------------------------------------------
        | SYSTEM / GLOBAL PERMISSIONS
        |--------------------------------------------------------------------------
        */
        $systemPermissions = [
            'view_live_tracking',
            'manage_roles',
            'manage_user_permissions',
            'manage_role_permissions',
        ];

        /*
        |--------------------------------------------------------------------------
        | INSERT ALL PERMISSIONS
        |--------------------------------------------------------------------------
        */
        foreach (
            array_merge(
                $dashboardPermissions,
                $modulePermissions,
                $chatPermissions,
                $systemPermissions
            ) as $permission
        ) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
