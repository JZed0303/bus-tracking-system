<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('employee_code', 50)->unique();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->text('photo_path')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestampsTz();
        });

        // PostgreSQL CHECK constraint (must be raw SQL)
        DB::statement("
            ALTER TABLE employees
            ADD CONSTRAINT employees_status_check
            CHECK (status IN ('active','resigned','suspended'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
