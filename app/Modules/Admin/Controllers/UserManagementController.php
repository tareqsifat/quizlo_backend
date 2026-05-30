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
        $users = User::select('id', 'name', 'phone', 'email', 'is_active')->get();

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
