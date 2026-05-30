<?php

namespace App\Modules\Gamification\Services\Contracts;

use App\Models\User;

interface CoinServiceInterface
{
    public function awardCoins(User $user, int $amount, string $reason): array;
    
    public function spendCoins(User $user, int $amount, string $reason): array;
    
    public function getBalance(User $user): int;
}
