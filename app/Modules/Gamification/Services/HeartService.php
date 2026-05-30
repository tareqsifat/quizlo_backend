<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\HeartServiceInterface;
use App\Modules\Gamification\Repositories\Contracts\HeartRepositoryInterface;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;

class HeartService implements HeartServiceInterface
{
    public function __construct(
        private readonly HeartRepositoryInterface $heartRepository,
        private readonly CoinServiceInterface $coinService
    ) {}

    public function deductHeart(User $user, int $amount = 1): array
    {
        $heart = $this->heartRepository->findByUser($user->id);
        $current = $heart ? $heart->current_hearts : 5;
        $new = max(0, $current - $amount);

        $this->heartRepository->updateHearts($user->id, $new);

        return [
            'success' => true,
            'current_hearts' => $new,
            'max_hearts' => $heart ? $heart->max_hearts : 5,
        ];
    }

    public function refillHearts(User $user): array
    {
        $heart = $this->heartRepository->findByUser($user->id);
        $max = $heart ? $heart->max_hearts : 5;
        
        if ($heart && $heart->current_hearts >= $max) {
            return [
                'success' => false,
                'message' => 'Hearts are already full.',
            ];
        }

        // Deduct 100 coins for refill
        $deducted = $this->coinService->spendCoins($user, 100, 'extra_heart_purchase');

        if (!$deducted['success']) {
            return [
                'success' => false,
                'message' => 'Not enough coins. Heart refill costs 100 coins.',
            ];
        }

        $this->heartRepository->updateHearts($user->id, $max, now());

        return [
            'success' => true,
            'current_hearts' => $max,
            'max_hearts' => $max,
        ];
    }

    public function getHeartsStatus(User $user): array
    {
        $heart = $this->heartRepository->findByUser($user->id);
        return [
            'current_hearts' => $heart ? $heart->current_hearts : 5,
            'max_hearts' => $heart ? $heart->max_hearts : 5,
            'last_refill_at' => $heart ? $heart->last_refill_at : null,
        ];
    }

    public function refillOverTime(): void
    {
        $hearts = \App\Models\UserHeart::where('current_hearts', '<', 5)->get();
        foreach ($hearts as $heart) {
            $heart->increment('current_hearts', 1);
            $heart->update(['last_refill_at' => now()]);
        }
    }
}
