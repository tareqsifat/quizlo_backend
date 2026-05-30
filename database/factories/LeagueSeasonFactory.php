<?php

namespace Database\Factories;

use App\Models\LeagueSeason;
use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeagueSeasonFactory extends Factory
{
    protected $model = LeagueSeason::class;

    public function definition(): array
    {
        return [
            'exam_type_id' => ExamType::factory(),
            'week_number' => now()->weekOfYear,
            'year' => now()->year,
            'starts_at' => now()->startOfWeek(),
            'ends_at' => now()->endOfWeek(),
            'is_active' => false,
            'processed' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function bcs(): static
    {
        return $this->state(function (array $attributes) {
            $bcs = ExamType::where('code', 'BCS')->first()
                ?? ExamType::factory()->create(['code' => 'BCS']);
            return [
                'exam_type_id' => $bcs->id,
            ];
        });
    }
}
