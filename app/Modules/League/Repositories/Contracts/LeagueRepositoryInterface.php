<?php

namespace App\Modules\League\Repositories\Contracts;

use App\Models\LeagueSeason;
use App\Models\UserLeague;
use Illuminate\Support\Collection;

interface LeagueRepositoryInterface
{
    public function findActiveSeason(int $examTypeId): ?LeagueSeason;
    
    public function findUserLeague(int $userId, int $seasonId): ?UserLeague;
    
    public function getGroupStandings(int $seasonId, int $tierId, int $groupNumber): Collection;
    
    public function getUserHistory(int $userId, int $examTypeId): Collection;
}
