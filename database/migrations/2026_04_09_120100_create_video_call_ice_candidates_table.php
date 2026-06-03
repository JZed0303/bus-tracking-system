<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_call_ice_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_call_id')->constrained('video_calls')->cascadeOnDelete();
            $table->string('sender_type', 32);
            $table->unsignedBigInteger('sender_id');
            $table->longText('candidate');
            $table->string('sdp_mid')->nullable();
            $table->integer('sdp_mline_index')->nullable();
            $table->timestamps();

            $table->index(['video_call_id', 'created_at'], 'video_call_ice_created_idx');
            $table->index(['sender_type', 'sender_id'], 'video_call_ice_sender_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_call_ice_candidates');
    }
};
