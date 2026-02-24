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
    Schema::create('employee_checkins', function (Blueprint $table) {
        $table->id();

        $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
        $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

        $table->enum('check_type', ['IN', 'OUT']);
        $table->timestampTz('checked_in_at')->useCurrent();

        $table->timestampsTz();
    });

    DB::statement("
        ALTER TABLE employee_checkins
        ADD COLUMN location GEOGRAPHY(Point, 4326)
    ");

    DB::statement("
        CREATE INDEX employee_checkins_location_gist
        ON employee_checkins USING GIST (location)
    ");
}

public function down(): void
{
    Schema::dropIfExists('employee_checkins');
}

};
