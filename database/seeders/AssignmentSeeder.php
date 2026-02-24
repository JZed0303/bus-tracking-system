<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assignment;
use App\Models\Driver;
use App\Models\Bus;
use App\Models\Company;
use App\Models\TransportRoute;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $driver = Driver::first();
        $bus = Bus::first();
        $company = Company::first();
        $route = TransportRoute::first();

        if (! $driver || ! $bus || ! $company || ! $route) {
            $this->command->warn('Missing data. Skipping AssignmentSeeder.');
            return;
        }

        Assignment::create([
            'driver_id'      => $driver->id,
            'bus_id'         => $bus->id,
            'company_id'     => $company->id,
            'route_id'       => $route->id,
            'effective_from' => now()->toDateString(),
            'status'         => 'active',
        ]);
    }
}
