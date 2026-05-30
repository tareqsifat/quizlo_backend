<?php

namespace Database\Factories;

use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamTypeFactory extends Factory
{
    protected $model = ExamType::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);
        return [
            'name' => $name,
            'name_bn' => 'পরীক্ষা ' . $name,
            'code' => strtoupper(fake()->lexify('???')),
            'slug' => fake()->slug(),
            'description' => fake()->sentence(),
            'icon' => 'exam_icon',
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
