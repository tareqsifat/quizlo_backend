<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();

        $this->actingAsAdmin()
             ->getJson('/api/v1/admin/users')
             ->assertOk()
             ->assertJsonStructure(['data' => [['id', 'name', 'phone']]]);
    }

    /** @test */
    public function test_regular_user_cannot_access_admin_routes(): void
    {
        $this->actingAsUser()
             ->getJson('/api/v1/admin/users')
             ->assertStatus(403);
    }

    /** @test */
    public function test_unauthenticated_request_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/v1/admin/users')
             ->assertUnauthorized();
    }

    /** @test */
    public function test_admin_can_toggle_user_active_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAsAdmin()
             ->patchJson("/api/v1/admin/users/{$user->id}/toggle-active")
             ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'is_active' => false,
        ]);
    }
}
