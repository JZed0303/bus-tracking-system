<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin=User::create([
            'first_name' => 'Super',
            'middle_name' => null,
            'last_name' => 'Admin',
            'address' => 'System Headquarters',

            'email' => 'admin@system.test',
            'password' => Hash::make('password'),

            'role' => 'super_admin',
            'status' => 'active',
        ]);

           
            $superadmin->assignRole('super_admin');

       
    }
}
