<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_sql_injection_attempt_in_phone_field_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/send-otp', [
            'phone' => "01700000000'; DROP TABLE users; --",
        ])->assertUnprocessable();
    }

    /** @test */
    public function test_xss_attempt_in_name_field_is_sanitized(): void
    {
        $user = User::factory()->create();

        $this->actingAsUser($user)
             ->putJson('/api/v1/user/profile', [
                 'name' => '<script>alert("xss")</script>',
             ])
             ->assertOk();

        $this->assertDatabaseMissing('users', [
            'name' => '<script>alert("xss")</script>',
        ]);
    }

    /** @test */
    public function test_mass_assignment_protection_prevents_role_escalation(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAsUser($user)
             ->putJson('/api/v1/user/profile', [
                 'name'     => 'Legitimate Name',
                 'is_admin' => true,          // mass assignment attack
             ])
             ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'is_admin' => false,              // unchanged
        ]);
    }

    /** @test */
    public function test_oversized_payload_is_rejected(): void
    {
        $this->actingAsUser()
             ->putJson('/api/v1/user/profile', [
                 'name' => str_repeat('A', 10000),
             ])
             ->assertUnprocessable();
    }
}
