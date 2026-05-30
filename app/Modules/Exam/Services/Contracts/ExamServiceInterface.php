<?php

namespace App\Modules\Exam\Services\Contracts;

use App\Models\User;

interface ExamServiceInterface
{
    public function getActiveModelTests(User $user, int $examTypeId): array;

    public function startExamSession(User $user, int $modelTestId): array;

    public function submitExamSession(User $user, int $sessionId, array $answers): array;

    public function getExamSessionResult(User $user, int $sessionId): array;

    public function getExamCountdown(int $examTypeId): ?array;
}
