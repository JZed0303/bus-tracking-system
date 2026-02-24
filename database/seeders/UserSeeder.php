<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Super',
            'middle_name' => null,
            'last_name' => 'Admin',
            'address' => 'System Headquarters',

            'email' => 'admin@system.test',
            'password' => Hash::make('password'),

            'role' => 'super_admin',
            'status' => 'active',
        ]);

        User::create([
            'first_name' => 'Company',
            'middle_name' => null,
            'last_name' => 'Admin',
            'address' => 'Company Office',

            'email' => 'company@system.test',
            'password' => Hash::make('password'),

            'role' => 'company_admin',
            'status' => 'active',
        ]);

        User::create([
            'first_name' => 'Driver',
            'middle_name' => null,
            'last_name' => 'One',
            'address' => 'Driver Terminal',

            'email' => 'driver@system.test',
            'password' => Hash::make('password'),

            'role' => 'driver',
            'status' => 'active',
        ]);
    }
}
