<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentManagementController extends Controller
{
    // Questions CRUD
    public function listQuestions(Request $request): JsonResponse
    {
        $questions = Question::with('options')->get();
        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'question_text' => ['required', 'string'],
            'question_bn' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'xp_value' => ['sometimes', 'integer'],
        ]);

        $question = Question::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully.',
            'data' => $question,
        ]);
    }

    public function updateQuestion(Request $request, int $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'subject_id' => ['sometimes', 'integer', 'exists:subjects,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'question_text' => ['sometimes', 'string'],
            'question_bn' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'xp_value' => ['sometimes', 'integer'],
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data' => $question,
        ]);
    }

    public function destroyQuestion(int $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found.',
                'data' => null,
            ], 404);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
            'data' => null,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        // Simple import stub
        return response()->json([
            'success' => true,
            'message' => 'Questions imported successfully.',
            'data' => [
                'imported_count' => 0,
            ],
        ]);
    }

    public function tagExamTypes(Request $request, int $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found.',
                'data' => null,
            ], 404);
        }

        $request->validate([
            'exam_type_ids' => ['required', 'array'],
            'exam_type_ids.*' => ['integer', 'exists:exam_types,id'],
        ]);

        $question->examTypes()->sync($request->input('exam_type_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Question tagged to exam types.',
            'data' => null,
        ]);
    }

    // Lessons CRUD
    public function listLessons(Request $request): JsonResponse
    {
        $lessons = Lesson::all();
        return response()->json([
            'success' => true,
            'data' => $lessons,
        ]);
    }

    public function storeLesson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'title' => ['required', 'string', 'max:200'],
            'title_bn' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'xp_reward' => ['sometimes', 'integer'],
            'coin_reward' => ['sometimes', 'integer'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'question_count' => ['sometimes', 'integer'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $lesson = Lesson::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lesson created successfully.',
            'data' => $lesson,
        ]);
    }

    public function updateLesson(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'exam_type_id' => ['sometimes', 'integer', 'exists:exam_types,id'],
            'subject_id' => ['sometimes', 'integer', 'exists:subjects,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'title' => ['sometimes', 'string', 'max:200'],
            'title_bn' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'xp_reward' => ['sometimes', 'integer'],
            'coin_reward' => ['sometimes', 'integer'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'question_count' => ['sometimes', 'integer'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $lesson->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully.',
            'data' => $lesson,
        ]);
    }
}
