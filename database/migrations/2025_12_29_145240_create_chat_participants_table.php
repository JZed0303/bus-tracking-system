<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('thread_id')
                ->constrained('chat_threads')
                ->cascadeOnDelete();

            // participant can be User or Bus
            $table->string('participant_type');
            $table->unsignedBigInteger('participant_id');

            $table->enum('role', ['owner', 'admin', 'member'])->default('member');

            // read tracking (optional)
            $table->unsignedBigInteger('last_read_message_id')->nullable();

            $table->timestamps();

            $table->unique(['thread_id', 'participant_type', 'participant_id'], 'chat_participant_unique');
            $table->index(['participant_type', 'participant_id'], 'chat_participant_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
