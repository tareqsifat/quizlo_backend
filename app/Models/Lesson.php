<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'topic_id',
        'title',
        'title_bn',
        'description',
        'xp_reward',
        'coin_reward',
        'difficulty',
        'question_count',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'subject_id' => 'integer',
        'topic_id' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'question_count' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function userCompletions()
    {
        return $this->hasMany(UserLessonCompletion::class);
    }
}
