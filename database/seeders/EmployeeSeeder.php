<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (! $company) {
            $this->command->warn('No company found. Skipping EmployeeSeeder.');
            return;
        }

        // Employee users data (aligned with new schema)
        $users = [
            [
                'first_name'  => 'Juan',
                'middle_name' => null,
                'last_name'   => 'Dela Cruz',
                'address'     => 'Employee Housing Area',

                'email'    => 'juan.employee@test.com',
                'password' => Hash::make('password'),

                'role'   => 'employee',
                'status' => 'active',
            ],
            [
                'first_name'  => 'Maria',
                'middle_name' => null,
                'last_name'   => 'Santos',
                'address'     => 'Employee Housing Area',

                'email'    => 'maria.employee@test.com',
                'password' => Hash::make('password'),

                'role'   => 'employee',
                'status' => 'active',
            ],
        ];

        foreach ($users as $index => $userData) {
            $user = User::create($userData);

            Employee::create([
                'user_id'       => $user->id,
                'company_id'    => $company->id,
                'employee_code' => 'EMP-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'department'    => 'Production',
                'position'      => 'Factory Worker',
                'status'        => 'active',
            ]);
        }
    }
}
