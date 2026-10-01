<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Question extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'exam_type_subject_id',
        'topic_id',
        'lesson_id',
        'question_type',
        'question_text',
        'question_bn',
        'explanation',
        'audio_url',
        'difficulty',
        'xp_value',
        'is_active',
        'verified_by_ai',
        'checked_by_human',
        'rejection_reason',
        'merged_into_question_id',
    ];

    protected $casts = [
        'exam_type_subject_id'     => 'integer',
        'topic_id'                 => 'integer',
        'lesson_id'                => 'integer',
        'xp_value'                 => 'integer',
        'is_active'                => 'boolean',
        'verified_by_ai'           => 'boolean',
        'checked_by_human'         => 'boolean',
        'merged_into_question_id'  => 'integer',
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

    public function appearances(): HasMany
    {
        return $this->hasMany(QuestionExamAppearance::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'merged_into_question_id');
    }

    // ─── Scout (Meilisearch) ────────────────────────────────────────

    /**
     * Only index the fields actually used for similarity matching.
     * search-similar re-fetches full rows from MySQL by ID, so options/
     * appearances don't need to be duplicated into the search index.
     */
    public function toSearchableArray(): array
    {
        return [
            'id'            => $this->id,
            'question_text' => $this->question_text,
            'question_bn'   => $this->question_bn,
        ];
    }

    /**
     * Merged-away and inactive questions shouldn't surface as "similar" matches.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active && $this->merged_into_question_id === null;
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
