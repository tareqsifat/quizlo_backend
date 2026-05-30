<?php

namespace App\Modules\User\Services\Contracts;

use App\Models\User;

interface UserServiceInterface
{
    public function getProfile(User $user): array;
    
    public function updateProfile(User $user, array $data): array;
    
    public function setDailyGoal(User $user, int $goal): array;
    
    public function enrollExamType(User $user, int $examTypeId, ?int $targetYear, bool $isPrimary): array;
    
    public function disenrollExamType(User $user, int $examTypeId): array;
    
    public function setPrimaryExamType(User $user, int $examTypeId): array;
}
