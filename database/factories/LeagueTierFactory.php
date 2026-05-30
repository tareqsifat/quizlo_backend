<?php

namespace Database\Factories;

use App\Models\LeagueTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeagueTierFactory extends Factory
{
    protected $model = LeagueTier::class;

    public function definition(): array
    {
        return [
            'name' => 'Bronze',
            'name_bn' => 'ব্রোঞ্জ',
            'tier_order' => 1,
            'color_hex' => '#CD7F32',
            'promotion_spots' => 10,
            'relegation_spots' => 5,
            'max_members' => 30,
        ];
    }

    public function bronze(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bronze',
            'name_bn' => 'ব্রোঞ্জ',
            'tier_order' => 1,
            'color_hex' => '#CD7F32',
            'promotion_spots' => 10,
            'relegation_spots' => 0,
        ]);
    }

    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Silver',
            'name_bn' => 'সিলভার',
            'tier_order' => 2,
            'color_hex' => '#C0C0C0',
            'promotion_spots' => 10,
            'relegation_spots' => 5,
        ]);
    }
}
