<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardReviewStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_include_human_review_counts(): void
    {
        Question::factory()->count(3)->create(['checked_by_human' => true, 'rejection_reason' => null]);
        Question::factory()->count(2)->create(['checked_by_human' => true, 'rejection_reason' => 'Wrong answer key']);
        Question::factory()->create(['checked_by_human' => false, 'rejection_reason' => null]);

        $this->actingAsAdmin()
             ->getJson('/api/v1/admin/dashboard/stats')
             ->assertOk()
             ->assertJsonPath('data.review.human_verified', 5)
             ->assertJsonPath('data.review.accepted', 3)
             ->assertJsonPath('data.review.rejected', 2);
    }
}
