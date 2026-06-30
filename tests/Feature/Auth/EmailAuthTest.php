<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use App\Events\EmailVerificationRequested;
use App\Events\PasswordResetRequested;
use Laravel\Passport\ClientRepository;

class EmailAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run Laravel Passport migrations
        $this->artisan('migrate', [
            '--path' => 'vendor/laravel/passport/database/migrations',
            '--realpath' => true,
        ]);

        // Set up passport client for password grant using ClientRepository
        $clientRepository = new ClientRepository();
        $clientRepository->createPasswordGrantClient(
            'Quizlo Password Grant Client', 'users'
        );
    }

    /** @test */
    public function test_it_registers_user_and_dispatches_verification_event(): void
    {
        Event::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('email_verifications', [
            'email' => 'test@example.com',
            'purpose' => 'email_verification',
        ]);

        Event::assertDispatched(EmailVerificationRequested::class);
    }

    /** @test */
    public function test_it_verifies_email_with_valid_otp_and_returns_tokens(): void
    {
        $user = User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
        ]);

        EmailVerification::create([
            'email' => 'unverified@example.com',
            'otp_code' => '123456',
            'purpose' => 'email_verification',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/verification', [
            'email' => 'unverified@example.com',
            'otp' => '123456',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['success', 'data' => ['token', 'user']]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function test_it_rejects_login_for_unverified_user(): void
    {
        User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden()
                 ->assertJson([
                     'success' => false,
                     'needs_verification' => true,
                 ]);
    }

    /** @test */
    public function test_it_allows_login_for_verified_user(): void
    {
        User::create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'verified@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['success', 'data' => ['token', 'user']]);
    }

    /** @test */
    public function test_it_sends_forgot_password_otp(): void
    {
        Event::fake();

        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/send-forget-password-otp', [
            'email' => 'user@example.com',
        ]);

        $response->assertOk()
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('email_verifications', [
            'email' => 'user@example.com',
            'purpose' => 'password_reset',
        ]);

        Event::assertDispatched(PasswordResetRequested::class);
    }

    /** @test */
    public function test_it_resets_password_using_otp(): void
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('oldpassword'),
            'email_verified_at' => now(),
        ]);

        EmailVerification::create([
            'email' => 'user@example.com',
            'otp_code' => '654321',
            'purpose' => 'password_reset',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/update-password', [
            'email' => 'user@example.com',
            'otp' => '654321',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
                 ->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
