<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;
use App\Modules\Gamification\Repositories\Contracts\CoinRepositoryInterface;

class CoinService implements CoinServiceInterface
{
    public function __construct(
        private readonly CoinRepositoryInterface $coinRepository
    ) {}

    public function awardCoins(User $user, int $amount, string $reason): array
    {
        $coin = $this->coinRepository->findByUser($user->id);
        $current = $coin ? $coin->balance : 0;
        $new = $current + $amount;

        $this->coinRepository->updateBalance($user->id, $new);
        $this->coinRepository->logTransaction($user->id, $amount, 'earn', $reason);

        return [
            'success' => true,
            'balance' => $new,
        ];
    }

    public function spendCoins(User $user, int $amount, string $reason): array
    {
        $coin = $this->coinRepository->findByUser($user->id);
        $current = $coin ? $coin->balance : 0;

        if ($current < $amount) {
            return [
                'success' => false,
                'balance' => $current,
                'message' => 'Insufficient coins balance.',
            ];
        }

        $new = $current - $amount;
        $this->coinRepository->updateBalance($user->id, $new);
        $this->coinRepository->logTransaction($user->id, $amount, 'spend', $reason);

        return [
            'success' => true,
            'balance' => $new,
        ];
    }

    public function getBalance(User $user): int
    {
        $coin = $this->coinRepository->findByUser($user->id);
        return $coin ? $coin->balance : 0;
    }
}
