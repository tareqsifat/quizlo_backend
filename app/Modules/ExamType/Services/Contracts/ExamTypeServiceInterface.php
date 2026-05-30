<?php

namespace App\Modules\ExamType\Services\Contracts;

interface ExamTypeServiceInterface
{
    public function getAllActive(): array;
}
