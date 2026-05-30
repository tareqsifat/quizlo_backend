<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'title' => fake()->words(2, true),
            'title_bn' => 'অ্যাচিভমেন্ট ' . fake()->word(),
            'description' => fake()->sentence(),
            'icon' => 'achievement_icon',
            'xp_reward' => 50,
            'type' => 'streak',
        ];
    }
}
