<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type_subject_id',
        'name',
        'name_bn',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'exam_type_subject_id' => 'integer',
    ];

    public function examTypeSubject()
    {
        return $this->belongsTo(ExamTypeSubject::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function userCompletions()
    {
        return $this->hasMany(UserTopicCompletion::class);
    }
}
