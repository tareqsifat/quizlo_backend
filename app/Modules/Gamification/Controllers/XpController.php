<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\UserDailyProgress;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use App\Modules\Gamification\Services\Contracts\HeartServiceInterface;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;
use App\Modules\Gamification\Services\Contracts\MasteryServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class XpController extends Controller
{
    public function __construct(
        private readonly XpServiceInterface $xpService,
        private readonly StreakServiceInterface $streakService,
        private readonly HeartServiceInterface $heartService,
        private readonly CoinServiceInterface $coinService,
        private readonly MasteryServiceInterface $masteryService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $examTypeId = $request->input('exam_type_id');

        $xp = $user->xp ? $user->xp->total_xp : 0;
        $level = $user->xp ? $user->xp->level : 1;
        $streak = $this->streakService->getStreakStatus($user);
        $hearts = $this->heartService->getHeartsStatus($user);
        $coins = $this->coinService->getBalance($user);
        
        $daily = null;
        if ($examTypeId) {
            $daily = UserDailyProgress::where('user_id', $user->id)
                ->where('exam_type_id', $examTypeId)
                ->where('date', now()->toDateString())
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'xp' => $xp,
                'level' => $level,
                'streak' => $streak,
                'hearts' => $hearts,
                'coins' => $coins,
                'daily_progress' => $daily ? [
                    'goal_questions' => $daily->goal_questions,
                    'answered_questions' => $daily->answered_questions,
                    'correct_questions' => $daily->correct_questions,
                    'xp_earned_today' => $daily->xp_earned_today,
                    'goal_met' => (bool) $daily->goal_met,
                ] : null,
            ],
        ]);
    }

    public function subjectProgress(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $masteries = $this->masteryService->getMasteryByExamType(
            $request->user(),
            (int) $request->input('exam_type_id')
        );

        return response()->json([
            'success' => true,
            'data' => $masteries,
        ]);
    }

    public function dailyProgress(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $progress = UserDailyProgress::where('user_id', $request->user()->id)
            ->where('exam_type_id', $request->input('exam_type_id'))
            ->where('date', now()->toDateString())
            ->first();

        return response()->json([
            'success' => true,
            'data' => $progress ? [
                'date' => $progress->date,
                'goal_questions' => $progress->goal_questions,
                'answered_questions' => $progress->answered_questions,
                'correct_questions' => $progress->correct_questions,
                'goal_met' => (bool) $progress->goal_met,
            ] : [
                'date' => now()->toDateString(),
                'goal_questions' => 20,
                'answered_questions' => 0,
                'correct_questions' => 0,
                'goal_met' => false,
            ],
        ]);
    }

    public function personalBest(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $best = UserDailyProgress::where('user_id', $request->user()->id)
            ->where('exam_type_id', $request->input('exam_type_id'))
            ->orderBy('correct_questions', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'max_correct_in_one_day' => $best ? $best->correct_questions : 0,
                'date' => $best ? $best->date : null,
            ],
        ]);
    }

    public function achievements(Request $request): JsonResponse
    {
        $achievements = Achievement::all();
        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    public function earnedAchievements(Request $request): JsonResponse
    {
        $earned = $request->user()->achievements()->get();
        return response()->json([
            'success' => true,
            'data' => $earned,
        ]);
    }
}
