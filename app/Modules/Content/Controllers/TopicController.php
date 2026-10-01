<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Topic;
use App\Modules\Content\Services\Contracts\TopicServiceInterface;
use App\Modules\Content\Services\Contracts\QuestionServiceInterface;
use App\Modules\Content\Resources\TopicResource;
use App\Modules\Content\Resources\QuestionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicServiceInterface $topicService,
        private readonly QuestionServiceInterface $questionService
    ) {}

    public function index(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        [$examTypeSubject, $topics] = $this->topicService->getTopicsBySubjectAndExam(
            $request->user(),
            $subject->id,
            (int) $request->input('exam_type_id')
        );

        if (!$examTypeSubject) {
            return response()->json([
                'success' => true,
                'message' => 'No topics found for this subject.',
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Topics retrieved successfully.',
            'data' => TopicResource::collection($topics),
        ]);
    }

    public function questions(Request $request, Topic $topic): JsonResponse
    {
        $questions = $this->questionService->getQuestionsByTopic($request->user(), $topic);

        return response()->json([
            'success' => true,
            'message' => 'Questions retrieved successfully.',
            'data' => QuestionResource::collection($questions),
        ]);
    }

    public function complete(Request $request, Topic $topic): JsonResponse
    {
        $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $result = $this->topicService->completeTopic(
            $request->user(),
            $topic,
            (int) $request->input('score')
        );

        return response()->json([
            'success' => true,
            'message' => 'Topic completed successfully.',
            'data' => $result,
        ]);
    }
}
