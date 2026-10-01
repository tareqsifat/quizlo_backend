<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamTypeSubject;
use App\Models\Question;
use App\Models\Topic;
use App\Modules\Admin\Requests\StoreTopicRequest;
use App\Modules\Admin\Requests\UpdateTopicRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TopicManagementController extends Controller
{
    /**
     * List, filterable by exam_type_subject_id / exam_type_id, searchable by name.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Topic::withCount('questions')
            ->with(['examTypeSubject:id,exam_type_id,subject_id,title,title_bn', 'examTypeSubject.examType:id,name,name_bn']);

        if ($request->filled('exam_type_subject_id')) {
            $query->where('exam_type_subject_id', $request->input('exam_type_subject_id'));
        }

        if ($request->filled('exam_type_id')) {
            $examTypeId = $request->input('exam_type_id');
            $query->whereHas('examTypeSubject', fn ($q) => $q->where('exam_type_id', $examTypeId));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_bn', 'like', "%{$search}%");
            });
        }

        $results = $query->orderBy('exam_type_subject_id')->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (Topic $topic) => $this->formatRow($topic));

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Flat lightweight list for a specific Exam Subject — used by cascading dropdowns.
     */
    public function allForExamTypeSubject(ExamTypeSubject $examTypeSubject): JsonResponse
    {
        $list = Topic::where('exam_type_subject_id', $examTypeSubject->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Topic $topic) => [
                'id'         => $topic->id,
                'name'       => $topic->name,
                'name_bn'    => $topic->name_bn,
                'sort_order' => $topic->sort_order,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $list,
        ]);
    }

    public function store(StoreTopicRequest $request): JsonResponse
    {
        $topic = Topic::create($request->validated());
        $topic->loadCount('questions');
        $topic->load(['examTypeSubject:id,exam_type_id,subject_id,title,title_bn', 'examTypeSubject.examType:id,name,name_bn']);

        return response()->json([
            'success' => true,
            'message' => 'Topic created successfully.',
            'data'    => $this->formatRow($topic),
        ], 201);
    }

    public function update(UpdateTopicRequest $request, Topic $topic): JsonResponse
    {
        $topic->update($request->validated());
        $topic->loadCount('questions');
        $topic->load(['examTypeSubject:id,exam_type_id,subject_id,title,title_bn', 'examTypeSubject.examType:id,name,name_bn']);

        return response()->json([
            'success' => true,
            'message' => 'Topic updated successfully.',
            'data'    => $this->formatRow($topic),
        ]);
    }

    public function destroy(Topic $topic): JsonResponse
    {
        $questionCount = Question::where('topic_id', $topic->id)->count();

        if ($questionCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: {$questionCount} question(s) are linked to this Topic. "
                           . 'Remove or reassign the questions first.',
                'data'    => null,
            ], 422);
        }

        $topic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Topic deleted successfully.',
            'data'    => null,
        ]);
    }

    private function formatRow(Topic $topic): array
    {
        return [
            'id'                    => $topic->id,
            'exam_type_subject_id'  => $topic->exam_type_subject_id,
            'exam_subject_title'    => $topic->examTypeSubject?->title,
            'exam_type_id'          => $topic->examTypeSubject?->exam_type_id,
            'exam_type_name'        => $topic->examTypeSubject?->examType?->name,
            'name'                  => $topic->name,
            'name_bn'               => $topic->name_bn,
            'sort_order'            => $topic->sort_order,
            'question_count'        => $topic->questions_count ?? 0,
            'is_active'             => $topic->is_active,
            'created_at'            => $topic->created_at,
            'updated_at'            => $topic->updated_at,
        ];
    }
}
