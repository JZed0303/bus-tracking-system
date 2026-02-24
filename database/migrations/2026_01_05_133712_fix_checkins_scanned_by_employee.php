<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            // Drop old FK
            $table->dropForeign('checkins_scanned_by_driver_id_foreign');

            // Rename column
            $table->renameColumn('scanned_by_driver_id', 'scanned_by_employee_id');

            // Add correct FK
            $table->foreign('scanned_by_employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropForeign(['scanned_by_employee_id']);
            $table->renameColumn('scanned_by_employee_id', 'scanned_by_driver_id');
            $table->foreign('scanned_by_driver_id')
                ->references('id')
                ->on('drivers');
        });
    }
};
