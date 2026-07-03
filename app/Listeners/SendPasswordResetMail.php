<?php

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Mail\ResetPasswordMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetMail implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public function handle(PasswordResetRequested $event): void
    {
        $link = url('/api/v1/auth/verification-link?email=' . urlencode($event->email) . '&otp=' . urlencode($event->otpCode) . '&purpose=password_reset');
        Mail::to($event->email)->send(new ResetPasswordMail($event->email, $event->otpCode, $link));
    }
}
