<?php

namespace App\Modules\Content\Services\Contracts;

use App\Models\Topic;
use App\Models\User;

interface TopicServiceInterface
{
    /**
     * @return array{0: \App\Models\ExamTypeSubject|null, 1: array<int, Topic>}
     */
    public function getTopicsBySubjectAndExam(User $user, int $subjectId, int $examTypeId): array;

    public function completeTopic(User $user, Topic $topic, int $score): array;
}
