<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\SendOtpRequest;
use App\Modules\Auth\Requests\VerifyOtpRequest;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Auth\Resources\TokenResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $result = $this->authService->sendOtp(
            $request->input('phone'),
            $request->input('purpose', 'login')
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'otp_code' => $result['otp_code'] ?? null,
            ]
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->input('phone'),
            $request->input('otp_code'),
            $request->input('purpose')
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
            'message' => 'OTP verified successfully.',
            'data' => [
                'token' => new TokenResource($result['token']),
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'phone' => $result['user']->phone,
                ]
            ]
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $result = $this->authService->refreshToken($request->input('refresh_token'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully.',
            'data' => new TokenResource($result['token']),
        ]);
    }
}
