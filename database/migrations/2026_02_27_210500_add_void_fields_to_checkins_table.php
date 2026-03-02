<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->timestampTz('voided_at')->nullable()->after('synced_at');
            $table->string('void_reason', 255)->nullable()->after('voided_at');
            $table->foreignId('voided_by_user_id')
                ->nullable()
                ->after('void_reason')  
                ->constrained('users')
                ->nullOnDelete();

            $table->index('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropForeign(['voided_by_user_id']);
            $table->dropIndex(['voided_at']);
            $table->dropColumn(['voided_at', 'void_reason', 'voided_by_user_id']);
        });
    }
};
