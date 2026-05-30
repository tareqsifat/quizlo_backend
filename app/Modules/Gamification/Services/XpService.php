<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use App\Modules\Gamification\Repositories\Contracts\XpRepositoryInterface;

class XpService implements XpServiceInterface
{
    public function __construct(
        private readonly XpRepositoryInterface $xpRepository
    ) {}

    public function awardXp(User $user, int $amount, string $reason, ?int $examTypeId = null, ?int $referenceId = null): array
    {
        $xp = $this->xpRepository->findByUser($user->id);
        $currentXp = $xp ? $xp->total_xp : 0;
        $newXp = $currentXp + $amount;

        // Level formula: 100 XP per level
        $newLevel = (int) (floor($newXp / 100) + 1);
        $levelUp = $xp ? ($newLevel > $xp->level) : false;

        $this->xpRepository->updateXp($user->id, $newXp, $newLevel);
        $this->xpRepository->logTransaction($user->id, $amount, $reason, $examTypeId, $referenceId);

        return [
            'success' => true,
            'xp_awarded' => $amount,
            'total_xp' => $newXp,
            'level' => $newLevel,
            'level_up' => $levelUp,
        ];
    }
}
