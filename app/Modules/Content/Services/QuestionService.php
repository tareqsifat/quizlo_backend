<?php

namespace App\Modules\Content\Services;

use App\Models\User;
use App\Models\Lesson;
use App\Models\UserAnswer;
use App\Modules\Content\Services\Contracts\QuestionServiceInterface;
use App\Modules\Content\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\Gamification\Events\QuestionAnswered;
use Carbon\Carbon;

class QuestionService implements QuestionServiceInterface
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository
    ) {}

    public function getQuestionsByLesson(User $user, Lesson $lesson): array
    {
        $difficulty = null;
        if (!$user->first_session_completed) {
            $difficulty = 'easy';
        }

        return $this->questionRepository->getByLesson($lesson->id, $difficulty)->all();
    }

    public function processAnswer(User $user, array $data): array
    {
        $question = $this->questionRepository->findQuestionWithOptions($data['question_id']);
        if (!$question) {
            throw new \InvalidArgumentException('Question not found.');
        }

        $selectedOption = $question->options->where('id', $data['selected_option_id'])->first();
        if (!$selectedOption) {
            throw new \InvalidArgumentException('Selected option does not belong to the question.');
        }

        $isCorrect = (bool) $selectedOption->is_correct;
        $xpValue = config('quizlo.gamification.xp_per_correct_answer', 10);
        $xpEarned = $isCorrect ? $xpValue : 0;

        // Save UserAnswer
        $userAnswer = $this->questionRepository->saveAnswer([
            'user_id' => $user->id,
            'exam_type_id' => $data['exam_type_id'],
            'question_id' => $data['question_id'],
            'selected_option_id' => $data['selected_option_id'],
            'is_correct' => $isCorrect,
            'time_taken_ms' => $data['time_taken_ms'] ?? null,
            'xp_earned' => $xpEarned,
            'session_type' => $data['session_type'],
            'session_id' => $data['session_id'] ?? null,
        ]);

        // Fire QuestionAnswered event to update DB asynchronously
        event(new QuestionAnswered($userAnswer));

        // Calculate prospective immediate return values
        // XP
        $currentXp = $user->xp ? $user->xp->total_xp : 0;
        $newXp = $currentXp + $xpEarned;

        // Hearts
        $currentHearts = $user->heart ? $user->heart->current_hearts : 5;
        $newHearts = $isCorrect ? $currentHearts : max(0, $currentHearts - 1);

        // Streak
        $currentStreakVal = $user->streak ? $user->streak->current_streak : 0;
        $streakUpdated = false;
        $milestoneReached = false;
        $milestoneDay = null;

        if ($isCorrect) {
            $lastActivity = $user->streak ? $user->streak->last_activity_date : null;
            $today = now()->toDateString();
            $yesterday = Carbon::yesterday()->toDateString();

            if ($lastActivity === $yesterday || $lastActivity === null) {
                $currentStreakVal++;
                $streakUpdated = true;
                if (in_array($currentStreakVal, [7, 14, 30, 60, 100, 365])) {
                    $milestoneReached = true;
                    $milestoneDay = $currentStreakVal;
                }
            } elseif ($lastActivity === $today) {
                $streakUpdated = false; // Already updated today
            } else {
                // Streak was broken, reset to 1
                $currentStreakVal = 1;
                $streakUpdated = true;
            }
        }

        // Subject Mastery ring prospective calculation
        $subjectId = $question->subject_id;
        $examTypeId = $data['exam_type_id'];
        $mastery = $user->masteries()
            ->where('exam_type_id', $examTypeId)
            ->where('subject_id', $subjectId)
            ->first();

        $totalAnswered = $mastery ? $mastery->total_answered + 1 : 1;
        $totalCorrect = $mastery ? $mastery->total_correct + ($isCorrect ? 1 : 0) : ($isCorrect ? 1 : 0);
        $newMasteryPercentage = round(($totalCorrect / $totalAnswered) * 100, 2);

        return [
            'is_correct' => $isCorrect,
            'correct_option_id' => $question->options->where('is_correct', true)->first()?->id,
            'explanation' => $question->explanation,
            'xp_earned' => $xpEarned,
            'total_xp' => $newXp,
            'streak' => [
                'current' => $currentStreakVal,
                'updated' => $streakUpdated,
                'milestone_reached' => $milestoneReached,
                'milestone_day' => $milestoneDay,
            ],
            'hearts' => [
                'current' => $newHearts,
                'max' => $user->heart ? $user->heart->max_hearts : 5,
                'deducted' => !$isCorrect,
            ],
            'mastery' => [
                'subject_id' => $subjectId,
                'exam_type_id' => $examTypeId,
                'new_percentage' => $newMasteryPercentage,
            ]
        ];
    }
}
