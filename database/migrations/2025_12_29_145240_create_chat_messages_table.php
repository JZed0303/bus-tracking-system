<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('thread_id')
                ->constrained('chat_threads')
                ->cascadeOnDelete();

            // sender can be User or Bus
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');

            $table->text('body');

            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
