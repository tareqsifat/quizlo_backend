<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCoin extends Model
{
    protected $table = 'user_coins';

    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'balance' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
