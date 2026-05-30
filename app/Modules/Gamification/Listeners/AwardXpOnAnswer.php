<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\QuestionAnswered;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AwardXpOnAnswer implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly XpServiceInterface $xpService
    ) {}

    public function handle(QuestionAnswered $event): void
    {
        $answer = $event->userAnswer;
        if ($answer->xp_earned > 0) {
            $this->xpService->awardXp(
                $answer->user,
                $answer->xp_earned,
                'correct_answer',
                $answer->exam_type_id,
                $answer->id
            );
        }
    }
}
