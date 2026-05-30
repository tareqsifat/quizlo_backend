<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakController extends Controller
{
    public function __construct(
        private readonly StreakServiceInterface $streakService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $result = $this->streakService->processUserActivity($request->user());

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function spendFreeze(Request $request): JsonResponse
    {
        $used = $this->streakService->useStreakFreeze($request->user());

        return response()->json([
            'success' => $used,
            'message' => $used ? 'Streak freeze applied.' : 'No freezes available.',
        ]);
    }
}
