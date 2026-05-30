<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\QuestionAnswered;
use App\Models\LeagueSeason;
use App\Models\LeagueTier;
use App\Models\UserLeague;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateLeagueXp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(QuestionAnswered $event): void
    {
        $answer = $event->userAnswer;
        if ($answer->xp_earned <= 0) {
            return;
        }

        $season = LeagueSeason::where('exam_type_id', $answer->exam_type_id)
            ->where('is_active', true)
            ->first();

        if (!$season) {
            // Auto create a season if none exists for testing/convenience
            $season = LeagueSeason::create([
                'exam_type_id' => $answer->exam_type_id,
                'week_number' => now()->weekOfYear,
                'year' => now()->year,
                'starts_at' => now()->startOfWeek(),
                'ends_at' => now()->endOfWeek(),
                'is_active' => true,
                'processed' => false,
            ]);
        }

        $bronzeTier = LeagueTier::where('tier_order', 1)->first();
        if (!$bronzeTier) {
            $bronzeTier = LeagueTier::create([
                'name' => 'Bronze',
                'name_bn' => 'ব্রোঞ্জ',
                'tier_order' => 1,
                'color_hex' => '#CD7F32',
                'promotion_spots' => 10,
                'relegation_spots' => 0,
                'max_members' => 30,
            ]);
        }

        $userLeague = UserLeague::firstOrCreate([
            'user_id' => $answer->user_id,
            'league_season_id' => $season->id,
        ], [
            'league_tier_id' => $bronzeTier->id,
            'group_number' => 1,
            'weekly_xp' => 0,
        ]);

        $userLeague->increment('weekly_xp', $answer->xp_earned);
    }
}
