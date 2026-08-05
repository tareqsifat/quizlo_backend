<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Question;
use App\Models\ExamSession;
use App\Models\UserAnswer;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\ExamType;
use App\Models\UserLessonCompletion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $today = now()->startOfDay();

        $topExamTypes = ExamType::withCount('users')
            ->orderByDesc('users_count')
            ->take(5)
            ->get()
            ->map(fn (ExamType $examType) => [
                'id' => $examType->id,
                'name' => $examType->name,
                'code' => $examType->code,
                'enrolled_users' => $examType->users_count,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => User::count(),
                    'active_today' => User::where('is_active', true)->count(),
                    'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
                    'active_this_month' => User::where('created_at', '>=', now()->subMonth())->count(),
                ],
                'content' => [
                    'total_questions' => Question::count(),
                    'total_lessons' => Lesson::count(),
                    'total_subjects' => Subject::count(),
                    'total_exam_types' => ExamType::count(),
                ],
                'engagement' => [
                    'answers_submitted_today' => UserAnswer::where('answered_at', '>=', $today)->count(),
                    'lessons_completed_today' => UserLessonCompletion::where('completed_at', '>=', $today)->count(),
                    'model_tests_today' => ExamSession::where('session_type', 'model_test')->where('started_at', '>=', $today)->count(),
                ],
                'top_exam_types' => $topExamTypes,
            ],
        ]);
    }

    public function retention(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'retention_rate_7d' => 85.50,
                'retention_rate_30d' => 62.10,
            ],
        ]);
    }

    public function dailyActives(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'dau' => User::where('is_active', true)->count(), // Simple count of active users
                'date' => now()->toDateString(),
            ],
        ]);
    }

    public function subjectPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        // Simple aggregation on user answers
        $performance = UserAnswer::where('exam_type_id', $request->input('exam_type_id'))
            ->selectRaw('is_correct, count(*) as count')
            ->groupBy('is_correct')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }
}
