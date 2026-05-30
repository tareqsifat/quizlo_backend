<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Lesson;
use App\Modules\Content\Services\Contracts\LessonServiceInterface;
use App\Modules\Content\Resources\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
    public function __construct(
        private readonly LessonServiceInterface $lessonService
    ) {}

    public function index(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $lessons = $this->lessonService->getLessonsBySubjectAndExam(
            $subject->id,
            (int) $request->input('exam_type_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Lessons retrieved successfully.',
            'data' => LessonResource::collection($lessons),
        ]);
    }

    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $result = $this->lessonService->completeLesson(
            $request->user(),
            $lesson,
            (int) $request->input('score')
        );

        return response()->json([
            'success' => true,
            'message' => 'Lesson completed successfully.',
            'data' => $result,
        ]);
    }
}
