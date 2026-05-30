<?php

namespace App\Modules\League\Services;

use App\Models\User;
use App\Modules\League\Services\Contracts\LeagueServiceInterface;
use App\Modules\League\Repositories\Contracts\LeagueRepositoryInterface;

class LeagueService implements LeagueServiceInterface
{
    public function __construct(
        private readonly LeagueRepositoryInterface $leagueRepository
    ) {}

    public function getCurrentStandings(User $user, int $examTypeId): ?array
    {
        $season = $this->leagueRepository->findActiveSeason($examTypeId);
        if (!$season) {
            return null;
        }

        $userLeague = $this->leagueRepository->findUserLeague($user->id, $season->id);
        if (!$userLeague) {
            return null;
        }

        $standings = $this->leagueRepository->getGroupStandings(
            $season->id,
            $userLeague->league_tier_id,
            $userLeague->group_number
        );

        $formattedStandings = [];
        $userRank = 1;
        $idx = 1;

        foreach ($standings as $row) {
            $formattedStandings[] = [
                'user_id' => $row->user_id,
                'name' => $row->user ? $row->user->name : 'Unknown',
                'weekly_xp' => $row->weekly_xp,
                'rank' => $idx,
            ];

            if ($row->user_id === $user->id) {
                $userRank = $idx;
            }

            $idx++;
        }

        $tier = $userLeague->tier;

        return [
            'tier' => $tier ? $tier->name : 'Bronze',
            'group_number' => $userLeague->group_number,
            'weekly_xp' => $userLeague->weekly_xp,
            'rank' => $userRank,
            'standings' => $formattedStandings,
            'promotion_spots' => $tier ? $tier->promotion_spots : 10,
            'relegation_spots' => $tier ? $tier->relegation_spots : 5,
        ];
    }

    public function getLeagueHistory(User $user, int $examTypeId): array
    {
        $history = $this->leagueRepository->getUserHistory($user->id, $examTypeId);

        $formatted = [];
        foreach ($history as $row) {
            $formatted[] = [
                'week_number' => $row->season->week_number,
                'year' => $row->season->year,
                'tier' => $row->tier ? $row->tier->name : 'Bronze',
                'weekly_xp' => $row->weekly_xp,
                'rank' => $row->rank,
                'promoted' => (bool) $row->promoted,
                'relegated' => (bool) $row->relegated,
            ];
        }

        return $formatted;
    }
}
