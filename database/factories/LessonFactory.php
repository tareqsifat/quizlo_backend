<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'exam_type_id' => ExamType::factory(),
            'subject_id' => Subject::factory(),
            'topic_id' => Topic::factory(),
            'title' => fake()->sentence(3),
            'title_bn' => 'পাঠ ' . fake()->word(),
            'description' => fake()->paragraph(),
            'xp_reward' => 20,
            'coin_reward' => 10,
            'difficulty' => 'medium',
            'question_count' => 10,
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
