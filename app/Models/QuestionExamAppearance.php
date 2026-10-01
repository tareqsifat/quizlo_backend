<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExamAppearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'exam_type_id',
        'year',
        'source_batch',
        'created_by',
    ];

    protected $casts = [
        'question_id'  => 'integer',
        'exam_type_id' => 'integer',
        'year'         => 'integer',
        'created_by'   => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
