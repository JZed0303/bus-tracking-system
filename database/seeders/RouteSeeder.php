<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\TransportRoute;
use App\Models\RouteStop;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        $route = TransportRoute::create([
            'name' => 'Carmona Morning Route',
            'company_id' => $company->id,
            'start_location' => 'Barangay A',
            'end_location' => 'Factory Gate',
            'status' => 'active',
        ]);

        $stops = [
            ['Stop A', 14.3255, 121.0402, 1],
            ['Stop B', 14.3290, 121.0450, 2],
            ['Factory', 14.3335, 121.0485, 3],
        ];

        foreach ($stops as [$name, $lat, $lng, $order]) {
            RouteStop::create([
                'route_id' => $route->id,
                'name' => $name,
                'latitude' => $lat,
                'longitude' => $lng,
                'stop_order' => $order,
            ]);
        }

        // Set route LINESTRING geometry
        DB::statement("
            UPDATE routes SET geom = ST_SetSRID(
                ST_MakeLine(ARRAY[
                    ST_MakePoint(121.0402,14.3255),
                    ST_MakePoint(121.0450,14.3290),
                    ST_MakePoint(121.0485,14.3335)
                ]),
                4326
            )
            WHERE id = {$route->id}
        ");
    }
}
