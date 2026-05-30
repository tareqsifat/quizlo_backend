<?php

namespace App\Modules\ExamType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ExamType\Services\Contracts\ExamTypeServiceInterface;
use App\Modules\ExamType\Resources\ExamTypeResource;
use Illuminate\Http\JsonResponse;

class ExamTypeController extends Controller
{
    public function __construct(
        private readonly ExamTypeServiceInterface $examTypeService
    ) {}

    public function index(): JsonResponse
    {
        $examTypes = $this->examTypeService->getAllActive();

        return response()->json([
            'success' => true,
            'message' => 'Active exam types retrieved successfully.',
            'data' => ExamTypeResource::collection($examTypes),
        ]);
    }
}
