<?php

// php artisan make:migration add_last_seen_at_to_users_and_buses
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
    });

    Schema::table('buses', function (Blueprint $table) {
      $table->timestamp('last_seen_at')->nullable()->after('status');
    });
  }

  public function down(): void
  {
    Schema::table('users', fn (Blueprint $t) => $t->dropColumn('last_seen_at'));
    Schema::table('buses', fn (Blueprint $t) => $t->dropColumn('last_seen_at'));
  }
};
