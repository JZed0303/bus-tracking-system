<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('trip_locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
    $table->foreignId('driver_id')->constrained();
    $table->foreignId('bus_id')->constrained();
    $table->decimal('latitude', 10, 7);
    $table->decimal('longitude', 10, 7);
    $table->decimal('speed', 6, 2)->nullable();
    $table->timestampTz('tracked_at');
    $table->timestampsTz();
});

DB::statement("
    CREATE INDEX trip_locations_trip_time
    ON trip_locations (trip_id, tracked_at DESC)
");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
