<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHeart extends Model
{
    protected $table = 'user_hearts';

    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'current_hearts',
        'max_hearts',
        'last_refill_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'current_hearts' => 'integer',
        'max_hearts' => 'integer',
        'last_refill_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
