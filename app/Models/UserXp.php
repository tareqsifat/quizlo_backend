<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserXp extends Model
{
    protected $table = 'user_xp';

    public $timestamps = true;
    const CREATED_AT = null; // only updated_at

    protected $fillable = [
        'user_id',
        'total_xp',
        'level',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_xp' => 'integer',
        'level' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
