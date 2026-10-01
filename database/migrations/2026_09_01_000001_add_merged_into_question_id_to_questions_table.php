<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('merged_into_question_id')
                ->nullable()
                ->after('exam_type_subject_id')
                ->constrained('questions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['merged_into_question_id']);
            $table->dropColumn('merged_into_question_id');
        });
    }
};
