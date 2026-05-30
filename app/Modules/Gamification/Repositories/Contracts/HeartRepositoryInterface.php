<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\UserHeart;

interface HeartRepositoryInterface
{
    public function findByUser(int $userId): ?UserHeart;
    
    public function updateHearts(int $userId, int $hearts, ?string $lastRefillAt = null): UserHeart;
}
