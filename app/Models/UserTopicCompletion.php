<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTopicCompletion extends Model
{
    protected $table = 'user_topic_completions';

    public $timestamps = true;
    const UPDATED_AT = null;
    const CREATED_AT = 'completed_at';

    protected $fillable = [
        'user_id',
        'topic_id',
        'score',
        'xp_earned',
        'coins_earned',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'topic_id' => 'integer',
        'score' => 'integer',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
}
