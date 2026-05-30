<?php

namespace Tests\Feature\League;

use Tests\TestCase;
use App\Models\User;
use App\Models\LeagueSeason;
use App\Models\LeagueTier;
use App\Models\UserLeague;
use App\Models\ExamType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeagueStandingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_can_see_their_current_league_standings(): void
    {
        $user   = User::factory()->create();
        $season = LeagueSeason::factory()->active()->bcs()->create();
        $tier   = LeagueTier::factory()->bronze()->create();

        UserLeague::factory()->create([
            'user_id'          => $user->id,
            'league_season_id' => $season->id,
            'league_tier_id'   => $tier->id,
            'group_number'     => 1,
            'weekly_xp'        => 150,
        ]);

        $this->actingAsUser($user)
             ->getJson('/api/v1/league/current?exam_type_id=' . $season->exam_type_id)
             ->assertOk()
             ->assertJsonStructure([
                 'data' => [
                     'tier',
                     'group_number',
                     'weekly_xp',
                     'rank',
                     'standings' => [['user_id', 'name', 'weekly_xp', 'rank']],
                     'promotion_spots',
                     'relegation_spots',
                 ],
             ]);
    }

    /** @test */
    public function test_league_standings_are_exam_type_scoped(): void
    {
        $user    = User::factory()->create();
        $bcs     = ExamType::factory()->create();
        $ssc     = ExamType::factory()->create();
        $bcsSeason = LeagueSeason::factory()->active()->create(['exam_type_id' => $bcs->id]);
        $sscSeason = LeagueSeason::factory()->active()->create(['exam_type_id' => $ssc->id]);

        // Only enrolled in BCS league
        UserLeague::factory()->create([
            'user_id'          => $user->id,
            'league_season_id' => $bcsSeason->id,
        ]);

        $this->actingAsUser($user)
             ->getJson('/api/v1/league/current?exam_type_id=' . $ssc->id)
             ->assertOk()
             ->assertJson(['data' => null]);            // not enrolled in SSC league
    }
}
