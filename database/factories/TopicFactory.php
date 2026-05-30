<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'name' => fake()->words(2, true),
            'name_bn' => 'টপিক ' . fake()->word(),
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
