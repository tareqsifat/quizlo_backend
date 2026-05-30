<?php

namespace App\Modules\Gamification\Events;

use App\Models\User;
use App\Models\Lesson;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LessonCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Lesson $lesson,
        public readonly int $score,
        public readonly int $xpEarned,
        public readonly int $coinsEarned
    ) {}
}
