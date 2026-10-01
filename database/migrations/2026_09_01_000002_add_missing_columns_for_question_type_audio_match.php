<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-existing bug fix: Question::$fillable already listed 'question_type'
 * and 'audio_url', and QuestionOption::$fillable already listed 'match_text',
 * but no migration ever created these columns — every storeQuestion/
 * updateQuestion call that touched options failed with a DB error. These
 * columns were the intended target of already-shipped model/controller code,
 * so this migration just catches the schema up to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type', 30)->default('mcq')->after('exam_type_subject_id');
            $table->string('audio_url')->nullable()->after('explanation');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->string('match_text')->nullable()->after('option_text_bn');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_type', 'audio_url']);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('match_text');
        });
    }
};
