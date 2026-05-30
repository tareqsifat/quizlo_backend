<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Models\OtpVerification;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function createOtp(string $phone, string $otpCode, string $purpose): OtpVerification
    {
        return OtpVerification::create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    public function findLatestOtp(string $phone, string $otpCode, string $purpose): ?OtpVerification
    {
        return OtpVerification::where('phone', $phone)
            ->where('otp_code', $otpCode)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderBy('id', 'desc')
            ->first();
    }

    public function markOtpVerified(OtpVerification $otp): void
    {
        $otp->update([
            'verified_at' => now(),
        ]);
    }

    public function findUserByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function createUser(array $data): User
    {
        return User::create([
            'phone' => $data['phone'],
            'name' => $data['name'] ?? 'Quizlo User',
            'is_active' => true,
            'daily_goal' => 20,
        ]);
    }
}
