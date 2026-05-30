<?php

namespace App\Modules\Content\Repositories;

use App\Models\Subject;
use App\Modules\Content\Repositories\Contracts\SubjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubjectRepository implements SubjectRepositoryInterface
{
    public function getByExamType(int $examTypeId): Collection
    {
        return Subject::whereHas('examTypes', function ($query) use ($examTypeId) {
            $query->where('exam_types.id', $examTypeId)
                  ->where('exam_type_subject.is_active', true);
        })
        ->where('is_active', true)
        ->with(['examTypes' => function ($query) use ($examTypeId) {
            $query->where('exam_types.id', $examTypeId);
        }])
        ->get();
    }
}
