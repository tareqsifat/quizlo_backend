<?php

namespace Database\Factories;

use App\Models\UserLeague;
use App\Models\User;
use App\Models\LeagueSeason;
use App\Models\LeagueTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserLeagueFactory extends Factory
{
    protected $model = UserLeague::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'league_season_id' => LeagueSeason::factory(),
            'league_tier_id' => LeagueTier::factory(),
            'group_number' => 1,
            'weekly_xp' => 0,
            'rank' => null,
            'promoted' => null,
            'relegated' => null,
        ];
    }
}
