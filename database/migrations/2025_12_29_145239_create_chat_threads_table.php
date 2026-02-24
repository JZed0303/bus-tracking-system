<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();

            // scope thread to company (bus support belongs to a company)
            $table->unsignedBigInteger('company_id')->nullable();

            // direct/group
            $table->enum('type', ['direct', 'group'])->default('direct');

            // title for group threads
            $table->string('title')->nullable();

            // creator can be user or bus (polymorphic)
            $table->string('created_by_type');
            $table->unsignedBigInteger('created_by_id');

            // context for separate systems
            // Example: context_type = 'bus_support', context_id = <bus_id>
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index(['context_type', 'context_id']);
            $table->index(['created_by_type', 'created_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
