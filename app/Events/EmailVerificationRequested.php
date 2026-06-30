<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $email,
        public string $otpCode
    ) {}
}
