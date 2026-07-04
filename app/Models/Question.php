<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type_subject_id',
        'topic_id',
        'lesson_id',
        'question_text',
        'question_bn',
        'explanation',
        'difficulty',
        'xp_value',
        'is_active',
    ];

    protected $casts = [
        'exam_type_subject_id' => 'integer',
        'topic_id'             => 'integer',
        'lesson_id'            => 'integer',
        'xp_value'             => 'integer',
        'is_active'            => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function examTypeSubject(): BelongsTo
    {
        return $this->belongsTo(ExamTypeSubject::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function modelTests()
    {
        return $this->belongsToMany(ModelTest::class, 'model_test_questions')
                    ->withPivot('sort_order');
    }

    // ─── Convenience Accessors ────────────────────────────────────

    /**
     * Convenience: $question->subject → the underlying Subject via ExamTypeSubject.
     */
    public function getSubjectAttribute(): ?Subject
    {
        return $this->examTypeSubject?->subject;
    }

    /**
     * Convenience: $question->examType → the ExamType via ExamTypeSubject.
     */
    public function getExamTypeAttribute(): ?ExamType
    {
        return $this->examTypeSubject?->examType;
    }
}
