<?php

namespace Database\Factories;

use App\Models\QuestionExamAppearance;
use App\Models\Question;
use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionExamAppearanceFactory extends Factory
{
    protected $model = QuestionExamAppearance::class;

    public function definition(): array
    {
        return [
            'question_id'  => Question::factory(),
            'exam_type_id' => ExamType::factory(),
            'year'         => fake()->numberBetween(2015, 2025),
            'source_batch' => null,
            'created_by'   => null,
        ];
    }
}
