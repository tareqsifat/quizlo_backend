<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\LessonCompleted;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AwardCoinOnLessonComplete implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CoinServiceInterface $coinService,
        private readonly XpServiceInterface $xpService
    ) {}

    public function handle(LessonCompleted $event): void
    {
        $user = $event->user;
        if ($event->coinsEarned > 0) {
            $this->coinService->awardCoins($user, $event->coinsEarned, 'lesson_complete');
        }

        if ($event->xpEarned > 0) {
            $this->xpService->awardXp(
                $user,
                $event->xpEarned,
                'lesson_complete',
                $event->lesson->exam_type_id,
                $event->lesson->id
            );
        }
    }
}
