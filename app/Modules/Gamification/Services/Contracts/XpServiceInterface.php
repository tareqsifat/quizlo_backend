<?php

namespace App\Modules\Gamification\Services\Contracts;

use App\Models\User;

interface XpServiceInterface
{
    public function awardXp(User $user, int $amount, string $reason, ?int $examTypeId = null, ?int $referenceId = null): array;
}
