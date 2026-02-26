<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->enum('menu_type', ['header', 'dropdown', 'link'])
                ->default('link')
                ->after('scope');
            $table->foreignId('parent_id')
                ->nullable()
                ->after('menu_type')
                ->constrained('modules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('menu_type');
        });
    }
};
