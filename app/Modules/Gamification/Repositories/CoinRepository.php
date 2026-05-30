<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\UserCoin;
use App\Models\CoinTransaction;
use App\Modules\Gamification\Repositories\Contracts\CoinRepositoryInterface;

class CoinRepository implements CoinRepositoryInterface
{
    public function findByUser(int $userId): ?UserCoin
    {
        return UserCoin::where('user_id', $userId)->first();
    }

    public function updateBalance(int $userId, int $balance): UserCoin
    {
        $coin = UserCoin::firstOrCreate(['user_id' => $userId]);
        $coin->update(['balance' => $balance]);
        return $coin;
    }

    public function logTransaction(int $userId, int $amount, string $type, string $reason): CoinTransaction
    {
        return CoinTransaction::create([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'reason' => $reason,
        ]);
    }
}
