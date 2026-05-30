<?php

namespace Tests\Unit\Gamification;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\UserHeart;
use App\Modules\Gamification\Services\HeartService;
use App\Modules\Gamification\Repositories\Contracts\HeartRepositoryInterface;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;

class HeartServiceTest extends TestCase
{
    private HeartRepositoryInterface $heartRepo;
    private CoinServiceInterface $coinService;
    private HeartService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->heartRepo = Mockery::mock(HeartRepositoryInterface::class);
        $this->coinService = Mockery::mock(CoinServiceInterface::class);
        $this->service = new HeartService($this->heartRepo, $this->coinService);
        $this->user = User::factory()->make(['id' => 1]);
    }

    /** @test */
    public function test_it_deducts_heart_on_wrong_answer(): void
    {
        $hearts = new UserHeart(['current_hearts' => 5, 'max_hearts' => 5]);
        $this->heartRepo->shouldReceive('findByUser')->andReturn($hearts);
        $this->heartRepo->shouldReceive('updateHearts')->once()->with($this->user->id, 4);

        $result = $this->service->deductHeart($this->user);

        $this->assertEquals(4, $result['current_hearts']);
    }

    /** @test */
    public function test_it_does_not_refill_above_max(): void
    {
        $hearts = new UserHeart(['current_hearts' => 5, 'max_hearts' => 5]);
        $this->heartRepo->shouldReceive('findByUser')->andReturn($hearts);

        $result = $this->service->refillHearts($this->user);

        $this->assertFalse($result['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
