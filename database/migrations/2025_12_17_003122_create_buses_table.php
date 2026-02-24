<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 50)->unique();
            $table->string('bus_code', 50)->nullable();
            $table->integer('capacity');
            $table->string('brand_model', 150)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();
        });

        // PostgreSQL CHECK constraints
        DB::statement("
            ALTER TABLE buses
            ADD CONSTRAINT buses_capacity_check
            CHECK (capacity > 0)
        ");

        DB::statement("
            ALTER TABLE buses
            ADD CONSTRAINT buses_status_check
            CHECK (status IN ('active','maintenance','retired'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
