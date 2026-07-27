<?php

namespace Tests\Feature\League;

use Tests\TestCase;
use App\Models\User;
use App\Models\LeagueSeason;
use App\Models\LeagueTier;
use App\Models\UserLeague;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeagueLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_inside_top_range_appears_in_entries_without_separate_current_user_block(): void
    {
        $user   = User::factory()->create();
        $season = LeagueSeason::factory()->active()->bcs()->create();
        $tier   = LeagueTier::factory()->bronze()->create();

        UserLeague::factory()->create([
            'user_id' => $user->id,
            'league_season_id' => $season->id,
            'league_tier_id' => $tier->id,
            'group_number' => 1,
            'weekly_xp' => 500,
        ]);

        // A couple of lower-ranked peers in the same group
        UserLeague::factory()->count(2)->create([
            'league_season_id' => $season->id,
            'league_tier_id' => $tier->id,
            'group_number' => 1,
            'weekly_xp' => 100,
        ]);

        $this->actingAsUser($user)
            ->getJson('/api/v1/league/leaderboard?exam_type_id=' . $season->exam_type_id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'week_number',
                    'year',
                    'tier',
                    'entries' => [['rank', 'user_id', 'name', 'xp', 'is_current_user']],
                    'current_user',
                    'total_participants',
                ],
            ])
            ->assertJsonPath('data.entries.0.user_id', $user->id)
            ->assertJsonPath('data.entries.0.rank', 1)
            ->assertJsonPath('data.entries.0.is_current_user', true)
            ->assertJsonPath('data.current_user', null)
            ->assertJsonPath('data.total_participants', 3);
    }

    /** @test */
    public function test_user_outside_top_range_is_appended_as_current_user_with_live_rank(): void
    {
        // Force a tiny cap so the current user's real rank (#4) falls outside it.
        config(['quizlo.league.leaderboard_limit' => 2]);

        $user   = User::factory()->create();
        $season = LeagueSeason::factory()->active()->bcs()->create();
        $tier   = LeagueTier::factory()->bronze()->create();

        // 3 peers ranked above the current user
        UserLeague::factory()->count(3)->create([
            'league_season_id' => $season->id,
            'league_tier_id' => $tier->id,
            'group_number' => 1,
            'weekly_xp' => 900,
        ]);

        UserLeague::factory()->create([
            'user_id' => $user->id,
            'league_season_id' => $season->id,
            'league_tier_id' => $tier->id,
            'group_number' => 1,
            'weekly_xp' => 10,
        ]);

        $this->actingAsUser($user)
            ->getJson('/api/v1/league/leaderboard?exam_type_id=' . $season->exam_type_id)
            ->assertOk()
            ->assertJsonCount(2, 'data.entries')
            ->assertJsonPath('data.current_user.rank', 4)
            ->assertJsonPath('data.current_user.user_id', $user->id)
            ->assertJsonPath('data.current_user.xp', 10)
            ->assertJsonPath('data.total_participants', 4);
    }

    /** @test */
    public function test_leaderboard_is_null_when_user_has_not_joined_a_league_this_week(): void
    {
        $user   = User::factory()->create();
        $season = LeagueSeason::factory()->active()->bcs()->create();

        $this->actingAsUser($user)
            ->getJson('/api/v1/league/leaderboard?exam_type_id=' . $season->exam_type_id)
            ->assertOk()
            ->assertJson(['data' => null]);
    }
}
