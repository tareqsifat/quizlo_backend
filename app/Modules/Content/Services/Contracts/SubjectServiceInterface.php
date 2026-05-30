<?php

namespace App\Modules\Content\Services\Contracts;

interface SubjectServiceInterface
{
    public function getSubjectsByExamType(int $examTypeId): array;
}
