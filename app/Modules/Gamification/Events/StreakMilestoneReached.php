<?php

namespace App\Modules\Gamification\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreakMilestoneReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $milestoneDay
    ) {}
}
