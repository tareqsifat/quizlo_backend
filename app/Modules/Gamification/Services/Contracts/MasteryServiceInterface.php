<?php

namespace App\Modules\Gamification\Services\Contracts;

use App\Models\User;

interface MasteryServiceInterface
{
    public function updateMastery(User $user, int $examTypeId, int $subjectId, bool $isCorrect): array;
    
    public function getMasteryByExamType(User $user, int $examTypeId): array;
}
