<?php

namespace App\Modules\Exam\Repositories;

use App\Models\ModelTest;
use App\Models\ExamSession;
use App\Models\ExamSchedule;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Support\Collection;

class ExamRepository implements ExamRepositoryInterface
{
    public function findModelTest(int $modelTestId): ?ModelTest
    {
        return ModelTest::find($modelTestId);
    }

    public function getActiveModelTests(int $examTypeId): Collection
    {
        return ModelTest::where('exam_type_id', $examTypeId)
            ->where('is_active', true)
            ->get();
    }

    public function createExamSession(array $data): ExamSession
    {
        return ExamSession::create($data);
    }

    public function findExamSession(int $sessionId): ?ExamSession
    {
        return ExamSession::find($sessionId);
    }

    public function saveExamSessionResult(ExamSession $session, array $data): ExamSession
    {
        $session->update($data);
        return $session;
    }

    public function getCountdown(int $examTypeId): ?ExamSchedule
    {
        return ExamSchedule::where('exam_type_id', $examTypeId)
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date', 'asc')
            ->first();
    }
}
