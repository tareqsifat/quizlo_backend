<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Auth\Resources\TokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {}

    /**
     * Redirect to Google OAuth (web / browser-based testing flow).
     */
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback — issues a Passport token.
     */
    public function callback(): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Google token.',
                'data'    => null,
            ], 401);
        }

        $result = $this->authService->loginWithGoogleUser($googleUser);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully via Google.',
            'data'    => [
                'token' => new TokenResource($result['token']),
                'user'  => [
                    'id'     => $result['user']->id,
                    'name'   => $result['user']->name,
                    'email'  => $result['user']->email,
                    'avatar' => $result['user']->avatar,
                ],
            ],
        ]);
    }

    /**
     * Flutter / mobile flow — receives a Google access_token and returns a Passport token.
     */
    public function loginWithToken(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->input('access_token'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired Google token.',
                'data'    => null,
            ], 401);
        }

        $result = $this->authService->loginWithGoogleUser($googleUser);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully via Google token.',
            'data'    => [
                'token' => new TokenResource($result['token']),
                'user'  => [
                    'id'     => $result['user']->id,
                    'name'   => $result['user']->name,
                    'email'  => $result['user']->email,
                    'avatar' => $result['user']->avatar,
                ],
            ],
        ]);
    }
}
