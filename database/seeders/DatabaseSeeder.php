<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
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
           
        ]);
    }
}
