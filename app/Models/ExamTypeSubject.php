<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

/**
 * Single source of truth for the exam-type <-> subject relationship.
 * One row = "Subject X displayed as 'Title Y' under ExamType Z".
 *
 * Doubles as the pivot model for ExamType::subjects() / Subject::examTypes()
 * (via AsPivot) so belongsToMany queries can hydrate it directly.
 *
 * Table: exam_type_subjects
 * Unique: (subject_id, exam_type_id)
 * Unique: (exam_type_id, title)
 */
class ExamTypeSubject extends Model
{
    use HasFactory;
    use AsPivot;

    protected $table = 'exam_type_subjects';

    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'title',
        'title_bn',
        'is_active',
        'sort_order',
        'total_marks',
        'syllabus_note',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'subject_id'   => 'integer',
        'is_active'    => 'boolean',
        'sort_order'   => 'integer',
        'total_marks'  => 'integer',
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
