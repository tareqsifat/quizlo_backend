<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature 2 entity: exam-type-specific display title for a subject.
 * One row = "Subject X displayed as 'Title Y' under ExamType Z".
 *
 * Table: exam_type_subjects
 * Unique: (subject_id, exam_type_id)
 * Unique: (exam_type_id, title)
 */
class ExamTypeSubject extends Model
{
    use HasFactory;

    protected $table = 'exam_type_subjects';

    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'title',
        'title_bn',
        'is_active',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'subject_id'   => 'integer',
        'is_active'    => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
