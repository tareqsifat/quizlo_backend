<?php

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\User;
use App\Models\ExamType;
use App\Models\ModelTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_type_id' => ExamType::factory(),
            'model_test_id' => ModelTest::factory(),
            'session_type' => 'model_test',
            'status' => 'in_progress',
            'total_questions' => 10,
            'answered_count' => 0,
            'correct_count' => 0,
            'score_percent' => 0.00,
            'xp_earned' => 0,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
