<?php

namespace App\Modules\Auth\Repositories\Contracts;

use App\Models\User;
use App\Models\OtpVerification;

interface AuthRepositoryInterface
{
    public function createOtp(string $phone, string $otpCode, string $purpose): OtpVerification;
    
    public function findLatestOtp(string $phone, string $otpCode, string $purpose): ?OtpVerification;
    
    public function markOtpVerified(OtpVerification $otp): void;
    
    public function findUserByPhone(string $phone): ?User;
    
    public function createUser(array $data): User;

    public function findUserByGoogleIdOrEmail(string $googleId, string $email): ?User;

    public function updateUserGoogleDetails(User $user, string $googleId, ?string $avatar): User;
}
