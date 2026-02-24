<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bus_gps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bus_id')
                ->constrained('buses')
                ->cascadeOnDelete();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Use timestamp for "latestOfMany('tracked_at')"
            $table->timestamp('tracked_at')->nullable()->index();

            $table->timestamps();

            $table->index(['bus_id', 'tracked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_gps');
    }
};
