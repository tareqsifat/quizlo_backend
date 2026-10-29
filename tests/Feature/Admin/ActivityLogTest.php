<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Events\ApiRequestHandled;
use App\Listeners\RecordActivityLog;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_request_is_written_to_activity_log_with_user(): void
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['admin']);

        $this->getJson('/api/v1/admin/users?search=abc')->assertOk();

        $log = ActivityLog::latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('GET', $log->method);
        $this->assertSame('api/v1/admin/users', $log->path);
        $this->assertSame(200, $log->status_code);
        $this->assertStringContainsString('UserManagementController', $log->action);
        $this->assertSame(['search' => 'abc'], $log->payload);
    }

    public function test_guest_request_is_logged_and_secrets_are_redacted(): void
    {
        $this->postJson('/api/v1/auth/admin-login', ['email' => 'x@example.com', 'password' => 'hunter2']);

        $log = ActivityLog::latest('id')->first();

        $this->assertNull($log->user_id);
        $this->assertSame('api/v1/auth/admin-login', $log->path);
        $this->assertSame('[REDACTED]', $log->payload['password']);
        $this->assertSame('x@example.com', $log->payload['email']);
    }

    public function test_listener_is_queued(): void
    {
        Event::fake([ApiRequestHandled::class]);

        $this->actingAsAdmin()->getJson('/api/v1/admin/users')->assertOk();

        Event::assertDispatched(ApiRequestHandled::class);
        Event::assertListening(ApiRequestHandled::class, RecordActivityLog::class);
        $this->assertContains(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            class_implements(RecordActivityLog::class),
        );
    }
}
