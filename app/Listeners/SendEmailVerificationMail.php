<?php

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;

class SendEmailVerificationMail
{
    public function handle(EmailVerificationRequested $event): void
    {
        $link = url('/api/v1/auth/verification-link?email=' . urlencode($event->email) . '&otp=' . urlencode($event->otpCode));
        Mail::to($event->email)->send(new VerifyEmailMail($event->email, $event->otpCode, $link));
    }
}
