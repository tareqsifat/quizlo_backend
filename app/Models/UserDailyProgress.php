<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserDailyProgress extends Model
{
    use HasFactory;
    protected $table = 'user_daily_progress';

    public $timestamps = false; // date manages it

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'date',
        'goal_questions',
        'answered_questions',
        'correct_questions',
        'xp_earned_today',
        'goal_met',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exam_type_id' => 'integer',
        'date' => 'date',
        'goal_questions' => 'integer',
        'answered_questions' => 'integer',
        'correct_questions' => 'integer',
        'xp_earned_today' => 'integer',
        'goal_met' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }
}
