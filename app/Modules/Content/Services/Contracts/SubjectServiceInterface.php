<?php

namespace App\Modules\Content\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface SubjectServiceInterface
{
    public function getSubjectsByExamType(int $examTypeId): Collection;
}
