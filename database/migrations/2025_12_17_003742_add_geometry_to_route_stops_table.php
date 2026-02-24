<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE route_stops
            ADD COLUMN geom geometry(POINT, 4326)
        ");

        DB::statement("
            UPDATE route_stops
            SET geom = ST_SetSRID(
                ST_MakePoint(longitude, latitude),
                4326
            )
        ");

        DB::statement("
            CREATE INDEX route_stops_geom_idx
            ON route_stops
            USING GIST (geom)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE route_stops DROP COLUMN geom");
    }
};
