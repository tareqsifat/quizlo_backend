<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserXp;
use App\Models\UserStreak;
use App\Models\UserHeart;
use App\Models\UserCoin;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        UserXp::create([
            'user_id' => $user->id,
            'total_xp' => 0,
            'level' => 1,
        ]);

        UserStreak::create([
            'user_id' => $user->id,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null,
            'freeze_used_today' => false,
            'streak_freeze_count' => 0,
        ]);

        UserHeart::create([
            'user_id' => $user->id,
            'current_hearts' => 5,
            'max_hearts' => 5,
            'last_refill_at' => now(),
        ]);

        UserCoin::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }
}
