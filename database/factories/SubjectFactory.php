<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->word();
        return [
            'name' => $name,
            'name_bn' => 'বিষয় ' . $name,
            'slug' => fake()->slug(),
            'icon' => 'subject_icon',
            'color_hex' => fake()->safeHexColor(),
            'is_active' => true,
        ];
    }
}
