<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            EmployeeSeeder::class,
            // EmployeeQrSeeder::class,
            DriverSeeder::class,
            BusSeeder::class,
            RouteSeeder::class,
            AssignmentSeeder::class,
             CheckinSeeder::class,
            TripSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class
        ]);
    }
}
