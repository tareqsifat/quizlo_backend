<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\MasteryServiceInterface;
use App\Modules\Gamification\Repositories\Contracts\MasteryRepositoryInterface;

class MasteryService implements MasteryServiceInterface
{
    public function __construct(
        private readonly MasteryRepositoryInterface $masteryRepository
    ) {}

    public function updateMastery(User $user, int $examTypeId, int $subjectId, bool $isCorrect): array
    {
        $mastery = $this->masteryRepository->findByUserExamSubject($user->id, $examTypeId, $subjectId);

        $answered = $mastery ? $mastery->total_answered + 1 : 1;
        $correct = $mastery ? $mastery->total_correct + ($isCorrect ? 1 : 0) : ($isCorrect ? 1 : 0);
        $percentage = round(($correct / $answered) * 100, 2);

        $record = $this->masteryRepository->updateOrUpdateMastery($user->id, $examTypeId, $subjectId, $answered, $correct, $percentage);

        // Badge criteria: >= 80% with >= 10 answered
        $badgeEarned = false;
        if ($percentage >= 80 && $answered >= 10 && !$record->badge_earned) {
            $record->update([
                'badge_earned' => true,
                'badge_earned_at' => now(),
            ]);
            $badgeEarned = true;
        }

        return [
            'success' => true,
            'total_answered' => $answered,
            'total_correct' => $correct,
            'mastery_percentage' => $percentage,
            'badge_earned' => $badgeEarned || $record->badge_earned,
        ];
    }

    public function getMasteryByExamType(User $user, int $examTypeId): array
    {
        return $this->masteryRepository->getByExamType($user->id, $examTypeId)->toArray();
    }
}
