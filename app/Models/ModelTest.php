<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelTest extends Model
{
    use HasFactory;
    protected $table = 'model_tests';

    public $timestamps = true;
    const UPDATED_AT = null; // only created_at is in SQL

    protected $fillable = [
        'exam_type_id',
        'title',
        'title_bn',
        'total_questions',
        'duration_minutes',
        'xp_reward',
        'is_active',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'total_questions' => 'integer',
        'duration_minutes' => 'integer',
        'xp_reward' => 'integer',
        'is_active' => 'boolean',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'model_test_questions')
                    ->withPivot('sort_order');
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}
