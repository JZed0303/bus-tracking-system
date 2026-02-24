<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_schedules', function (Blueprint $table) {
        $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('route_id')
                ->constrained('routes')
                ->cascadeOnDelete();

            $table->foreignId('bus_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('schedule_date');

            $table->string('shift_name')->nullable();
            $table->time('expected_pickup_time')->nullable();
            $table->time('expected_dropoff_time')->nullable();

            $table->enum('status', [
                'scheduled',
                'completed',
                'missed',
                'cancelled'
            ])->default('scheduled');

            $table->timestamps();

            $table->unique([
                'employee_id',
                'schedule_date'
            ], 'employee_daily_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};
