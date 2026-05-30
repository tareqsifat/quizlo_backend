<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\QuestionAnswered;
use App\Modules\Gamification\Services\Contracts\HeartServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DeductHeartOnWrongAnswer implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly HeartServiceInterface $heartService
    ) {}

    public function handle(QuestionAnswered $event): void
    {
        $answer = $event->userAnswer;
        if (!$answer->is_correct) {
            $this->heartService->deductHeart($answer->user, 1);
        }
    }
}
