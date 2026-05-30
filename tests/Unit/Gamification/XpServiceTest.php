<?php

namespace Tests\Unit\Gamification;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Modules\Gamification\Services\XpService;
use App\Modules\Gamification\Repositories\Contracts\XpRepositoryInterface;

class XpServiceTest extends TestCase
{
    private XpRepositoryInterface $xpRepo;
    private XpService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xpRepo = Mockery::mock(XpRepositoryInterface::class);
        $this->service = new XpService($this->xpRepo);
        $this->user = User::factory()->make(['id' => 1]);
    }

    /** @test */
    public function test_it_awards_correct_xp_for_a_correct_answer(): void
    {
        $this->xpRepo->shouldReceive('findByUser')
                     ->once()
                     ->with($this->user->id)
                     ->andReturn(null);

        $this->xpRepo->shouldReceive('updateXp')
                     ->once()
                     ->with($this->user->id, config('quizlo.gamification.xp_per_correct_answer'), 1);

        $this->xpRepo->shouldReceive('logTransaction')
                     ->once()
                     ->with($this->user->id, config('quizlo.gamification.xp_per_correct_answer'), 'correct_answer', 1, null);

        $result = $this->service->awardXp($this->user, config('quizlo.gamification.xp_per_correct_answer'), 'correct_answer', 1);

        $this->assertEquals(config('quizlo.gamification.xp_per_correct_answer'), $result['xp_awarded']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
