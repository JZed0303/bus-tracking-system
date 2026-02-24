<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('trip_date');
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            $table->timestampTz('actual_start_time')->nullable();
            $table->timestampTz('actual_end_time')->nullable();
            $table->string('direction', 20);
            $table->string('status', 20);

            $table->timestampsTz();
        });

        // PostgreSQL CHECK constraints
        DB::statement("
            ALTER TABLE trips
            ADD CONSTRAINT trips_direction_check
            CHECK (direction IN ('pickup','dropoff'))
        ");

        DB::statement("
            ALTER TABLE trips
            ADD CONSTRAINT trips_status_check
            CHECK (status IN ('scheduled','ongoing','completed','cancelled'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
