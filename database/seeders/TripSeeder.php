<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Trip;
use App\Models\Assignment;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        Trip::create([
            'assignment_id' => Assignment::first()->id,
            'trip_date' => now()->toDateString(),
            'scheduled_start_time' => '06:00',
            'scheduled_end_time' => '08:00',
            'direction' => 'pickup',
            'status' => 'scheduled',
        ]);
    }
}
