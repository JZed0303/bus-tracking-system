<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->string('caller_type', 32);
            $table->unsignedBigInteger('caller_id');
            $table->string('callee_type', 32);
            $table->unsignedBigInteger('callee_id');
            $table->string('status', 32)->default('ringing');
            $table->json('offer')->nullable();
            $table->json('answer')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['caller_type', 'caller_id'], 'video_calls_caller_idx');
            $table->index(['callee_type', 'callee_id'], 'video_calls_callee_idx');
            $table->index(['status', 'created_at'], 'video_calls_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_calls');
    }
};
