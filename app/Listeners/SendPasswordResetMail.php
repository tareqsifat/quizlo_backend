<?php

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetMail
{
    public function handle(PasswordResetRequested $event): void
    {
        $link = url('/api/v1/auth/verification-link?email=' . urlencode($event->email) . '&otp=' . urlencode($event->otpCode) . '&purpose=password_reset');
        Mail::to($event->email)->send(new ResetPasswordMail($event->email, $event->otpCode, $link));
    }
}
