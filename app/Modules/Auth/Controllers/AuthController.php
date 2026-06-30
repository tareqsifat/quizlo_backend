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

    public function adminLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->adminLogin(
            $request->input('email'),
            $request->input('password')
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
            'message' => 'Admin logged in successfully.',
            'data' => [
                'token' => new TokenResource($result['token']),
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'phone' => $result['user']->phone,
                ]
            ]
        ]);
    }

    // ── New Email-based Authentication & Verification Handlers ─────────────────

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register($request->only('name', 'email', 'password'));

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ], 201);
    }

    public function sendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $result = $this->authService->sendVerification($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function verification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $result = $this->authService->verifyEmail(
            $request->input('email'),
            $request->input('otp')
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
            'message' => 'Email verified successfully.',
            'data' => [
                'token' => new TokenResource($result['token']),
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'phone' => $result['user']->phone,
                ]
            ]
        ]);
    }

    public function verificationLink(Request $request)
    {
        $email = $request->query('email');
        $otp = $request->query('otp');
        $purpose = $request->query('purpose', 'email_verification');

        if (!$email || !$otp) {
            return $this->renderWebResponse(false, 'Missing email or verification code.');
        }

        if ($purpose === 'password_reset') {
            // For password reset, just confirm the code is valid
            $verification = \App\Models\EmailVerification::where('email', $email)
                ->where('otp_code', $otp)
                ->where('purpose', 'password_reset')
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->orderBy('id', 'desc')
                ->first();

            if (!$verification) {
                return $this->renderWebResponse(false, 'Invalid or expired password reset link.');
            }

            return $this->renderWebResponse(true, 'Your password reset code is verified. Please enter this code in the Quizlo app to complete resetting your password: <strong style="font-size: 24px; color: #6366f1; letter-spacing: 2px;">' . e($otp) . '</strong>');
        }

        // Email verification flow
        $result = $this->authService->verifyEmail($email, $otp);

        if (!$result['success']) {
            return $this->renderWebResponse(false, $result['message']);
        }

        return $this->renderWebResponse(true, 'Your email has been verified successfully! You can now log in to the Quizlo app.');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$result['success']) {
            $status = isset($result['needs_verification']) ? 403 : 400;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'needs_verification' => $result['needs_verification'] ?? false,
                'data' => null,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data' => [
                'token' => new TokenResource($result['token']),
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'phone' => $result['user']->phone,
                ]
            ]
        ]);
    }

    public function sendForgetPasswordOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $result = $this->authService->sendForgetPasswordOtp($request->input('email'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->updatePassword(
            $request->input('email'),
            $request->input('otp'),
            $request->input('password')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('password')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    // ── Premium Web Response Renderer ──────────────────────────────────────────

    private function renderWebResponse(bool $success, string $message): string
    {
        $title = $success ? 'Verification Successful' : 'Verification Failed';
        $themeColor = $success ? '#10b981' : '#ef4444';
        $icon = $success 
            ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="icon success"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>' 
            : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="icon error"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} | Quizlo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.6s ease-out;
        }
        .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid {$themeColor};
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.05);
        }
        .icon {
            width: 40px;
            height: 40px;
        }
        .icon.success { color: #10b981; }
        .icon.error { color: #ef4444; }
        h1 {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 16px;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            color: #94a3b8;
            margin: 0 0 32px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(79, 70, 229, 0.4);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            {$icon}
        </div>
        <h1>{$title}</h1>
        <p>{$message}</p>
        <a href="#" class="btn" onclick="window.close(); return false;">Return to App</a>
    </div>
</body>
</html>
HTML;
    }
}
