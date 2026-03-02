<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trip_employee_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('to_trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_bus_id')->nullable()->constrained('buses')->nullOnDelete();
            $table->string('status', 20)->default('pending_confirm');
            $table->string('reason', 50)->nullable();
            $table->timestampTz('transferred_at');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['from_trip_id', 'to_trip_id', 'employee_id'], 'trip_transfer_unique_employee');
            $table->index(['to_trip_id', 'status'], 'trip_transfer_to_trip_status_idx');
        });

        DB::statement("\n            ALTER TABLE trip_employee_transfers\n            ADD CONSTRAINT trip_employee_transfers_status_check\n            CHECK (status IN ('pending_confirm','confirmed','cancelled'))\n        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE trip_employee_transfers DROP CONSTRAINT IF EXISTS trip_employee_transfers_status_check');
        Schema::dropIfExists('trip_employee_transfers');
    }
};
