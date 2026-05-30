<?php

namespace Tests\Unit\Gamification;

use Tests\TestCase;
use Mockery;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserStreak;
use App\Modules\Gamification\Services\StreakService;
use App\Modules\Gamification\Repositories\Contracts\StreakRepositoryInterface;
use App\Modules\Gamification\Events\StreakMilestoneReached;
use Illuminate\Support\Facades\Event;

class StreakServiceTest extends TestCase
{
    private StreakRepositoryInterface $streakRepo;
    private StreakService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streakRepo = Mockery::mock(StreakRepositoryInterface::class);
        $this->service    = new StreakService($this->streakRepo);
        $this->user       = User::factory()->make(['id' => 1]);
        Event::fake();
    }

    /** @test */
    public function test_it_does_not_update_streak_if_already_active_today(): void
    {
        $streak = new UserStreak([
            'current_streak'     => 5,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->streakRepo->shouldReceive('findByUser')
                         ->once()
                         ->with($this->user->id)
                         ->andReturn($streak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertFalse($result['streak_updated']);
        $this->assertEquals(5, $result['current_streak']);
    }

    /** @test */
    public function test_it_increments_streak_on_consecutive_day(): void
    {
        $streak = new UserStreak([
            'current_streak'     => 6,
            'longest_streak'     => 6,
            'last_activity_date' => Carbon::yesterday()->toDateString(),
        ]);

        $updatedStreak = new UserStreak([
            'current_streak'     => 7,
            'longest_streak'     => 7,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('incrementStreak')->once()->andReturn($updatedStreak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertTrue($result['streak_updated']);
        $this->assertEquals(7, $result['current_streak']);
    }

    /** @test */
    public function test_it_fires_milestone_event_on_day_7(): void
    {
        $streak = new UserStreak([
            'current_streak'     => 6,
            'last_activity_date' => Carbon::yesterday()->toDateString(),
        ]);

        $updatedStreak = new UserStreak([
            'current_streak'     => 7,
            'longest_streak'     => 7,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('incrementStreak')->andReturn($updatedStreak);

        $this->service->processUserActivity($this->user);

        Event::assertDispatched(StreakMilestoneReached::class, function ($event) {
            return $event->milestoneDay === 7;
        });
    }

    /** @test */
    public function test_it_resets_streak_when_more_than_one_day_missed_and_no_freeze(): void
    {
        $streak = new UserStreak([
            'current_streak'      => 10,
            'last_activity_date'  => Carbon::now()->subDays(3)->toDateString(),
            'streak_freeze_count' => 0,
            'freeze_used_today'   => false,
        ]);

        $resetStreak = new UserStreak([
            'current_streak'     => 1,
            'longest_streak'     => 10,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('resetStreak')->once();
        $this->streakRepo->shouldReceive('incrementStreak')->andReturn($resetStreak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertTrue($result['was_reset']);
        $this->assertEquals(1, $result['current_streak']);
    }

    /** @test */
    public function test_it_applies_freeze_when_one_day_missed_and_freeze_available(): void
    {
        $streak = new UserStreak([
            'current_streak'      => 10,
            'last_activity_date'  => Carbon::now()->subDays(2)->toDateString(),
            'streak_freeze_count' => 1,
            'freeze_used_today'   => false,
        ]);

        $updatedStreak = new UserStreak([
            'current_streak'     => 11,
            'longest_streak'     => 11,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('useStreakFreeze')->once()->andReturn(true);
        $this->streakRepo->shouldReceive('incrementStreak')->andReturn($updatedStreak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertTrue($result['freeze_used']);
        $this->assertFalse($result['was_reset']);
        $this->assertEquals(11, $result['current_streak']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
