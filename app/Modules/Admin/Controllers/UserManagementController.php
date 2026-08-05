<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::select('id', 'name', 'phone', 'email', 'is_active')
            ->with(['xp', 'streak', 'heart', 'coin'])
            ->get()
            ->map(function (User $user) {
                return [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'phone'     => $user->phone,
                    'email'     => $user->email,
                    'is_active' => $user->is_active,
                    'level'     => $user->xp?->level ?? 1,
                    'xp'        => $user->xp?->total_xp ?? 0,
                    'streak'    => $user->streak?->current_streak ?? 0,
                    'coins'     => $user->coin?->balance ?? 0,
                    'hearts'    => $user->heart?->current_hearts ?? 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['examTypes', 'xp', 'streak', 'heart', 'coin'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function toggleActive(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => null,
            ], 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User active status updated.',
            'data' => [
                'id' => $user->id,
                'is_active' => $user->is_active,
            ],
        ]);
    }
}
