<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'checkins_trip_id_employee_id_scan_type_unique'
                ) THEN
                    ALTER TABLE checkins
                    DROP CONSTRAINT checkins_trip_id_employee_id_scan_type_unique;
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE checkins
            ADD CONSTRAINT checkins_trip_id_employee_id_scan_type_unique
            UNIQUE (trip_id, employee_id, scan_type);
        SQL);
    }
};

