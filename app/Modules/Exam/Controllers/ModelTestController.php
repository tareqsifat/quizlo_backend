<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exam\Services\Contracts\ExamServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModelTestController extends Controller
{
    public function __construct(
        private readonly ExamServiceInterface $examService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $tests = $this->examService->getActiveModelTests(
            $request->user(),
            (int) $request->input('exam_type_id')
        );

        return response()->json([
            'success' => true,
            'data' => $tests,
        ]);
    }

    public function countdown(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $countdown = $this->examService->getExamCountdown(
            (int) $request->input('exam_type_id')
        );

        return response()->json([
            'success' => true,
            'data' => $countdown,
        ]);
    }
}
