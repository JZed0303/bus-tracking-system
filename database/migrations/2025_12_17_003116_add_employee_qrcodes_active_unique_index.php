<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safety check (prevents crash if order is wrong)
        if (!Schema::hasTable('employee_qrcodes')) {
            return;
        }

        // Deactivate duplicate active QR codes
        DB::statement("
            UPDATE employee_qrcodes
            SET is_active = false
            WHERE id NOT IN (
                SELECT MAX(id)
                FROM employee_qrcodes
                WHERE is_active = true
                GROUP BY employee_id
            )
            AND is_active = true
        ");

        // Create partial unique index
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS employee_qrcodes_one_active_idx
            ON employee_qrcodes (employee_id)
            WHERE is_active = true
        ");
    }

    public function down(): void
    {
        DB::statement("
            DROP INDEX IF EXISTS employee_qrcodes_one_active_idx
        ");
    }
};
