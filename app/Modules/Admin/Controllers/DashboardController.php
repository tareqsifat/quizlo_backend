<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Question;
use App\Models\ExamSession;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'total_questions' => Question::count(),
                'total_sessions' => ExamSession::count(),
                'completed_sessions' => ExamSession::where('status', 'completed')->count(),
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
