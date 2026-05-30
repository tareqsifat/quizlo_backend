<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\UserStreak;
use App\Modules\Gamification\Repositories\Contracts\StreakRepositoryInterface;

class StreakRepository implements StreakRepositoryInterface
{
    public function findByUser(int $userId): ?UserStreak
    {
        return UserStreak::where('user_id', $userId)->first();
    }

    public function incrementStreak(int $userId): UserStreak
    {
        $streak = UserStreak::firstOrCreate(['user_id' => $userId]);

        $today = now()->toDateString();
        $current = $streak->current_streak + 1;
        $longest = max($streak->longest_streak, $current);

        $streak->update([
            'current_streak' => $current,
            'longest_streak' => $longest,
            'last_activity_date' => $today,
        ]);

        return $streak;
    }

    public function resetStreak(int $userId): void
    {
        UserStreak::where('user_id', $userId)->update([
            'current_streak' => 0,
        ]);
    }

    public function useStreakFreeze(int $userId): bool
    {
        $streak = $this->findByUser($userId);

        if ($streak && $streak->streak_freeze_count > 0 && !$streak->freeze_used_today) {
            $streak->update([
                'streak_freeze_count' => $streak->streak_freeze_count - 1,
                'freeze_used_today' => true,
            ]);
            return true;
        }

        return false;
    }

    public function addStreakFreeze(int $userId, int $count = 1): void
    {
        $streak = UserStreak::firstOrCreate(['user_id' => $userId]);
        $streak->increment('streak_freeze_count', $count);
    }
}
