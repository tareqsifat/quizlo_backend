<?php

namespace App\Modules\Content\Repositories\Contracts;

use App\Models\ExamTypeSubject;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicCompletion;
use Illuminate\Database\Eloquent\Collection;

interface TopicRepositoryInterface
{
    public function findExamTypeSubject(int $subjectId, int $examTypeId): ?ExamTypeSubject;

    public function getByExamTypeSubject(int $examTypeSubjectId): Collection;

    public function getCompletedTopicIds(User $user, array $topicIds): array;

    /** @return array<int, int> topic_id => distinct questions answered correctly */
    public function getCorrectCountsByTopic(User $user, array $topicIds): array;

    public function createOrUpdateCompletion(User $user, Topic $topic, int $score, int $xpEarned, int $coinsEarned): UserTopicCompletion;
}
