<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLeague extends Model
{
    use HasFactory;
    protected $table = 'user_leagues';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'league_season_id',
        'league_tier_id',
        'group_number',
        'weekly_xp',
        'rank',
        'promoted',
        'relegated',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'league_season_id' => 'integer',
        'league_tier_id' => 'integer',
        'group_number' => 'integer',
        'weekly_xp' => 'integer',
        'rank' => 'integer',
        'promoted' => 'boolean',
        'relegated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function season()
    {
        return $this->belongsTo(LeagueSeason::class, 'league_season_id');
    }

    public function tier()
    {
        return $this->belongsTo(LeagueTier::class, 'league_tier_id');
    }
}
