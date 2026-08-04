<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Absorbs the legacy exam_type_subject (singular) pivot's metadata
     * columns into exam_type_subjects (plural), which becomes the single
     * table for exam-type <-> subject relationships.
     */
    public function up(): void
    {
        Schema::table('exam_type_subjects', function (Blueprint $table) {
            $table->smallInteger('sort_order')->default(0)->after('is_active');
            $table->smallInteger('total_marks')->nullable()->after('sort_order');
            $table->text('syllabus_note')->nullable()->after('total_marks');
        });
    }

    public function down(): void
    {
        Schema::table('exam_type_subjects', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'total_marks', 'syllabus_note']);
        });
    }
};
