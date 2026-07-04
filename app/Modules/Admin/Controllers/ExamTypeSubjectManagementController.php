<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use App\Models\Question;
use App\Modules\Admin\Requests\StoreExamTypeSubjectRequest;
use App\Modules\Admin\Requests\UpdateExamTypeSubjectRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamTypeSubjectManagementController extends Controller
{
    /**
     * Paginated list, filterable by exam_type_id, searchable by title.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExamTypeSubject::with(['examType:id,name,name_bn', 'subject:id,name,name_bn,slug']);

        if ($request->filled('exam_type_id')) {
            $query->where('exam_type_id', $request->input('exam_type_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('title_bn', 'like', "%{$search}%");
            });
        }

        $results = $query->orderBy('exam_type_id')->orderBy('title')->get()
            ->map(fn ($ets) => $this->formatRow($ets));

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Flat lightweight list for a specific exam type — used by the bulk import dropdown.
     */
    public function allForExamType(ExamType $examType): JsonResponse
    {
        $list = ExamTypeSubject::with('subject:id,name,name_bn')
            ->where('exam_type_id', $examType->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get()
            ->map(fn ($ets) => [
                'id'              => $ets->id,
                'title'           => $ets->title,
                'title_bn'        => $ets->title_bn,
                'subject_id'      => $ets->subject_id,
                'subject_name'    => $ets->subject?->name,
                'subject_name_bn' => $ets->subject?->name_bn,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $list,
        ]);
    }

    public function store(StoreExamTypeSubjectRequest $request): JsonResponse
    {
        $examTypeSubject = ExamTypeSubject::create($request->validated());
        $examTypeSubject->load(['examType:id,name,name_bn', 'subject:id,name,name_bn']);

        return response()->json([
            'success' => true,
            'message' => 'Exam Subject created successfully.',
            'data'    => $this->formatRow($examTypeSubject),
        ], 201);
    }

    public function update(UpdateExamTypeSubjectRequest $request, ExamTypeSubject $examTypeSubject): JsonResponse
    {
        $examTypeSubject->update($request->validated());
        $examTypeSubject->load(['examType:id,name,name_bn', 'subject:id,name,name_bn']);

        return response()->json([
            'success' => true,
            'message' => 'Exam Subject updated successfully.',
            'data'    => $this->formatRow($examTypeSubject),
        ]);
    }

    public function destroy(ExamTypeSubject $examTypeSubject): JsonResponse
    {
        $questionCount = Question::where('exam_type_subject_id', $examTypeSubject->id)->count();

        if ($questionCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: {$questionCount} question(s) are linked to this Exam Subject. "
                           . 'Remove or reassign the questions first.',
                'data'    => null,
            ], 422);
        }

        $examTypeSubject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam Subject deleted successfully.',
            'data'    => null,
        ]);
    }

    private function formatRow(ExamTypeSubject $ets): array
    {
        return [
            'id'              => $ets->id,
            'exam_type_id'    => $ets->exam_type_id,
            'exam_type_name'  => $ets->examType?->name,
            'subject_id'      => $ets->subject_id,
            'subject_name'    => $ets->subject?->name,
            'subject_name_bn' => $ets->subject?->name_bn,
            'title'           => $ets->title,
            'title_bn'        => $ets->title_bn,
            'is_active'       => $ets->is_active,
            'created_at'      => $ets->created_at,
            'updated_at'      => $ets->updated_at,
        ];
    }
}
