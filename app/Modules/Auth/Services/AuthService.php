<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository
    ) {}

    public function sendOtp(string $phone, string $purpose): array
    {
        // For testing, if it's the target Sajid user, make it 123456
        $otpCode = ($phone === '01711223344') ? '123456' : (string) rand(100000, 999999);

        $this->authRepository->createOtp($phone, $otpCode, $purpose);

        return [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'otp_code' => $otpCode, // Returned for sandbox/testing convenience
        ];
    }

    public function verifyOtp(string $phone, string $otpCode, string $purpose): array
    {
        $otp = $this->authRepository->findLatestOtp($phone, $otpCode, $purpose);

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        $this->authRepository->markOtpVerified($otp);

        $user = $this->authRepository->findUserByPhone($phone);

        if (!$user) {
            // Auto-register user if not registered
            $user = $this->authRepository->createUser([
                'phone' => $phone,
                'name' => 'User ' . substr($phone, -4),
            ]);
        }

        // Fetch Password Client Details
        $client = DB::table('oauth_clients')
            ->where('password_client', 1)
            ->first();

        if (!$client) {
            return [
                'success' => false,
                'message' => 'OAuth Password client not configured.',
            ];
        }

        // Issue token via Passport internal route dispatch
        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $phone,
            'password' => 'dummy_password', // Bypassed in User model
            'scope' => 'user',
        ]);

        $response = app()->handle($tokenRequest);
        $tokenData = json_decode($response->getContent(), true);

        if (isset($tokenData['error'])) {
            return [
                'success' => false,
                'message' => $tokenData['message'] ?? 'Token issue failed.',
            ];
        }

        return [
            'success' => true,
            'token' => $tokenData,
            'user' => $user,
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $client = DB::table('oauth_clients')
            ->where('password_client', 1)
            ->first();

        if (!$client) {
            return [
                'success' => false,
                'message' => 'OAuth Password client not configured.',
            ];
        }

        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'refresh_token' => $refreshToken,
            'scope' => 'user',
        ]);

        $response = app()->handle($tokenRequest);
        $tokenData = json_decode($response->getContent(), true);

        if (isset($tokenData['error'])) {
            return [
                'success' => false,
                'message' => $tokenData['message'] ?? 'Token refresh failed.',
            ];
        }

        return [
            'success' => true,
            'token' => $tokenData,
        ];
    }
}
