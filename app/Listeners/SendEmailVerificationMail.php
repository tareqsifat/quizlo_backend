<?php

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Mail\VerifyEmailMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailVerificationMail implements ShouldQueue
{
    /**
     * The number of seconds before the job should be retried.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public function handle(EmailVerificationRequested $event): void
    {
        $link = url('/api/v1/auth/verification-link?email=' . urlencode($event->email) . '&otp=' . urlencode($event->otpCode));
        Mail::to($event->email)->send(new VerifyEmailMail($event->email, $event->otpCode, $link));
    }
}
