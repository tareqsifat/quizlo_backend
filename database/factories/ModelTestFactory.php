<?php

namespace Database\Factories;

use App\Models\ModelTest;
use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelTestFactory extends Factory
{
    protected $model = ModelTest::class;

    public function definition(): array
    {
        return [
            'exam_type_id' => ExamType::factory(),
            'title' => fake()->sentence(3),
            'title_bn' => 'মডেল টেস্ট ' . fake()->word(),
            'total_questions' => 10,
            'duration_minutes' => 60,
            'xp_reward' => 100,
            'is_active' => true,
        ];
    }
}
