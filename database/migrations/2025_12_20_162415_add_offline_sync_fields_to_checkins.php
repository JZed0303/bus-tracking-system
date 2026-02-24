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
      Schema::table('checkins', function (Blueprint $table) {
    $table->uuid('client_scan_id')->nullable()->unique();
    $table->boolean('is_offline')->default(false);
    $table->timestamp('synced_at')->nullable();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            //
        });
    }
};
