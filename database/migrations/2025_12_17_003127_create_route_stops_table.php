<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('route_stops', function (Blueprint $table) {
    $table->id();
    $table->foreignId('route_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('name', 150);
    $table->decimal('latitude', 10, 7);
    $table->decimal('longitude', 10, 7);
    $table->integer('stop_order');
    $table->timestampTz('created_at')->useCurrent();

    $table->unique(['route_id', 'stop_order']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
