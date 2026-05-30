<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\QuestionAnswered;
use App\Modules\Gamification\Services\Contracts\MasteryServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateSubjectMastery implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly MasteryServiceInterface $masteryService
    ) {}

    public function handle(QuestionAnswered $event): void
    {
        $answer = $event->userAnswer;
        $question = $answer->question;
        if ($question) {
            $this->masteryService->updateMastery(
                $answer->user,
                $answer->exam_type_id,
                $question->subject_id,
                $answer->is_correct
            );
        }
    }
}
