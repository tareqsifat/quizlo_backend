<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Clear test data ───────────────────────────────
        // Disable FK checks to allow truncation of tables with FK relationships.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('user_answers')->truncate();
        DB::table('exam_type_question')->truncate();
        DB::table('question_options')->truncate();
        DB::table('questions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Step 2: Drop exam_type_question pivot table ───────────
        Schema::dropIfExists('exam_type_question');

        // ── Step 3 & 4: Modify questions table ───────────────────
        Schema::table('questions', function (Blueprint $table) {
            // Drop old FK and column
            $table->dropForeign(['subject_id']);
            $table->dropIndex('idx_subject_difficulty');
            $table->dropColumn('subject_id');

            // Add new FK column
            $table->foreignId('exam_type_subject_id')
                  ->after('id')
                  ->constrained('exam_type_subjects')
                  ->restrictOnDelete();
            $table->index('exam_type_subject_id', 'idx_questions_exam_type_subject');
        });
    }

    public function down(): void
    {
        // Reverse: restore subject_id, drop exam_type_subject_id
        // Note: exam_type_question table is not restored (no live data was lost).
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['exam_type_subject_id']);
            $table->dropIndex('idx_questions_exam_type_subject');
            $table->dropColumn('exam_type_subject_id');

            $table->foreignId('subject_id')
                  ->after('id')
                  ->constrained('subjects');
            $table->index(['subject_id', 'difficulty'], 'idx_subject_difficulty');
        });

        // Re-create exam_type_question pivot
        Schema::create('exam_type_question', function (Blueprint $table) {
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->string('source_batch', 20)->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->primary(['exam_type_id', 'question_id']);
            $table->index('exam_type_id', 'idx_exam_type_question_exam_type_id');
            $table->index('source_batch', 'idx_batch');
        });
    }
};
