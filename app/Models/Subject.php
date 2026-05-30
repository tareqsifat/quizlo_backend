<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_bn',
        'slug',
        'icon',
        'color_hex',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function examTypes()
    {
        return $this->belongsToMany(ExamType::class, 'exam_type_subject')
                    ->withPivot('is_active', 'sort_order', 'total_marks', 'syllabus_note', 'created_at');
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function masteries()
    {
        return $this->hasMany(UserSubjectMastery::class);
    }
}
