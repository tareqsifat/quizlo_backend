<?php

namespace App\Modules\ExamType\Repositories\Contracts;

interface ExamTypeRepositoryInterface
{
    public function getActive(): \Illuminate\Database\Eloquent\Collection;
}
