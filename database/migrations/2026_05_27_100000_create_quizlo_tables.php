<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. exam_types
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_bn', 100);
            $table->string('code', 30)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. subjects
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('name_bn', 150);
            $table->string('slug', 150)->unique();
            $table->string('icon', 50)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. exam_type_subject (pivot)
        Schema::create('exam_type_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->smallInteger('total_marks')->nullable();
            $table->text('syllabus_note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['exam_type_id', 'subject_id'], 'uq_exam_subject');
            $table->index('exam_type_id', 'idx_exam_type_subject_exam_type');
        });

        // 4. topics
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name', 200);
            $table->string('name_bn', 200);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. user_exam_types (pivot)
        Schema::create('user_exam_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('target_year')->nullable();
            $table->timestamp('enrolled_at')->useCurrent();

            $table->unique(['user_id', 'exam_type_id'], 'uq_user_exam');
            $table->index('user_id', 'idx_user_exam_types_user_id');
            $table->index('exam_type_id', 'idx_user_exam_types_exam_type_id');
        });

        // 6. otp_verifications
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15);
            $table->string('otp_code', 6);
            $table->enum('purpose', ['login', 'register'])->default('login');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phone', 'purpose'], 'idx_phone_purpose');
            $table->index('expires_at', 'idx_expires');
        });

        // 7. lessons
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('topic_id')->nullable()->constrained('topics');
            $table->string('title', 200);
            $table->string('title_bn', 200);
            $table->text('description')->nullable();
            $table->smallInteger('xp_reward')->default(20);
            $table->smallInteger('coin_reward')->default(10);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->tinyInteger('question_count')->default(10);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['exam_type_id', 'subject_id'], 'idx_exam_subject');
        });

        // 8. questions
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('topic_id')->nullable()->constrained('topics');
            $table->foreignId('lesson_id')->nullable()->constrained('lessons');
            $table->text('question_text');
            $table->text('question_bn')->nullable();
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->tinyInteger('xp_value')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subject_id', 'difficulty'], 'idx_subject_difficulty');
        });

        // 9. exam_type_question (pivot)
        Schema::create('exam_type_question', function (Blueprint $table) {
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->string('source_batch', 20)->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();

            $table->primary(['exam_type_id', 'question_id']);
            $table->index('exam_type_id', 'idx_exam_type_question_exam_type_id');
            $table->index('source_batch', 'idx_batch');
        });

        // 10. question_options
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->text('option_text');
            $table->text('option_text_bn')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->tinyInteger('sort_order')->default(0);

            $table->index('question_id', 'idx_question');
        });

        // 11. user_answers
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->foreignId('question_id')->constrained('questions');
            $table->foreignId('selected_option_id')->constrained('question_options');
            $table->boolean('is_correct');
            $table->unsignedSmallInteger('time_taken_ms')->nullable();
            $table->tinyInteger('xp_earned')->default(0);
            $table->enum('session_type', ['lesson', 'model_test', 'practice', 'exam_mode']);
            $table->bigInteger('session_id')->unsigned()->nullable();
            $table->timestamp('answered_at')->useCurrent();

            $table->index(['user_id', 'exam_type_id', 'answered_at'], 'idx_user_exam_date');
            $table->index(['user_id', 'question_id'], 'idx_user_question');
        });

        // 12. user_subject_mastery
        Schema::create('user_subject_mastery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->integer('total_answered')->default(0);
            $table->integer('total_correct')->default(0);
            $table->decimal('mastery_percentage', 5, 2)->default(0.00);
            $table->boolean('badge_earned')->default(false);
            $table->timestamp('badge_earned_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->unique(['user_id', 'exam_type_id', 'subject_id'], 'uq_user_exam_subject');
            $table->index(['user_id', 'exam_type_id'], 'idx_user_exam');
        });

        // 13. user_daily_progress
        Schema::create('user_daily_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->date('date');
            $table->tinyInteger('goal_questions')->default(20);
            $table->tinyInteger('answered_questions')->default(0);
            $table->tinyInteger('correct_questions')->default(0);
            $table->smallInteger('xp_earned_today')->default(0);
            $table->boolean('goal_met')->default(false);

            $table->unique(['user_id', 'exam_type_id', 'date'], 'uq_user_exam_date');
            $table->index('date', 'idx_date');
        });

        // 14. user_lesson_completions
        Schema::create('user_lesson_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('lesson_id')->constrained('lessons');
            $table->tinyInteger('score')->unsigned();
            $table->smallInteger('xp_earned')->default(0);
            $table->smallInteger('coins_earned')->default(0);
            $table->timestamp('completed_at')->useCurrent();

            $table->unique(['user_id', 'lesson_id'], 'uq_user_lesson');
        });

        // 15. user_xp
        Schema::create('user_xp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->integer('total_xp')->default(0);
            $table->smallInteger('level')->default(1);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });

        // 16. xp_transactions
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types');
            $table->smallInteger('amount');
            $table->enum('reason', [
                'correct_answer',
                'lesson_complete',
                'streak_bonus',
                'daily_goal_met',
                'model_test_complete',
                'league_promotion',
                'streak_milestone'
            ]);
            $table->bigInteger('reference_id')->unsigned()->nullable();
            $table->timestamp('earned_at')->useCurrent();

            $table->index(['user_id', 'earned_at'], 'idx_user_date');
        });

        // 17. user_streaks
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->smallInteger('current_streak')->default(0);
            $table->smallInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->boolean('freeze_used_today')->default(false);
            $table->tinyInteger('streak_freeze_count')->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });

        // 18. user_hearts
        Schema::create('user_hearts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->tinyInteger('current_hearts')->default(5);
            $table->tinyInteger('max_hearts')->default(5);
            $table->timestamp('last_refill_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });

        // 19. user_coins
        Schema::create('user_coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->integer('balance')->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });

        // 20. coin_transactions
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->smallInteger('amount');
            $table->enum('type', ['earn', 'spend']);
            $table->enum('reason', [
                'lesson_complete',
                'streak_milestone',
                'daily_goal_met',
                'streak_freeze_purchase',
                'extra_heart_purchase',
                'hint_purchase'
            ]);
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'idx_coin_transactions_user_id');
        });

        // 21. achievements
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('title', 150);
            $table->string('title_bn', 150);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->smallInteger('xp_reward')->default(0);
            $table->enum('type', ['streak', 'subject', 'exam', 'social', 'daily']);
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types');
            $table->timestamp('created_at')->useCurrent();
        });

        // 22. user_achievements (pivot)
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained('achievements')->onDelete('cascade');
            $table->timestamp('earned_at')->useCurrent();

            $table->unique(['user_id', 'achievement_id'], 'uq_user_achievement');
        });

        // 23. league_tiers
        Schema::create('league_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('name_bn', 50);
            $table->tinyInteger('tier_order')->unique();
            $table->string('color_hex', 7)->nullable();
            $table->tinyInteger('promotion_spots')->default(10);
            $table->tinyInteger('relegation_spots')->default(5);
            $table->tinyInteger('max_members')->default(30);
        });

        // 24. league_seasons
        Schema::create('league_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->smallInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('processed')->default(false);

            $table->unique(['exam_type_id', 'week_number', 'year'], 'uq_exam_week_year');
        });

        // 25. user_leagues
        Schema::create('user_leagues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('league_season_id')->constrained('league_seasons');
            $table->foreignId('league_tier_id')->constrained('league_tiers');
            $table->smallInteger('group_number');
            $table->integer('weekly_xp')->default(0);
            $table->smallInteger('rank')->nullable();
            $table->boolean('promoted')->nullable();
            $table->boolean('relegated')->nullable();

            $table->unique(['user_id', 'league_season_id'], 'uq_user_season');
            $table->index(['league_season_id', 'league_tier_id', 'group_number'], 'idx_season_tier_group');
        });

        // 26. model_tests
        Schema::create('model_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->string('title', 200);
            $table->string('title_bn', 200);
            $table->smallInteger('total_questions')->default(100);
            $table->smallInteger('duration_minutes')->default(60);
            $table->smallInteger('xp_reward')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        // 27. model_test_questions (pivot)
        Schema::create('model_test_questions', function (Blueprint $table) {
            $table->foreignId('model_test_id')->constrained('model_tests')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->smallInteger('sort_order')->default(0);

            $table->primary(['model_test_id', 'question_id']);
        });

        // 28. exam_sessions
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->foreignId('model_test_id')->nullable()->constrained('model_tests');
            $table->enum('session_type', ['model_test', 'practice', 'exam_mode']);
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->smallInteger('total_questions');
            $table->smallInteger('answered_count')->default(0);
            $table->smallInteger('correct_count')->default(0);
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->smallInteger('xp_earned')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });

        // 29. exam_schedules
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types');
            $table->string('batch_label', 50)->nullable();
            $table->enum('exam_stage', ['preliminary', 'written', 'viva', 'main']);
            $table->date('scheduled_date');
            $table->boolean('is_confirmed')->default(false);
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['exam_type_id', 'scheduled_date'], 'idx_exam_type_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('model_test_questions');
        Schema::dropIfExists('model_tests');
        Schema::dropIfExists('user_leagues');
        Schema::dropIfExists('league_seasons');
        Schema::dropIfExists('league_tiers');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('coin_transactions');
        Schema::dropIfExists('user_coins');
        Schema::dropIfExists('user_hearts');
        Schema::dropIfExists('user_streaks');
        Schema::dropIfExists('xp_transactions');
        Schema::dropIfExists('user_xp');
        Schema::dropIfExists('user_lesson_completions');
        Schema::dropIfExists('user_daily_progress');
        Schema::dropIfExists('user_subject_mastery');
        Schema::dropIfExists('user_answers');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('exam_type_question');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('user_exam_types');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('exam_type_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('exam_types');
    }
};
