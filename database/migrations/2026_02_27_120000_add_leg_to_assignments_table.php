<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('leg', 20)->default('both')->after('status');
        });

        DB::statement("\n            ALTER TABLE assignments\n            ADD CONSTRAINT assignments_leg_check\n            CHECK (leg IN ('pickup','dropoff','both'))\n        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assignments DROP CONSTRAINT IF EXISTS assignments_leg_check');

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('leg');
        });
    }
};
