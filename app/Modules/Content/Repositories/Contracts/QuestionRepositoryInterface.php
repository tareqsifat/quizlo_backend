<?php

namespace App\Modules\Content\Repositories\Contracts;

use App\Models\Question;
use App\Models\UserAnswer;

interface QuestionRepositoryInterface
{
    public function getByLesson(int $lessonId, ?string $difficulty = null): \Illuminate\Database\Eloquent\Collection;
    
    public function findQuestionWithOptions(int $questionId): ?Question;
    
    public function saveAnswer(array $data): UserAnswer;
}
