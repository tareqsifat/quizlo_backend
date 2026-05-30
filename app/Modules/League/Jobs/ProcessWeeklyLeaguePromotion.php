<?php

namespace App\Modules\League\Jobs;

use App\Models\LeagueSeason;
use App\Models\UserLeague;
use App\Models\LeagueTier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWeeklyLeaguePromotion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?int $seasonId = null
    ) {}

    public function handle(): void
    {
        $seasonsQuery = LeagueSeason::query();
        if ($this->seasonId) {
            $seasonsQuery->where('id', $this->seasonId);
        } else {
            $seasonsQuery->where('processed', false)->where('is_active', false);
        }

        $seasons = $seasonsQuery->get();

        foreach ($seasons as $season) {
            // Get all user leagues in this season
            $userLeagues = UserLeague::where('league_season_id', $season->id)->get();
            
            // Group by tier and group number
            $groups = $userLeagues->groupBy(function ($item) {
                return $item->league_tier_id . '-' . $item->group_number;
            });

            foreach ($groups as $key => $groupMembers) {
                // Sort members by weekly_xp descending
                $sorted = $groupMembers->sortByDesc('weekly_xp')->values();
                $totalMembers = $sorted->count();

                foreach ($sorted as $index => $userLeague) {
                    $rank = $index + 1;
                    $tier = $userLeague->tier;

                    $promoted = false;
                    $relegated = false;

                    if ($tier) {
                        // Promotion criteria
                        if ($tier->promotion_spots > 0 && $rank <= $tier->promotion_spots) {
                            $promoted = true;
                        }
                        // Relegation criteria
                        if ($tier->relegation_spots > 0 && $rank > ($totalMembers - $tier->relegation_spots)) {
                            $relegated = true;
                        }
                    }

                    $userLeague->update([
                        'rank' => $rank,
                        'promoted' => $promoted,
                        'relegated' => $relegated,
                    ]);
                }
            }

            $season->update(['processed' => true]);
        }
    }
}
