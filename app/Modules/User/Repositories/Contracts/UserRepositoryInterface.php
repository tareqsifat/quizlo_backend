<?php

namespace App\Modules\User\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function update(User $user, array $data): User;
    
    public function enrollExamType(User $user, int $examTypeId, ?int $targetYear, bool $isPrimary): void;
    
    public function disenrollExamType(User $user, int $examTypeId): void;
    
    public function resetPrimaryExamTypes(User $user): void;
    
    public function setPrimaryExamType(User $user, int $examTypeId): void;
}
