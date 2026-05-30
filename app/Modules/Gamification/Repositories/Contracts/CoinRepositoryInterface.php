<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\UserCoin;
use App\Models\CoinTransaction;

interface CoinRepositoryInterface
{
    public function findByUser(int $userId): ?UserCoin;
    
    public function updateBalance(int $userId, int $balance): UserCoin;
    
    public function logTransaction(int $userId, int $amount, string $type, string $reason): CoinTransaction;
}
