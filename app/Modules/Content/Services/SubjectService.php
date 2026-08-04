<?php

namespace App\Modules\Content\Services;

use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use App\Models\Subject;
use App\Modules\Content\Services\Contracts\SubjectServiceInterface;
use App\Modules\Content\Repositories\Contracts\SubjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SubjectService implements SubjectServiceInterface
{
    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository
    ) {}

    public function getSubjectsByExamType(int $examTypeId): Collection
    {
        $cacheKey = "subjects:exam:{$examTypeId}";
        $ttl = config('quizlo.cache.exam_type_subjects_ttl', 86400);

        // Cache only the raw attribute arrays (not serialized Eloquent objects)
        // to avoid "incomplete object" errors on deserialization.
        $rawRows = Cache::remember($cacheKey, $ttl, function () use ($examTypeId) {
            return $this->subjectRepository->getByExamType($examTypeId)->map(function ($subject) {
                // Preserve the pivot data alongside the model attributes.
                $attrs = $subject->getAttributes();
                if ($subject->relationLoaded('examTypes') && $subject->examTypes->isNotEmpty()) {
                    $attrs['_pivot'] = $subject->examTypes->first()->pivot->toArray();
                }
                return $attrs;
            })->values()->all();
        });

        // Hydrate back into proper Eloquent models so SubjectResource works normally.
        return Collection::make($rawRows)->map(function ($attrs) {
            $pivotData = $attrs['_pivot'] ?? [];
            unset($attrs['_pivot']);

            $subject = Subject::newModelInstance($attrs);
            $subject->exists = true;

            if (!empty($pivotData)) {
                // Re-attach a minimal pivot object so SubjectResource can read ->pivot->title etc.
                $examType = new ExamType();
                $pivot = ExamTypeSubject::fromRawAttributes($examType, $pivotData, 'exam_type_subjects', true);
                $examTypeCollection = new Collection([$examType->setRelation('pivot', $pivot)]);
                $subject->setRelation('examTypes', $examTypeCollection);
            }

            return $subject;
        });
    }
}
