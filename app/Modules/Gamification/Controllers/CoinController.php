<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CoinController extends Controller
{
    public function __construct(
        private readonly CoinServiceInterface $coinService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $balance = $this->coinService->getBalance($request->user());
        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
            ],
        ]);
    }

    public function spend(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string'],
        ]);

        $result = $this->coinService->spendCoins(
            $request->user(),
            (int) $request->input('amount'),
            $request->input('reason')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coins spent successfully.',
            'data' => $result,
        ]);
    }
}
