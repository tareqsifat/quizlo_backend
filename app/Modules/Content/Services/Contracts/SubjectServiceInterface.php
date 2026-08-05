<?php

namespace App\Modules\Content\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SubjectServiceInterface
{
    public function getSubjectsByExamType(int $examTypeId, ?User $user = null): Collection;
}
