<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('scan_type', 20);
            $table->timestampTz('scan_time');

            $table->decimal('scan_lat', 10, 7)->nullable();
            $table->decimal('scan_lng', 10, 7)->nullable();

            $table->foreignId('scanned_by_driver_id')
                ->constrained('drivers')
                ->restrictOnDelete();

            $table->timestampsTz();

            $table->unique(['trip_id', 'employee_id', 'scan_type']);
        });

        // PostgreSQL CHECK constraint (raw SQL)
        DB::statement("
            ALTER TABLE checkins
            ADD CONSTRAINT checkins_scan_type_check
            CHECK (scan_type IN ('checkin','checkout'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
