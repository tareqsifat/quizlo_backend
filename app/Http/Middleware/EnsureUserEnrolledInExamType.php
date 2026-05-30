<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserEnrolledInExamType
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        $examTypeId = $request->input('exam_type_id') 
            ?? $request->header('X-Exam-Type-Id') 
            ?? $request->route('examType');

        if (!$examTypeId) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Exam type parameter is required in request body, header (X-Exam-Type-Id), or path.',
            ], 400);
        }

        if ($user) {
            $isEnrolled = $user->examTypes()->where('exam_types.id', $examTypeId)->exists();
            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'User is not enrolled in this exam type.',
                ], 403);
            }
        }

        return $next($request);
    }
}
