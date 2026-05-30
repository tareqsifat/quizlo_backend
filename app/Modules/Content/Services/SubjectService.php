<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Services\Contracts\SubjectServiceInterface;
use App\Modules\Content\Repositories\Contracts\SubjectRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SubjectService implements SubjectServiceInterface
{
    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository
    ) {}

    public function getSubjectsByExamType(int $examTypeId): array
    {
        $cacheKey = "subjects:exam:{$examTypeId}";
        $ttl = config('quizlo.cache.exam_type_subjects_ttl', 86400);

        return Cache::remember($cacheKey, $ttl, function () use ($examTypeId) {
            return $this->subjectRepository->getByExamType($examTypeId)->all();
        });
    }
}
