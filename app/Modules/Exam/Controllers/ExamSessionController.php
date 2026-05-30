<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exam\Services\Contracts\ExamServiceInterface;
use App\Modules\Exam\Requests\StartExamRequest;
use App\Modules\Exam\Requests\SubmitExamRequest;
use App\Modules\Exam\Resources\ExamResultResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;

class ExamSessionController extends Controller
{
    public function __construct(
        private readonly ExamServiceInterface $examService
    ) {}

    public function start(StartExamRequest $request, int $modelTestId): JsonResponse
    {
        $session = $this->examService->startExamSession($request->user(), $modelTestId);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    public function submit(SubmitExamRequest $request, int $sessionId): JsonResponse
    {
        try {
            $session = $this->examService->submitExamSession(
                $request->user(),
                $sessionId,
                $request->input('answers', [])
            );

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }

    public function result(Request $request, int $sessionId): JsonResponse
    {
        try {
            $result = $this->examService->getExamSessionResult($request->user(), $sessionId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }
}
