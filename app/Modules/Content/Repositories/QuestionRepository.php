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
            ->with(['options', 'appearances.examType']);

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        return $query->get();
    }

    public function getByTopic(int $topicId, int $userId, int $limit, bool $preferEasy = false): Collection
    {
        $query = Question::where('topic_id', $topicId)
            ->servable()
            ->with(['options', 'appearances.examType'])
            // Questions the user hasn't answered correctly yet come first.
            ->orderByRaw(
                'exists (select 1 from user_answers ua where ua.question_id = questions.id and ua.user_id = ? and ua.is_correct = ?)',
                [$userId, true]
            );

        // Prefer (not require) easy questions, so a topic without any easy
        // ones still fills the quiz with other difficulties.
        if ($preferEasy) {
            $query->orderByRaw("difficulty = 'easy' desc");
        }

        return $query->inRandomOrder()->limit($limit)->get();
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
