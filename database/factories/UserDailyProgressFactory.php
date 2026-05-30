<?php

namespace Database\Factories;

use App\Models\UserDailyProgress;
use App\Models\User;
use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDailyProgressFactory extends Factory
{
    protected $model = UserDailyProgress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_type_id' => ExamType::factory(),
            'date' => now()->toDateString(),
            'goal_questions' => 20,
            'answered_questions' => 0,
            'correct_questions' => 0,
            'xp_earned_today' => 0,
            'goal_met' => false,
        ];
    }
}
