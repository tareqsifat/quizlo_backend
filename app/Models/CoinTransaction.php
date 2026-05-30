<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    protected $table = 'coin_transactions';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reason',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
