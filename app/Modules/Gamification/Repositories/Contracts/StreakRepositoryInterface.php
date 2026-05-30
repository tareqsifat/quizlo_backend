<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\UserStreak;

interface StreakRepositoryInterface
{
    public function findByUser(int $userId): ?UserStreak;
    
    public function incrementStreak(int $userId): UserStreak;
    
    public function resetStreak(int $userId): void;
    
    public function useStreakFreeze(int $userId): bool;
    
    public function addStreakFreeze(int $userId, int $count = 1): void;
}
