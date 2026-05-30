<?php

namespace App\Modules\Content\Repositories;

use App\Models\User;
use App\Models\Lesson;
use App\Models\UserLessonCompletion;
use App\Modules\Content\Repositories\Contracts\LessonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LessonRepository implements LessonRepositoryInterface
{
    public function getBySubjectAndExam(int $subjectId, int $examTypeId): Collection
    {
        return Lesson::where('subject_id', $subjectId)
            ->where('exam_type_id', $examTypeId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function createOrUpdateCompletion(User $user, Lesson $lesson, int $score, int $xpEarned, int $coinsEarned): UserLessonCompletion
    {
        return UserLessonCompletion::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'score' => $score,
                'xp_earned' => $xpEarned,
                'coins_earned' => $coinsEarned,
                'completed_at' => now(),
            ]
        );
    }
}
