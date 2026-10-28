<?php

namespace App\Modules\Content\Repositories;

use App\Models\ExamTypeSubject;
use App\Models\Question;
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
        // Count and require only servable questions, so the journey never
        // shows a checkpoint that opens an empty quiz.
        return Topic::withCount(['questions' => fn ($q) => $q->servable()])
            ->whereHas('questions', fn ($q) => $q->servable())
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

    public function getCorrectCountsByTopic(User $user, array $topicIds): array
    {
        // Distinct servable questions, so a question answered right twice
        // counts once and the count never exceeds question_count.
        return Question::whereIn('topic_id', $topicIds)
            ->servable()
            ->whereHas('userAnswers', fn ($q) => $q->where('user_id', $user->id)->where('is_correct', true))
            ->selectRaw('topic_id, count(*) as correct_count')
            ->groupBy('topic_id')
            ->pluck('correct_count', 'topic_id')
            ->map(fn ($count) => (int) $count)
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
