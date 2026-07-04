<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\ExamTypeSubject;
use App\Modules\Admin\Requests\StoreSubjectRequest;
use App\Modules\Admin\Requests\UpdateSubjectRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubjectManagementController extends Controller
{
    /**
     * Paginated, searchable list of all subjects.
     * Query params: search (string), is_active (0|1), per_page (default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subject::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_bn', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $subjects = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $subjects,
        ]);
    }

    /**
     * Flat lightweight list for dropdown use — no pagination, minimal fields.
     */
    public function all(): JsonResponse
    {
        $subjects = Subject::select('id', 'name', 'name_bn', 'slug', 'is_active')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $subjects,
        ]);
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = Subject::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully.',
            'data'    => $subject,
        ], 201);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully.',
            'data'    => $subject->fresh(),
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $usageCount = ExamTypeSubject::where('subject_id', $subject->id)->count();

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: subject is used by {$usageCount} Exam Subject mapping(s). "
                           . 'Remove those first, or deactivate the subject instead.',
                'data'    => null,
            ], 422);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully.',
            'data'    => null,
        ]);
    }
}
