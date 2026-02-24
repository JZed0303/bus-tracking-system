<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('bus_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('route_id')
                ->constrained()
                ->restrictOnDelete();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestampsTz();
        });

        // PostgreSQL CHECK constraint (raw SQL)
        DB::statement("
            ALTER TABLE assignments
            ADD CONSTRAINT assignments_status_check
            CHECK (status IN ('active','ended'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
