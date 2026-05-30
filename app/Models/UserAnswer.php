<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAnswer extends Model
{
    use HasFactory;
    public $timestamps = true;
    const UPDATED_AT = null;
    const CREATED_AT = 'answered_at';

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'question_id',
        'selected_option_id',
        'is_correct',
        'time_taken_ms',
        'xp_earned',
        'session_type',
        'session_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exam_type_id' => 'integer',
        'question_id' => 'integer',
        'selected_option_id' => 'integer',
        'is_correct' => 'boolean',
        'time_taken_ms' => 'integer',
        'xp_earned' => 'integer',
        'session_id' => 'integer',
        'answered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
