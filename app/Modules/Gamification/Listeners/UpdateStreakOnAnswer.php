<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\QuestionAnswered;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateStreakOnAnswer implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly StreakServiceInterface $streakService
    ) {}

    public function handle(QuestionAnswered $event): void
    {
        $answer = $event->userAnswer;
        $this->streakService->processUserActivity($answer->user);
    }
}
