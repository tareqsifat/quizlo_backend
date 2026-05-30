<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\UpdateProfileRequest;
use App\Modules\User\Requests\SetDailyGoalRequest;
use App\Modules\User\Requests\EnrollExamTypeRequest;
use App\Modules\User\Services\Contracts\UserServiceInterface;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    public function profile(Request $request): JsonResponse
    {
        $profile = $this->userService->getProfile($request->user());

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully.',
            'data' => new UserResource($request->user()->load('examTypes')),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $this->userService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User profile updated successfully.',
            'data' => new UserResource($request->user()->fresh()->load('examTypes')),
        ]);
    }

    public function setDailyGoal(SetDailyGoalRequest $request): JsonResponse
    {
        $result = $this->userService->setDailyGoal($request->user(), $request->input('daily_goal'));

        return response()->json([
            'success' => true,
            'message' => 'Daily goal updated successfully.',
            'data' => $result,
        ]);
    }

    public function enroll(EnrollExamTypeRequest $request): JsonResponse
    {
        $result = $this->userService->enrollExamType(
            $request->user(),
            $request->input('exam_type_id'),
            $request->input('target_year'),
            $request->input('is_primary', false)
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => null,
        ]);
    }

    public function disenroll(Request $request, int $examTypeId): JsonResponse
    {
        $result = $this->userService->disenrollExamType($request->user(), $examTypeId);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => null,
        ]);
    }

    public function setPrimary(Request $request, int $examTypeId): JsonResponse
    {
        $result = $this->userService->setPrimaryExamType($request->user(), $examTypeId);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => null,
        ]);
    }
}
