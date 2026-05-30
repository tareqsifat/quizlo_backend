<?php

namespace Database\Factories;

use App\Models\QuestionOption;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'option_text' => fake()->word(),
            'option_text_bn' => 'অপশন ' . fake()->word(),
            'is_correct' => false,
            'sort_order' => 1,
        ];
    }
}
