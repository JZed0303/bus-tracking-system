<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_call_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_call_id')->constrained('video_calls')->cascadeOnDelete();
            $table->string('recorded_by_type', 32)->nullable();
            $table->unsignedBigInteger('recorded_by_id')->nullable();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['video_call_id', 'created_at'], 'video_call_recordings_call_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_call_recordings');
    }
};
