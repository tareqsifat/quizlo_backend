<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\OtpVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendOtpTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_it_sends_otp_to_valid_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '01711000001',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['success', 'data' => ['otp_code']]);

        $this->assertDatabaseHas('otp_verifications', [
            'phone' => '01711000001',
        ]);
    }

    /** @test */
    public function test_it_rejects_invalid_phone_format(): void
    {
        $this->postJson('/api/v1/auth/send-otp', ['phone' => '123'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function test_it_rejects_expired_otp(): void
    {
        OtpVerification::create([
            'phone'      => '01711000003',
            'otp_code'   => '123456',
            'expires_at' => now()->subMinutes(10),
            'purpose'    => 'login',
        ]);

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone'    => '01711000003',
            'otp_code' => '123456',
            'purpose'  => 'login',
        ])->assertStatus(400); // returns 400 on invalid/expired OTP in AuthController
    }
}
