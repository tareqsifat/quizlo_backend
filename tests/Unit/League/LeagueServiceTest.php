<?php

namespace Tests\Unit\League;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\LeagueSeason;
use App\Modules\League\Services\LeagueService;
use App\Modules\League\Repositories\Contracts\LeagueRepositoryInterface;

class LeagueServiceTest extends TestCase
{
    private LeagueRepositoryInterface $leagueRepo;
    private LeagueService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leagueRepo = Mockery::mock(LeagueRepositoryInterface::class);
        $this->service = new LeagueService($this->leagueRepo);
        $this->user = User::factory()->make(['id' => 1]);
    }

    /** @test */
    public function test_it_returns_null_standings_if_no_active_season(): void
    {
        $this->leagueRepo->shouldReceive('findActiveSeason')->andReturn(null);

        $result = $this->service->getCurrentStandings($this->user, 1);

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
