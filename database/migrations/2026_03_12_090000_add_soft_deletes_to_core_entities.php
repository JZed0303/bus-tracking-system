<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->softDeletesTz();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->softDeletesTz();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->softDeletesTz();
        });

        Schema::table('buses', function (Blueprint $table) {
            $table->softDeletesTz();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('buses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
