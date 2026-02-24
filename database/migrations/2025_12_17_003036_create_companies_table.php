<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();
        });

        // ✅ PostgreSQL CHECK constraint
        DB::statement("
            ALTER TABLE companies
            ADD CONSTRAINT companies_status_check
            CHECK (status IN ('active', 'inactive'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
