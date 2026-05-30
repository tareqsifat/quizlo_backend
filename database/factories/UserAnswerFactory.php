<?php

namespace Database\Factories;

use App\Models\UserAnswer;
use App\Models\User;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAnswerFactory extends Factory
{
    protected $model = UserAnswer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_type_id' => ExamType::factory(),
            'question_id' => Question::factory(),
            'selected_option_id' => QuestionOption::factory(),
            'is_correct' => true,
            'time_taken_ms' => 1000,
            'xp_earned' => 10,
            'session_type' => 'practice',
            'session_id' => null,
            'answered_at' => now(),
        ];
    }
}
