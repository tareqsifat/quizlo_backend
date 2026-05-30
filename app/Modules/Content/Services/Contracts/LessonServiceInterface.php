<?php

namespace App\Modules\Content\Services\Contracts;

use App\Models\User;
use App\Models\Lesson;

interface LessonServiceInterface
{
    public function getLessonsBySubjectAndExam(int $subjectId, int $examTypeId): array;
    
    public function completeLesson(User $user, Lesson $lesson, int $score): array;
}
