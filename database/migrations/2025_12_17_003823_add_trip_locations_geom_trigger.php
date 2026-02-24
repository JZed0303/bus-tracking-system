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
            CREATE OR REPLACE FUNCTION set_trip_location_geom()
            RETURNS trigger AS $$
            BEGIN
                NEW.geom :=
                    ST_SetSRID(
                        ST_MakePoint(NEW.longitude, NEW.latitude),
                        4326
                    );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_trip_locations_geom
            BEFORE INSERT OR UPDATE
            ON trip_locations
            FOR EACH ROW
            EXECUTE FUNCTION set_trip_location_geom();
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS trg_trip_locations_geom ON trip_locations");
        DB::statement("DROP FUNCTION IF EXISTS set_trip_location_geom()");
    }
};
