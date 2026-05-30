<?php

namespace Database\Factories;

use App\Models\UserSubjectMastery;
use App\Models\User;
use App\Models\ExamType;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSubjectMasteryFactory extends Factory
{
    protected $model = UserSubjectMastery::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_type_id' => ExamType::factory(),
            'subject_id' => Subject::factory(),
            'total_answered' => 0,
            'total_correct' => 0,
            'mastery_percentage' => 0.00,
            'badge_earned' => false,
            'badge_earned_at' => null,
            'last_activity_at' => now(),
        ];
    }
}
