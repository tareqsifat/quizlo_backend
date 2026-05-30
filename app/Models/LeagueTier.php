<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeagueTier extends Model
{
    use HasFactory;

    protected $table = 'league_tiers';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_bn',
        'tier_order',
        'color_hex',
        'promotion_spots',
        'relegation_spots',
        'max_members',
    ];

    protected $casts = [
        'tier_order' => 'integer',
        'promotion_spots' => 'integer',
        'relegation_spots' => 'integer',
        'max_members' => 'integer',
    ];

    public function userLeagues()
    {
        return $this->hasMany(UserLeague::class);
    }
}
