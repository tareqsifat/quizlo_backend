<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            ->where('grant_types', 'like', '%password%')
            ->first();

        if (!$client) {
            return [
                'success' => false,
                'message' => 'OAuth Password client not configured.',
            ];
        }

        $clientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET') ?: $client->secret;

        // Issue token via Passport internal route dispatch
        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $clientSecret,
            'username' => $phone,
            'password' => 'dummy_password', // Bypassed in User model
            'scope' => $user->is_admin ? 'admin user' : 'user',
        ]);

        $response = app()->handle($tokenRequest);
        $tokenData = json_decode($response->getContent(), true);

        if (isset($tokenData['error'])) {
            return [
                'success' => false,
                'message' => ($tokenData['error'] ?? 'error') . ': ' . ($tokenData['error_description'] ?? ($tokenData['message'] ?? 'Token issue failed.')),
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
            ->where('grant_types', 'like', '%password%')
            ->first();

        if (!$client) {
            return [
                'success' => false,
                'message' => 'OAuth Password client not configured.',
            ];
        }

        $clientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET') ?: $client->secret;

        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->id,
            'client_secret' => $clientSecret,
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

    public function adminLogin(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$user->is_admin) {
            return [
                'success' => false,
                'message' => 'Invalid admin credentials or account not authorized.',
            ];
        }

        if (!Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid password.',
            ];
        }

        $client = DB::table('oauth_clients')
            ->where('grant_types', 'like', '%password%')
            ->first();

        if (!$client) {
            return [
                'success' => false,
                'message' => 'OAuth Password client not configured.',
            ];
        }

        $client = DB::table('oauth_clients')
            ->where('id', env('PASSPORT_PASSWORD_CLIENT_ID'))
            ->first();

        if (!$client) {
            return [
                'success' => false,
                'message' => 'OAuth Password client not configured.',
            ];
        }

        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type'    => 'password',
            'client_id'     => $client->id,
            'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
            'username'      => $email,
            'password'      => $password,
            'scope'         => 'admin user',
        ]);

        $response = app()->handle($tokenRequest);
        $tokenData = json_decode($response->getContent(), true);

        if (isset($tokenData['error'])) {
            return [
                'success' => false,
                'message' => ($tokenData['error'] ?? 'error') . ': ' . ($tokenData['error_description'] ?? ($tokenData['message'] ?? 'Token issue failed.')),
            ];
        }

        return [
            'success' => true,
            'token' => $tokenData,
            'user' => $user,
        ];
    }
}
