<?php

namespace App\Modules\ExamType\Repositories;

use App\Models\ExamType;
use App\Modules\ExamType\Repositories\Contracts\ExamTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ExamTypeRepository implements ExamTypeRepositoryInterface
{
    public function getActive(): Collection
    {
        return ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
