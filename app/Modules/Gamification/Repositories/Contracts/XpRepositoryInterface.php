<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\UserXp;
use App\Models\XpTransaction;

interface XpRepositoryInterface
{
    public function findByUser(int $userId): ?UserXp;
    
    public function updateXp(int $userId, int $newXp, int $newLevel): UserXp;
    
    public function logTransaction(int $userId, int $amount, string $reason, ?int $examTypeId = null, ?int $referenceId = null): XpTransaction;
}
