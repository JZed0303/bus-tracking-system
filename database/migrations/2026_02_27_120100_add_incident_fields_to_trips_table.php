<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('transfer_from_trip_id')
                ->nullable()
                ->after('assignment_id')
                ->constrained('trips')
                ->nullOnDelete();

            $table->string('ended_reason', 50)->nullable()->after('status');
            $table->timestampTz('incident_reported_at')->nullable()->after('ended_reason');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_from_trip_id');
            $table->dropColumn(['ended_reason', 'incident_reported_at']);
        });
    }
};
