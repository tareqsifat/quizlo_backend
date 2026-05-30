<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserSubjectMastery extends Model
{
    use HasFactory;
    protected $table = 'user_subject_mastery';

    public $timestamps = true;
    const CREATED_AT = null; // only updated_at

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'subject_id',
        'total_answered',
        'total_correct',
        'mastery_percentage',
        'badge_earned',
        'badge_earned_at',
        'last_activity_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exam_type_id' => 'integer',
        'subject_id' => 'integer',
        'total_answered' => 'integer',
        'total_correct' => 'integer',
        'mastery_percentage' => 'decimal:2',
        'badge_earned' => 'boolean',
        'badge_earned_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
