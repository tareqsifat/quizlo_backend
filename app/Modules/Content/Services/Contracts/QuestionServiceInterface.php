<?php

namespace App\Modules\Content\Services\Contracts;

use App\Models\User;
use App\Models\Lesson;

interface QuestionServiceInterface
{
    public function getQuestionsByLesson(User $user, Lesson $lesson): array;
    
    public function processAnswer(User $user, array $data): array;
}
