<?php

namespace App\Modules\Content\Repositories\Contracts;

use App\Models\User;
use App\Models\Lesson;
use App\Models\UserLessonCompletion;

interface LessonRepositoryInterface
{
    public function getBySubjectAndExam(int $subjectId, int $examTypeId): \Illuminate\Database\Eloquent\Collection;
    
    public function createOrUpdateCompletion(User $user, Lesson $lesson, int $score, int $xpEarned, int $coinsEarned): UserLessonCompletion;
}
