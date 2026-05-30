<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Services\Contracts\HeartServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HeartController extends Controller
{
    public function __construct(
        private readonly HeartServiceInterface $heartService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $status = $this->heartService->getHeartsStatus($request->user());
        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function refill(Request $request): JsonResponse
    {
        $result = $this->heartService->refillHearts($request->user());
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hearts refilled successfully.',
            'data' => $result,
        ]);
    }
}
