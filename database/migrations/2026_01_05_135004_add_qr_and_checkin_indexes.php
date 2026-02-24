<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('employee_qrcodes')) {
            Schema::table('employee_qrcodes', function (Blueprint $table) {
                $table->index(['qr_token', 'is_active']);
            });
        }

        if (Schema::hasTable('checkins')) {
            Schema::table('checkins', function (Blueprint $table) {
                $table->index(['employee_id', 'scan_type', 'trip_id', 'id']);
                $table->index(['trip_id', 'employee_id', 'id']);
            });
        }

        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->index(['status', 'id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_qrcodes')) {
            Schema::table('employee_qrcodes', function (Blueprint $table) {
                $table->dropIndex(['qr_token', 'is_active']);
            });
        }

        if (Schema::hasTable('checkins')) {
            Schema::table('checkins', function (Blueprint $table) {
                $table->dropIndex(['employee_id', 'scan_type', 'trip_id', 'id']);
                $table->dropIndex(['trip_id', 'employee_id', 'id']);
            });
        }

        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->dropIndex(['status', 'id']);
            });
        }
    }
};
