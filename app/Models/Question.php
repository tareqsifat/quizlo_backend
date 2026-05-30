<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
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
        'subject_id' => 'integer',
        'topic_id' => 'integer',
        'lesson_id' => 'integer',
        'xp_value' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function examTypes()
    {
        return $this->belongsToMany(ExamType::class, 'exam_type_question')
                    ->withPivot('source_batch', 'source_year');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function modelTests()
    {
        return $this->belongsToMany(ModelTest::class, 'model_test_questions')
                    ->withPivot('sort_order');
    }
}
