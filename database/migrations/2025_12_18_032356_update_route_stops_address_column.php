<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->renameColumn('name', 'address');
            $table->string('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->renameColumn('address', 'name');
        });
    }
};
