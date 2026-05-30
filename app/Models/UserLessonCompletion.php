<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLessonCompletion extends Model
{
    protected $table = 'user_lesson_completions';

    public $timestamps = true;
    const UPDATED_AT = null;
    const CREATED_AT = 'completed_at';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'score',
        'xp_earned',
        'coins_earned',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'lesson_id' => 'integer',
        'score' => 'integer',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
