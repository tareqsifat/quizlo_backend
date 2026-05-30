<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserAchievement extends Pivot
{
    protected $table = 'user_achievements';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'earned_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'achievement_id' => 'integer',
        'earned_at' => 'datetime',
    ];
}
