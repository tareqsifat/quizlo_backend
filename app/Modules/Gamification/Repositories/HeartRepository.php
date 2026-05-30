<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\UserHeart;
use App\Modules\Gamification\Repositories\Contracts\HeartRepositoryInterface;

class HeartRepository implements HeartRepositoryInterface
{
    public function findByUser(int $userId): ?UserHeart
    {
        return UserHeart::where('user_id', $userId)->first();
    }

    public function updateHearts(int $userId, int $hearts, ?string $lastRefillAt = null): UserHeart
    {
        $heart = UserHeart::firstOrCreate(['user_id' => $userId]);
        $data = ['current_hearts' => $hearts];
        if ($lastRefillAt) {
            $data['last_refill_at'] = $lastRefillAt;
        }
        $heart->update($data);
        return $heart;
    }
}
