<?php

namespace App\Modules\Gamification\Services\Contracts;

use App\Models\User;

interface StreakServiceInterface
{
    public function processUserActivity(User $user): array;
    public function getStreakStatus(User $user): array;
    public function useStreakFreeze(User $user): bool;
    public function addStreakFreeze(User $user, int $count = 1): void;
}
