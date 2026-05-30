<?php

namespace App\Modules\Content\Services;

use App\Models\User;
use App\Models\Lesson;
use App\Modules\Content\Services\Contracts\LessonServiceInterface;
use App\Modules\Content\Repositories\Contracts\LessonRepositoryInterface;
use App\Modules\Gamification\Events\LessonCompleted;

class LessonService implements LessonServiceInterface
{
    public function __construct(
        private readonly LessonRepositoryInterface $lessonRepository
    ) {}

    public function getLessonsBySubjectAndExam(int $subjectId, int $examTypeId): array
    {
        return $this->lessonRepository->getBySubjectAndExam($subjectId, $examTypeId)->all();
    }

    public function completeLesson(User $user, Lesson $lesson, int $score): array
    {
        $xpEarned = $lesson->xp_reward;
        $coinsEarned = $lesson->coin_reward;

        $completion = $this->lessonRepository->createOrUpdateCompletion(
            $user,
            $lesson,
            $score,
            $xpEarned,
            $coinsEarned
        );

        // First win guarantee: set flag to true after first lesson completed
        if (!$user->first_session_completed) {
            $user->update(['first_session_completed' => true]);
        }

        // Dispatch LessonCompleted event
        event(new LessonCompleted($user, $lesson, $score, $xpEarned, $coinsEarned));

        return [
            'success' => true,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'first_session_completed' => (bool) $user->first_session_completed,
        ];
    }
}
