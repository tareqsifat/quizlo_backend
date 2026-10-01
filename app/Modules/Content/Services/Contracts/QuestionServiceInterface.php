<?php

namespace App\Modules\Content\Services\Contracts;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Topic;

interface QuestionServiceInterface
{
    public function getQuestionsByLesson(User $user, Lesson $lesson): array;

    public function getQuestionsByTopic(User $user, Topic $topic): array;

    public function processAnswer(User $user, array $data): array;
}
