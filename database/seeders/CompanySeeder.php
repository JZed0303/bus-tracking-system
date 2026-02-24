<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'company' => [
                    'name' => 'ABC Manufacturing Corp',
                    'address' => 'Carmona, Cavinti',
                    'contact_person' => 'HR Manager',
                    'contact_number' => '09171234567',
                    'status' => 'active',
                ],
                'admin' => [
                    'first_name' => 'ABC',
                    'last_name'  => 'Admin',
                    'email'      => 'abc@company.test',
                    'password'   => 'password',
                ],
            ],
            [
                'company' => [
                    'name' => 'XYZ Industrial Solutions',
                    'address' => 'Carmona Industrial Park',
                    'contact_person' => 'Operations Head',
                    'contact_number' => '09181234567',
                    'status' => 'active',
                ],
                'admin' => [
                    'first_name' => 'XYZ',
                    'last_name'  => 'Admin',
                    'email'      => 'xyz@company.test',
                    'password'   => 'password',
                ],
            ],
        ];

        foreach ($companies as $data) {

            /** Create Company */
            $company = Company::create([
                'name'           => $data['company']['name'],
                'address'        => $data['company']['address'],
                'contact_person' => $data['company']['contact_person'],
                'contact_number' => $data['company']['contact_number'],
                'status'         => $data['company']['status'],
            ]);

            /** Create Company Admin User */
            User::create([
                'first_name' => $data['admin']['first_name'],
                'last_name'  => $data['admin']['last_name'],
                'email'      => $data['admin']['email'],
                'password'   => Hash::make($data['admin']['password']),
                'role'       => 'company_admin',
                'status'     => 'active',
                'company_id' => $company->id,
            ]);
        }
    }
}
