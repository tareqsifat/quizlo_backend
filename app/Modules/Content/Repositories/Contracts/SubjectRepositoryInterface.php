<?php

namespace App\Modules\Content\Repositories\Contracts;

interface SubjectRepositoryInterface
{
    public function getByExamType(int $examTypeId): \Illuminate\Database\Eloquent\Collection;
}
