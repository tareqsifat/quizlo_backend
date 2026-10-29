<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\ApiRequestHandled;

class ActivityLogListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep the listing requests themselves out of the table under test.
        Event::fake([ApiRequestHandled::class]);
    }

    private function log(array $attrs): ActivityLog
    {
        return ActivityLog::create(array_merge([
            'method' => 'GET', 'path' => 'api/v1/x', 'status_code' => 200,
            'duration_ms' => 5, 'created_at' => now(),
        ], $attrs));
    }

    public function test_lists_newest_first_with_10_per_page_by_default(): void
    {
        foreach (range(1, 12) as $i) {
            $this->log(['path' => "api/v1/p{$i}", 'created_at' => now()->subMinutes(20 - $i)]);
        }

        $res = $this->actingAsAdmin()->getJson('/api/v1/admin/activity-logs')->assertOk();

        $res->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.path', 'api/v1/p12');
    }

    public function test_sorts_searches_and_filters_by_user(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Alice Admin']);
        $this->log(['path' => 'api/v1/admin/questions', 'user_id' => $admin->id, 'duration_ms' => 50]);
        $this->log(['path' => 'api/v1/admin/users', 'user_id' => $admin->id, 'duration_ms' => 10]);
        $this->log(['path' => 'api/v1/admin/questions/5', 'duration_ms' => 30]);

        $this->actingAsAdmin()
             ->getJson('/api/v1/admin/activity-logs?sort_by=duration_ms&sort_dir=asc')
             ->assertJsonPath('data.0.duration_ms', 10);

        $this->getJson('/api/v1/admin/activity-logs?search=questions')
             ->assertJsonPath('meta.total', 2);

        $this->getJson("/api/v1/admin/activity-logs?user_id={$admin->id}")
             ->assertJsonPath('meta.total', 2)
             ->assertJsonPath('data.0.user.name', 'Alice Admin');

        $this->getJson('/api/v1/admin/activity-logs?sort_by=payload')->assertStatus(422);
    }

    public function test_admins_endpoint_lists_only_admins(): void
    {
        User::factory()->create(['name' => 'Regular']);
        User::factory()->admin()->create(['name' => 'Bob Admin']);

        $names = collect($this->actingAsAdmin()->getJson('/api/v1/admin/activity-logs/admins')
            ->assertOk()->json('data'))->pluck('name');

        $this->assertContains('Bob Admin', $names);
        $this->assertNotContains('Regular', $names);
    }

    public function test_regular_user_cannot_view_activity_logs(): void
    {
        $this->actingAsUser()->getJson('/api/v1/admin/activity-logs')->assertStatus(403);
    }
}
