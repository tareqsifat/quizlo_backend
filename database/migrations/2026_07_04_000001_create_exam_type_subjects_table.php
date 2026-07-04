<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_type_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')
                  ->constrained('exam_types')
                  ->onDelete('cascade');
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->onDelete('cascade');
            $table->string('title', 150);
            $table->string('title_bn', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // A subject can only be mapped once per exam type
            $table->unique(['subject_id', 'exam_type_id'], 'uq_exam_type_subject');
            // No two subjects share the same title within an exam type
            $table->unique(['exam_type_id', 'title'], 'uq_exam_type_title');
            $table->index('exam_type_id', 'idx_exam_type_subjects_exam_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_type_subjects');
    }
};
