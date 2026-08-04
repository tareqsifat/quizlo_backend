<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsToMany(ExamType::class, 'exam_type_subjects')
                    ->using(ExamTypeSubject::class)
                    ->withPivot('id', 'title', 'title_bn', 'is_active', 'sort_order', 'total_marks', 'syllabus_note', 'created_at', 'updated_at');
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function examTypeSubjects(): HasMany
    {
        return $this->hasMany(ExamTypeSubject::class);
    }

    public function masteries(): HasMany
    {
        return $this->hasMany(UserSubjectMastery::class);
    }
}
