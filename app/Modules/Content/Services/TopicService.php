<?php

namespace App\Modules\Content\Services;

use App\Models\Topic;
use App\Models\User;
use App\Modules\Content\Services\Contracts\TopicServiceInterface;
use App\Modules\Content\Repositories\Contracts\TopicRepositoryInterface;

class TopicService implements TopicServiceInterface
{
    // Topics don't carry their own reward columns (unlike Lesson); a flat
    // reward keeps v1 simple. Revisit if per-topic reward tuning is needed.
    private const DEFAULT_XP_REWARD   = 20;
    private const DEFAULT_COIN_REWARD = 10;

    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository
    ) {}

    public function getTopicsBySubjectAndExam(User $user, int $subjectId, int $examTypeId): array
    {
        $examTypeSubject = $this->topicRepository->findExamTypeSubject($subjectId, $examTypeId);

        if (!$examTypeSubject) {
            return [null, []];
        }

        $topics = $this->topicRepository->getByExamTypeSubject($examTypeSubject->id);
        $completedTopicIds = $this->topicRepository->getCompletedTopicIds($user, $topics->pluck('id')->all());

        $foundCurrent = false;
        foreach ($topics as $topic) {
            if (in_array($topic->id, $completedTopicIds, true)) {
                $topic->status = 'completed';
            } elseif (!$foundCurrent) {
                $topic->status = 'current';
                $foundCurrent = true;
            } else {
                $topic->status = 'locked';
            }
        }

        return [$examTypeSubject, $topics->all()];
    }

    public function completeTopic(User $user, Topic $topic, int $score): array
    {
        $xpEarned = self::DEFAULT_XP_REWARD;
        $coinsEarned = self::DEFAULT_COIN_REWARD;

        $this->topicRepository->createOrUpdateCompletion($user, $topic, $score, $xpEarned, $coinsEarned);

        // A finished topic quiz counts as the user's first session, same as a lesson.
        if (!$user->first_session_completed) {
            $user->update(['first_session_completed' => true]);
        }

        return [
            'success' => true,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
        ];
    }
}
