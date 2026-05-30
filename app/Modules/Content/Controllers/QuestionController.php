<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Modules\Content\Requests\User\SubmitAnswerRequest;
use App\Modules\Content\Services\Contracts\QuestionServiceInterface;
use App\Modules\Content\Resources\QuestionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionServiceInterface $questionService
    ) {}

    public function index(Request $request, Lesson $lesson): JsonResponse
    {
        $questions = $this->questionService->getQuestionsByLesson($request->user(), $lesson);

        return response()->json([
            'success' => true,
            'message' => 'Questions retrieved successfully.',
            'data' => QuestionResource::collection($questions),
        ]);
    }

    public function answer(SubmitAnswerRequest $request): JsonResponse
    {
        try {
            $result = $this->questionService->processAnswer($request->user(), $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Answer processed.',
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }
}
