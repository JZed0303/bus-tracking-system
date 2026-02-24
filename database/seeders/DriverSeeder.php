<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\User;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('role', 'driver')->first();

        if (! $user) {
            $this->command->warn('No driver user found. Skipping DriverSeeder.');
            return;
        }

        Driver::create([
            'user_id' => $user->id,
            'license_number' => 'DRV-123456',
            'phone' => '09170000000',
            'status' => 'active',
        ]);
    }
}
