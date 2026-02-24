<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Bus;

class BusSeeder extends Seeder
{
    public function run(): void
    {
        Bus::create([
            'plate_number' => 'ABC-1234',
            'bus_code' => 'BUS-01',
            'capacity' => 40,
            'brand_model' => 'Isuzu NQR',
            'status' => 'active',
        ]);
    }
}
