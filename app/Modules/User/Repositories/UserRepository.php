<?php

namespace App\Modules\User\Repositories;

use App\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    public function enrollExamType(User $user, int $examTypeId, ?int $targetYear, bool $isPrimary): void
    {
        if ($isPrimary) {
            $this->resetPrimaryExamTypes($user);
        }

        // Attach or update the pivot details
        $user->examTypes()->syncWithoutDetaching([
            $examTypeId => [
                'is_primary' => $isPrimary,
                'target_year' => $targetYear,
                'enrolled_at' => now(),
            ]
        ]);
    }

    public function disenrollExamType(User $user, int $examTypeId): void
    {
        $user->examTypes()->detach($examTypeId);
    }

    public function resetPrimaryExamTypes(User $user): void
    {
        // Update all related pivots
        $ids = $user->examTypes()->pluck('exam_types.id')->toArray();
        if (!empty($ids)) {
            $user->examTypes()->updateExistingPivot($ids, ['is_primary' => false]);
        }
    }

    public function setPrimaryExamType(User $user, int $examTypeId): void
    {
        $this->resetPrimaryExamTypes($user);
        $user->examTypes()->updateExistingPivot($examTypeId, ['is_primary' => true]);
    }
}
