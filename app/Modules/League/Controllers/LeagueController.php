<?php

namespace App\Modules\League\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\League\Services\Contracts\LeagueServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    public function __construct(
        private readonly LeagueServiceInterface $leagueService
    ) {}

    public function current(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $standings = $this->leagueService->getCurrentStandings(
            $request->user(),
            (int) $request->input('exam_type_id')
        );

        return response()->json([
            'success' => true,
            'data' => $standings,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
        ]);

        $history = $this->leagueService->getLeagueHistory(
            $request->user(),
            (int) $request->input('exam_type_id')
        );

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
