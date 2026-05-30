<?php

namespace App\Modules\Exam\Services;

use App\Models\User;
use App\Models\ExamSession;
use App\Models\UserAnswer;
use App\Models\Question;
use App\Modules\Exam\Services\Contracts\ExamServiceInterface;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;

class ExamService implements ExamServiceInterface
{
    public function __construct(
        private readonly ExamRepositoryInterface $examRepository,
        private readonly XpServiceInterface $xpService
    ) {}

    public function getActiveModelTests(User $user, int $examTypeId): array
    {
        return $this->examRepository->getActiveModelTests($examTypeId)->toArray();
    }

    public function startExamSession(User $user, int $modelTestId): array
    {
        $modelTest = $this->examRepository->findModelTest($modelTestId);
        if (!$modelTest) {
            throw new \InvalidArgumentException('Model test not found.');
        }

        $session = $this->examRepository->createExamSession([
            'user_id' => $user->id,
            'exam_type_id' => $modelTest->exam_type_id,
            'model_test_id' => $modelTest->id,
            'session_type' => 'model_test',
            'status' => 'in_progress',
            'total_questions' => $modelTest->total_questions,
            'answered_count' => 0,
            'correct_count' => 0,
            'score_percent' => 0.00,
            'xp_earned' => 0,
            'started_at' => now(),
        ]);

        return $session->toArray();
    }

    public function submitExamSession(User $user, int $sessionId, array $answers): array
    {
        $session = $this->examRepository->findExamSession($sessionId);
        if (!$session) {
            throw new \InvalidArgumentException('Exam session not found.');
        }

        if ($session->user_id !== $user->id) {
            throw new AuthorizationException('Unauthorized access to exam session.');
        }

        if ($session->status === 'completed') {
            return $session->toArray();
        }

        $modelTest = $session->modelTest;
        $correctCount = 0;
        $answeredCount = count($answers);

        foreach ($answers as $ans) {
            $question = Question::with('options')->find($ans['question_id']);
            if (!$question) continue;

            $selectedOption = $question->options->where('id', $ans['selected_option_id'])->first();
            $isCorrect = $selectedOption ? (bool) $selectedOption->is_correct : false;

            if ($isCorrect) {
                $correctCount++;
            }

            // Save user answer
            UserAnswer::create([
                'user_id' => $user->id,
                'exam_type_id' => $session->exam_type_id,
                'question_id' => $ans['question_id'],
                'selected_option_id' => $ans['selected_option_id'],
                'is_correct' => $isCorrect,
                'time_taken_ms' => $ans['time_taken_ms'] ?? null,
                'xp_earned' => $isCorrect ? config('quizlo.gamification.xp_per_correct_answer', 10) : 0,
                'session_type' => 'model_test',
                'session_id' => $session->id,
            ]);
        }

        $totalQuestions = $session->total_questions;
        $scorePercent = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0.00;

        // Model Test Complete XP Reward + XP per correct answer
        $xpReward = $modelTest ? $modelTest->xp_reward : 100;
        $correctXp = $correctCount * config('quizlo.gamification.xp_per_correct_answer', 10);
        $totalXpEarned = $xpReward + $correctXp;

        $updatedSession = $this->examRepository->saveExamSessionResult($session, [
            'status' => 'completed',
            'answered_count' => $answeredCount,
            'correct_count' => $correctCount,
            'score_percent' => $scorePercent,
            'xp_earned' => $totalXpEarned,
            'completed_at' => now(),
        ]);

        // Award XP to User
        $this->xpService->awardXp(
            $user,
            $totalXpEarned,
            'model_test_complete',
            $session->exam_type_id,
            $session->id
        );

        return $updatedSession->toArray();
    }

    public function getExamSessionResult(User $user, int $sessionId): array
    {
        $session = $this->examRepository->findExamSession($sessionId);
        if (!$session) {
            throw new \InvalidArgumentException('Exam session not found.');
        }

        if ($session->user_id !== $user->id) {
            throw new AuthorizationException('Unauthorized access to exam session.');
        }

        return $session->load('modelTest')->toArray();
    }

    public function getExamCountdown(int $examTypeId): ?array
    {
        $schedule = $this->examRepository->getCountdown($examTypeId);
        if (!$schedule) {
            return null;
        }

        $daysRemaining = max(0, now()->diffInDays(\Carbon\Carbon::parse($schedule->scheduled_date), false));

        return [
            'batch_label' => $schedule->batch_label,
            'exam_stage' => $schedule->exam_stage,
            'scheduled_date' => $schedule->scheduled_date,
            'days_remaining' => $daysRemaining,
            'is_confirmed' => (bool) $schedule->is_confirmed,
            'note' => $schedule->note,
        ];
    }
}
