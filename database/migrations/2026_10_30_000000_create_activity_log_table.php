<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            // No FK: the log must survive user deletion and races with the queue.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method', 10);
            $table->string('path', 2048);
            $table->string('route')->nullable();
            $table->string('action')->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
