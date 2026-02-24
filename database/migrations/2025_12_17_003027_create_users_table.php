<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // 🔹 Identity fields
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->text('address')->nullable();

            // 🔹 Auth fields
            $table->string('email', 150)->unique();
            $table->string('password');

            // 🔹 Role & status
            $table->string('role', 30);
            $table->string('status', 20)->default('active');

            // 🔹 Laravel auth requirements
            $table->rememberToken();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
        });

        // ✅ PostgreSQL CHECK constraints
        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_role_check
            CHECK (role IN ('super_admin', 'company_admin', 'driver', 'employee'))
        ");

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_status_check
            CHECK (status IN ('active', 'inactive', 'suspended'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
