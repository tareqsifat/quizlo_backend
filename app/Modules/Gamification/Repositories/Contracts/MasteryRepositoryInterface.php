<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\UserSubjectMastery;

interface MasteryRepositoryInterface
{
    public function findByUserExamSubject(int $userId, int $examTypeId, int $subjectId): ?UserSubjectMastery;
    
    public function updateOrUpdateMastery(int $userId, int $examTypeId, int $subjectId, int $answered, int $correct, float $percentage): UserSubjectMastery;
    
    public function getByExamType(int $userId, int $examTypeId): \Illuminate\Database\Eloquent\Collection;
}
