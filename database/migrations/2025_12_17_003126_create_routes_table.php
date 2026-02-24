<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('start_location')->nullable();
            $table->string('end_location')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestampsTz();
        });

        // PostgreSQL CHECK constraint (must be raw SQL)
        DB::statement("
            ALTER TABLE routes
            ADD CONSTRAINT routes_status_check
            CHECK (status IN ('active','inactive'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
