<?php

namespace App\Modules\League\Repositories;

use App\Models\LeagueSeason;
use App\Models\UserLeague;
use App\Modules\League\Repositories\Contracts\LeagueRepositoryInterface;
use Illuminate\Support\Collection;

class LeagueRepository implements LeagueRepositoryInterface
{
    public function findActiveSeason(int $examTypeId): ?LeagueSeason
    {
        return LeagueSeason::where('exam_type_id', $examTypeId)
            ->where('is_active', true)
            ->first();
    }

    public function findUserLeague(int $userId, int $seasonId): ?UserLeague
    {
        return UserLeague::where('user_id', $userId)
            ->where('league_season_id', $seasonId)
            ->first();
    }

    public function getGroupStandings(int $seasonId, int $tierId, int $groupNumber): Collection
    {
        return UserLeague::with('user')
            ->where('league_season_id', $seasonId)
            ->where('league_tier_id', $tierId)
            ->where('group_number', $groupNumber)
            ->orderBy('weekly_xp', 'desc')
            ->get();
    }

    public function getUserHistory(int $userId, int $examTypeId): Collection
    {
        return UserLeague::with(['season', 'tier'])
            ->where('user_id', $userId)
            ->whereHas('season', function ($query) use ($examTypeId) {
                $query->where('exam_type_id', $examTypeId)->where('processed', true);
            })
            ->get();
    }
}
