<?php

namespace App\Modules\ExamType\Services;

use App\Modules\ExamType\Services\Contracts\ExamTypeServiceInterface;
use App\Modules\ExamType\Repositories\Contracts\ExamTypeRepositoryInterface;

class ExamTypeService implements ExamTypeServiceInterface
{
    public function __construct(
        private readonly ExamTypeRepositoryInterface $examTypeRepository
    ) {}

    public function getAllActive(): array
    {
        return $this->examTypeRepository->getActive()->toArray();
    }
}
