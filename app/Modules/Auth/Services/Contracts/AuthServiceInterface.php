<?php

namespace App\Modules\Auth\Services\Contracts;

use Laravel\Socialite\Contracts\User as SocialiteUser;

interface AuthServiceInterface
{
    public function sendOtp(string $phone, string $purpose): array;
    
    public function verifyOtp(string $phone, string $otpCode, string $purpose): array;
    
    public function adminLogin(string $email, string $password): array;
    
    public function refreshToken(string $refreshToken): array;

    public function loginWithGoogleUser(SocialiteUser $googleUser): array;

    public function register(array $data): array;

    public function sendVerification(string $email): array;

    public function verifyEmail(string $email, string $otp): array;

    public function login(string $email, string $password): array;

    public function sendForgetPasswordOtp(string $email): array;

    public function updatePassword(string $email, string $otp, string $password): array;

    public function changePassword(\App\Models\User $user, string $currentPassword, string $newPassword): array;
}
