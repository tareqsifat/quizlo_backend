<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\UserSubjectMastery;
use App\Modules\Gamification\Repositories\Contracts\MasteryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MasteryRepository implements MasteryRepositoryInterface
{
    public function findByUserExamSubject(int $userId, int $examTypeId, int $subjectId): ?UserSubjectMastery
    {
        return UserSubjectMastery::where('user_id', $userId)
            ->where('exam_type_id', $examTypeId)
            ->where('subject_id', $subjectId)
            ->first();
    }

    public function updateOrUpdateMastery(int $userId, int $examTypeId, int $subjectId, int $answered, int $correct, float $percentage): UserSubjectMastery
    {
        return UserSubjectMastery::updateOrCreate(
            [
                'user_id' => $userId,
                'exam_type_id' => $examTypeId,
                'subject_id' => $subjectId,
            ],
            [
                'total_answered' => $answered,
                'total_correct' => $correct,
                'mastery_percentage' => $percentage,
                'last_activity_at' => now(),
            ]
        );
    }

    public function getByExamType(int $userId, int $examTypeId): Collection
    {
        return UserSubjectMastery::where('user_id', $userId)
            ->where('exam_type_id', $examTypeId)
            ->get();
    }
}
