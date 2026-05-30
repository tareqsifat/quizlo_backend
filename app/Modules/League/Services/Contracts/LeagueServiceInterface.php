<?php

namespace App\Modules\League\Services\Contracts;

use App\Models\User;

interface LeagueServiceInterface
{
    public function getCurrentStandings(User $user, int $examTypeId): ?array;
    
    public function getLeagueHistory(User $user, int $examTypeId): array;
}
