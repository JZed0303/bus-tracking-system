<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Checkin;
use App\Models\Trip;
use App\Models\Employee;
use App\Models\Driver;

class CheckinSeeder extends Seeder
{
    public function run(): void
    {
        $trip = Trip::first();
        $employee = Employee::first();
        $driver = Driver::first();

        if (! $trip || ! $employee) {
            $this->command->warn('Skipping CheckinSeeder: missing trip or employee.');
            return;
        }

        // CHECK-IN
        Checkin::create([
            'trip_id' => $trip->id,
            'employee_id' => $employee->id,
            'scan_type' => 'checkin',   // ✅ MUST MATCH CONSTRAINT
            'scan_time' => now()->subMinutes(20),
            'scan_lat' => 14.5995,
            'scan_lng' => 120.9842,
            'scanned_by_driver_id' => $driver?->id,
        ]);

        // CHECK-OUT
        Checkin::create([
            'trip_id' => $trip->id,
            'employee_id' => $employee->id,
            'scan_type' => 'checkout',  // ✅ MUST MATCH CONSTRAINT
            'scan_time' => now(),
            'scan_lat' => 14.6001,
            'scan_lng' => 120.9850,
            'scanned_by_driver_id' => $driver?->id,
        ]);
    }
}
