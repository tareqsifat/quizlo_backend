<?php

namespace App\Modules\Content\Repositories;

use App\Models\ExamTypeSubject;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicCompletion;
use App\Modules\Content\Repositories\Contracts\TopicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TopicRepository implements TopicRepositoryInterface
{
    public function findExamTypeSubject(int $subjectId, int $examTypeId): ?ExamTypeSubject
    {
        return ExamTypeSubject::where('subject_id', $subjectId)
            ->where('exam_type_id', $examTypeId)
            ->first();
    }

    public function getByExamTypeSubject(int $examTypeSubjectId): Collection
    {
        return Topic::withCount('questions')
            ->where('exam_type_subject_id', $examTypeSubjectId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getCompletedTopicIds(User $user, array $topicIds): array
    {
        return UserTopicCompletion::where('user_id', $user->id)
            ->whereIn('topic_id', $topicIds)
            ->pluck('topic_id')
            ->all();
    }

    public function createOrUpdateCompletion(User $user, Topic $topic, int $score, int $xpEarned, int $coinsEarned): UserTopicCompletion
    {
        return UserTopicCompletion::updateOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
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
