<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('license_number', 100)->unique();
            $table->string('phone', 50)->nullable();
            $table->text('photo_path')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestampsTz();
        });

        // PostgreSQL CHECK constraint
        DB::statement("
            ALTER TABLE drivers
            ADD CONSTRAINT drivers_status_check
            CHECK (status IN ('active','on_leave','suspended'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
