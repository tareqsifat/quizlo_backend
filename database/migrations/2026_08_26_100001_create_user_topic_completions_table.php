<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_topic_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('topic_id')->constrained('topics');
            $table->tinyInteger('score')->unsigned();
            $table->smallInteger('xp_earned')->default(0);
            $table->smallInteger('coins_earned')->default(0);
            $table->timestamp('completed_at')->useCurrent();

            $table->unique(['user_id', 'topic_id'], 'uq_user_topic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_topic_completions');
    }
};
