<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\ExamSchedule;
use App\Models\Subject;
use App\Modules\Admin\Requests\StoreExamTypeRequest;
use App\Modules\Admin\Requests\AssignSubjectToExamTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamTypeManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $examTypes = ExamType::all();
        return response()->json([
            'success' => true,
            'data' => $examTypes,
        ]);
    }

    public function store(StoreExamTypeRequest $request): JsonResponse
    {
        $examType = ExamType::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam type created successfully.',
            'data' => $examType,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $examType = ExamType::find($id);
        if (!$examType) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found.',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'name_bn' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'unique:exam_types,code,' . $id],
            'slug' => ['sometimes', 'string', 'unique:exam_types,slug,' . $id],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $examType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exam type updated successfully.',
            'data' => $examType,
        ]);
    }

    public function assignSubject(AssignSubjectToExamTypeRequest $request, ?int $examTypeId = null): JsonResponse
    {
        $id = $examTypeId ?? $request->input('exam_type_id');
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type ID is required.',
                'data' => null,
            ], 422);
        }

        $examType = ExamType::find($id);
        if (!$examType) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found.',
                'data' => null,
            ], 404);
        }

        $subjectId = $request->input('subject_id');
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.',
                'data' => null,
            ], 404);
        }

        $examType->subjects()->syncWithoutDetaching([
            $subjectId => [
                'is_active' => $request->input('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
                'total_marks' => $request->input('total_marks'),
                'syllabus_note' => $request->input('syllabus_note'),
                'created_at' => now(),
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject assigned to exam type successfully.',
            'data' => [
                'exam_type_id' => $examType->id,
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'is_active' => $request->input('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
                'total_marks' => $request->input('total_marks'),
                'syllabus_note' => $request->input('syllabus_note'),
            ],
        ]);
    }

    public function removeSubject(int $examTypeId, int $subjectId): JsonResponse
    {
        $examType = ExamType::find($examTypeId);
        if (!$examType) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found.',
                'data' => null,
            ], 404);
        }

        $examType->subjects()->detach($subjectId);

        return response()->json([
            'success' => true,
            'message' => 'Subject removed from exam type.',
            'data' => null,
        ]);
    }

    public function listSchedules(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $schedules = ExamSchedule::where('exam_type_id', $request->input('exam_type_id'))->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function storeSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'batch_label' => ['nullable', 'string', 'max:50'],
            'exam_stage' => ['required', 'in:preliminary,written,viva,main'],
            'scheduled_date' => ['required', 'date'],
            'is_confirmed' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $schedule = ExamSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule created successfully.',
            'data' => $schedule,
        ]);
    }

    public function listAssignedSubjects(int $examTypeId): JsonResponse
    {
        $examType = ExamType::find($examTypeId);
        if (!$examType) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found.',
                'data' => null,
            ], 404);
        }

        $subjects = $examType->subjects()->get()->map(function ($subject) {
            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'is_active' => $subject->pivot->is_active,
                'sort_order' => $subject->pivot->sort_order,
                'total_marks' => $subject->pivot->total_marks,
                'syllabus_note' => $subject->pivot->syllabus_note,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }
}
