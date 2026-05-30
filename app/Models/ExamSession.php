<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamSession extends Model
{
    use HasFactory;
    protected $table = 'exam_sessions';

    public $timestamps = false; // managed by started_at and completed_at

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'model_test_id',
        'session_type',
        'status',
        'total_questions',
        'answered_count',
        'correct_count',
        'score_percent',
        'xp_earned',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exam_type_id' => 'integer',
        'model_test_id' => 'integer',
        'total_questions' => 'integer',
        'answered_count' => 'integer',
        'correct_count' => 'integer',
        'score_percent' => 'decimal:2',
        'xp_earned' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function modelTest()
    {
        return $this->belongsTo(ModelTest::class);
    }
}
