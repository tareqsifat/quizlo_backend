<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('01#########'),
            'email' => fake()->unique()->safeEmail(),
            'avatar' => null,
            'district' => fake()->city(),
            'division' => fake()->state(),
            'daily_goal' => fake()->randomElement([10, 20, 30]),
            'first_session_completed' => false,
            'is_active' => true,
            'is_admin' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function hasXp(array $attributes = []): static
    {
        return $this->afterCreating(function (User $user) use ($attributes) {
            $user->xp()->updateOrCreate([], array_merge([
                'total_xp' => 0,
                'level' => 1,
            ], $attributes));
        });
    }

    public function hasStreak(array $attributes = []): static
    {
        return $this->afterCreating(function (User $user) use ($attributes) {
            $user->streak()->updateOrCreate([], array_merge([
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
                'freeze_used_today' => false,
                'streak_freeze_count' => 0,
            ], $attributes));
        });
    }

    public function hasHearts(int $count = 5): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            $user->heart()->updateOrCreate([], [
                'current_hearts' => $count,
                'max_hearts' => 5,
                'last_refill_at' => now(),
            ]);
        });
    }

    public function hasCoins(array $attributes = []): static
    {
        return $this->afterCreating(function (User $user) use ($attributes) {
            $user->coin()->updateOrCreate([], array_merge([
                'balance' => 0,
            ], $attributes));
        });
    }

    public function withAllGamificationData(): static
    {
        return $this->hasXp()
            ->hasStreak()
            ->hasHearts()
            ->hasCoins();
    }
}
