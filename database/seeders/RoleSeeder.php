<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $companyAdmin = Role::firstOrCreate([
            'name' => 'company_admin',
            'guard_name' => 'web',
        ]);

        $employee = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $driver = Role::firstOrCreate([
            'name' => 'driver',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Give ALL permissions to Super Admin
        |--------------------------------------------------------------------------
        */
        $superAdmin->syncPermissions(Permission::all());

        /*
        |--------------------------------------------------------------------------
        | Example: Limited permissions for Company Admin
        |--------------------------------------------------------------------------
        */
        $companyAdmin->syncPermissions([
            'view_company_dashboard',
            'view_companies',
            'create_companies',
            'update_companies',
            'view_employees',
            'create_employees',
            'update_employees',
            'view_buses',
            'view_routes',
            'view_live_tracking',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Employee Permissions
        |--------------------------------------------------------------------------
        */
        $employee->syncPermissions([
            'view_company_dashboard',
            'view_trips',
            'view_reports',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Driver Permissions
        |--------------------------------------------------------------------------
        */
        $driver->syncPermissions([
            'view_trips',
        ]);
    }
}