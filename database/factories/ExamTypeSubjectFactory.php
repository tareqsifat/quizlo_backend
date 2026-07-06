<?php

namespace Database\Factories;

use App\Models\ExamTypeSubject;
use App\Models\ExamType;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamTypeSubjectFactory extends Factory
{
    protected $model = ExamTypeSubject::class;

    public function definition(): array
    {
        $title = fake()->word() . ' Subject';
        return [
            'exam_type_id' => ExamType::factory(),
            'subject_id'   => Subject::factory(),
            'title'        => $title,
            'title_bn'     => 'বিষয় ' . $title,
            'is_active'    => true,
        ];
    }
}
