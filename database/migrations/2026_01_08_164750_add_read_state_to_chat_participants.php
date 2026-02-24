<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_participants', function (Blueprint $table) {
            // Only add if the column does NOT exist yet

            if (!Schema::hasColumn('chat_participants', 'last_read_message_id')) {
                $table->unsignedBigInteger('last_read_message_id')->nullable();
            }

            if (!Schema::hasColumn('chat_participants', 'last_read_at')) {
                $table->timestamp('last_read_at')->nullable();
            }

            if (!Schema::hasColumn('chat_participants', 'unread_count')) {
                $table->unsignedInteger('unread_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_participants', function (Blueprint $table) {
            if (Schema::hasColumn('chat_participants', 'last_read_message_id')) {
                $table->dropColumn('last_read_message_id');
            }

            if (Schema::hasColumn('chat_participants', 'last_read_at')) {
                $table->dropColumn('last_read_at');
            }

            if (Schema::hasColumn('chat_participants', 'unread_count')) {
                $table->dropColumn('unread_count');
            }
        });
    }
};
