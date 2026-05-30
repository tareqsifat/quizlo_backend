<?php

namespace App\Modules\Content\Repositories;

use App\Models\Question;
use App\Models\UserAnswer;
use App\Modules\Content\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function getByLesson(int $lessonId, ?string $difficulty = null): Collection
    {
        $query = Question::where('lesson_id', $lessonId)
            ->where('is_active', true)
            ->with('options');

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        return $query->get();
    }

    public function findQuestionWithOptions(int $questionId): ?Question
    {
        return Question::with('options')->find($questionId);
    }

    public function saveAnswer(array $data): UserAnswer
    {
        return UserAnswer::create($data);
    }
}
