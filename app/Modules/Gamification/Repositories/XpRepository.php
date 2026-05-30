<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\UserXp;
use App\Models\XpTransaction;
use App\Modules\Gamification\Repositories\Contracts\XpRepositoryInterface;

class XpRepository implements XpRepositoryInterface
{
    public function findByUser(int $userId): ?UserXp
    {
        return UserXp::where('user_id', $userId)->first();
    }

    public function updateXp(int $userId, int $newXp, int $newLevel): UserXp
    {
        $xp = UserXp::firstOrCreate(['user_id' => $userId]);
        $xp->update([
            'total_xp' => $newXp,
            'level' => $newLevel,
        ]);
        return $xp;
    }

    public function logTransaction(int $userId, int $amount, string $reason, ?int $examTypeId = null, ?int $referenceId = null): XpTransaction
    {
        return XpTransaction::create([
            'user_id' => $userId,
            'exam_type_id' => $examTypeId,
            'amount' => $amount,
            'reason' => $reason,
            'reference_id' => $referenceId,
        ]);
    }
}
