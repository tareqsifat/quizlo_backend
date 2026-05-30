<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Services\Contracts\UserServiceInterface;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getProfile(User $user): array
    {
        return $user->load('examTypes')->toArray();
    }

    public function updateProfile(User $user, array $data): array
    {
        if (isset($data['name'])) {
            $data['name'] = strip_tags($data['name']);
        }
        $updatedUser = $this->userRepository->update($user, $data);
        return $updatedUser->load('examTypes')->toArray();
    }

    public function setDailyGoal(User $user, int $goal): array
    {
        $updatedUser = $this->userRepository->update($user, ['daily_goal' => $goal]);
        return ['daily_goal' => $updatedUser->daily_goal];
    }

    public function enrollExamType(User $user, int $examTypeId, ?int $targetYear, bool $isPrimary): array
    {
        if ($user->examTypes()->where('exam_types.id', $examTypeId)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'exam_type_id' => ['User is already enrolled in this exam type.'],
            ]);
        }

        $this->userRepository->enrollExamType($user, $examTypeId, $targetYear, $isPrimary);
        return ['success' => true, 'message' => 'Successfully enrolled in exam type.'];
    }

    public function disenrollExamType(User $user, int $examTypeId): array
    {
        $this->userRepository->disenrollExamType($user, $examTypeId);
        return ['success' => true, 'message' => 'Successfully disenrolled from exam type.'];
    }

    public function setPrimaryExamType(User $user, int $examTypeId): array
    {
        $this->userRepository->setPrimaryExamType($user, $examTypeId);
        return ['success' => true, 'message' => 'Primary exam type updated.'];
    }
}
