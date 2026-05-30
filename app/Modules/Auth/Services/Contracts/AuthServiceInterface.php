<?php

namespace App\Modules\Auth\Services\Contracts;

interface AuthServiceInterface
{
    public function sendOtp(string $phone, string $purpose): array;
    
    public function verifyOtp(string $phone, string $otpCode, string $purpose): array;
    
    public function refreshToken(string $refreshToken): array;
}
