<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('exam_type_subject_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('exam_type_subjects')
                  ->cascadeOnDelete();
        });

        // Backfill: each existing topic's subject_id maps to exactly one
        // exam_type_subjects row today (verified: no Subject spans >1 ExamType yet).
        $topics = DB::table('topics')->select('id', 'subject_id')->get();
        foreach ($topics as $topic) {
            $examTypeSubjectId = DB::table('exam_type_subjects')
                ->where('subject_id', $topic->subject_id)
                ->value('id');

            if ($examTypeSubjectId !== null) {
                DB::table('topics')->where('id', $topic->id)->update([
                    'exam_type_subject_id' => $examTypeSubjectId,
                ]);
            }
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
            $table->foreignId('exam_type_subject_id')->nullable(false)->change();
            $table->unique(['exam_type_subject_id', 'name'], 'uq_topic_exam_subject_name');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropUnique('uq_topic_exam_subject_name');
            $table->foreignId('subject_id')->nullable()->after('id')->constrained('subjects');
        });

        $topics = DB::table('topics')->select('id', 'exam_type_subject_id')->get();
        foreach ($topics as $topic) {
            $subjectId = DB::table('exam_type_subjects')
                ->where('id', $topic->exam_type_subject_id)
                ->value('subject_id');

            if ($subjectId !== null) {
                DB::table('topics')->where('id', $topic->id)->update([
                    'subject_id' => $subjectId,
                ]);
            }
        }

        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable(false)->change();
            $table->dropForeign(['exam_type_subject_id']);
            $table->dropColumn('exam_type_subject_id');
        });
    }
};
