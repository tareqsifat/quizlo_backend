<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_bn',
        'code',
        'slug',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'exam_type_subject')
                    ->withPivot('is_active', 'sort_order', 'total_marks', 'syllabus_note', 'created_at');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_type_question')
                    ->withPivot('source_batch', 'source_year');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_exam_types')
                    ->withPivot('is_primary', 'target_year', 'enrolled_at');
    }

    public function leagueSeasons()
    {
        return $this->hasMany(LeagueSeason::class);
    }

    public function modelTests()
    {
        return $this->hasMany(ModelTest::class);
    }

    public function examSchedules()
    {
        return $this->hasMany(ExamSchedule::class);
    }
}
