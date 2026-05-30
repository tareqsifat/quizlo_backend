<?php

namespace App\Modules\Exam\Repositories\Contracts;

use App\Models\ModelTest;
use App\Models\ExamSession;
use App\Models\ExamSchedule;
use Illuminate\Support\Collection;

interface ExamRepositoryInterface
{
    public function findModelTest(int $modelTestId): ?ModelTest;

    public function getActiveModelTests(int $examTypeId): Collection;

    public function createExamSession(array $data): ExamSession;

    public function findExamSession(int $sessionId): ?ExamSession;

    public function saveExamSessionResult(ExamSession $session, array $data): ExamSession;

    public function getCountdown(int $examTypeId): ?ExamSchedule;
}
