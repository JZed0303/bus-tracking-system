<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE trip_locations
            ADD COLUMN geom geometry(POINT, 4326)
        ");

        DB::statement("
            UPDATE trip_locations
            SET geom = ST_SetSRID(
                ST_MakePoint(longitude, latitude),
                4326
            )
        ");

        DB::statement("
            CREATE INDEX trip_locations_geom_idx
            ON trip_locations
            USING GIST (geom)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trip_locations DROP COLUMN geom");
    }
};
