<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_exam_appearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('exam_type_id')->constrained('exam_types')->restrictOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('source_batch', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['question_id', 'exam_type_id', 'year'], 'uq_question_exam_year');
            $table->index(['exam_type_id', 'year'], 'idx_exam_type_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_exam_appearances');
    }
};
