<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_otp_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/send-otp', ['phone' => '01700000001']);
        }

        $this->postJson('/api/v1/auth/send-otp', ['phone' => '01700000001'])
             ->assertStatus(429);
    }
}
