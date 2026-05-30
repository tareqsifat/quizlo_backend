<?php

namespace App\Modules\Gamification\Listeners;

use App\Modules\Gamification\Events\QuestionAnswered;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateDailyProgress implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly XpServiceInterface $xpService,
        private readonly CoinServiceInterface $coinService
    ) {}

    public function handle(QuestionAnswered $event): void
    {
        $answer = $event->userAnswer;
        $user = $answer->user;
        $date = now()->toDateString();

        $progress = \App\Models\UserDailyProgress::firstOrCreate([
            'user_id' => $user->id,
            'exam_type_id' => $answer->exam_type_id,
            'date' => $date,
        ], [
            'goal_questions' => $user->daily_goal ?? 20,
            'answered_questions' => 0,
            'correct_questions' => 0,
            'xp_earned_today' => 0,
            'goal_met' => false,
        ]);

        $progress->answered_questions += 1;
        if ($answer->is_correct) {
            $progress->correct_questions += 1;
        }
        $progress->xp_earned_today += $answer->xp_earned;

        if ($progress->answered_questions >= $progress->goal_questions && !$progress->goal_met) {
            $progress->goal_met = true;
            
            $xpBonus = config('quizlo.gamification.xp_daily_goal_bonus', 50);
            $coinBonus = config('quizlo.gamification.coins_daily_goal', 25);

            $this->xpService->awardXp($user, $xpBonus, 'daily_goal_met', $answer->exam_type_id, $progress->id);
            $this->coinService->awardCoins($user, $coinBonus, 'daily_goal_met');
        }

        $progress->save();
    }
}
