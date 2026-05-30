<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStreak extends Model
{
    protected $table = 'user_streaks';

    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'freeze_used_today',
        'streak_freeze_count',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'freeze_used_today' => 'boolean',
        'streak_freeze_count' => 'integer',
        'last_activity_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
