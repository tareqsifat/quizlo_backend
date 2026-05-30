<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'topic_id' => Topic::factory(),
            'lesson_id' => Lesson::factory(),
            'question_text' => fake()->sentence(6) . '?',
            'question_bn' => 'প্রশ্ন ' . fake()->word(),
            'explanation' => fake()->sentence(10),
            'difficulty' => 'medium',
            'xp_value' => 10,
            'is_active' => true,
        ];
    }

    public function withOptions(): static
    {
        return $this->afterCreating(function (Question $question) {
            \App\Models\QuestionOption::factory()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);
            \App\Models\QuestionOption::factory()->count(3)->create([
                'question_id' => $question->id,
                'is_correct' => false,
            ]);
        });
    }
}
