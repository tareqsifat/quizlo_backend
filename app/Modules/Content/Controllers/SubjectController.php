<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\Contracts\SubjectServiceInterface;
use App\Modules\Content\Resources\SubjectResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function __construct(
        private readonly SubjectServiceInterface $subjectService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $subjects = $this->subjectService->getSubjectsByExamType((int) $request->input('exam_type_id'));

        return response()->json([
            'success' => true,
            'message' => 'Subjects retrieved successfully.',
            'data' => SubjectResource::collection($subjects),
        ]);
    }
}
