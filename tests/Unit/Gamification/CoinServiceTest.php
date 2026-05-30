<?php

namespace Tests\Unit\Gamification;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\UserCoin;
use App\Modules\Gamification\Services\CoinService;
use App\Modules\Gamification\Repositories\Contracts\CoinRepositoryInterface;

class CoinServiceTest extends TestCase
{
    private CoinRepositoryInterface $coinRepo;
    private CoinService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coinRepo = Mockery::mock(CoinRepositoryInterface::class);
        $this->service = new CoinService($this->coinRepo);
        $this->user = User::factory()->make(['id' => 1]);
    }

    /** @test */
    public function test_it_awards_coins_successfully(): void
    {
        $coin = new UserCoin(['balance' => 50]);
        $this->coinRepo->shouldReceive('findByUser')->andReturn($coin);
        $this->coinRepo->shouldReceive('updateBalance')->once()->with($this->user->id, 70);
        $this->coinRepo->shouldReceive('logTransaction')->once()->with($this->user->id, 20, 'earn', 'lesson_complete');

        $result = $this->service->awardCoins($this->user, 20, 'lesson_complete');

        $this->assertTrue($result['success']);
        $this->assertEquals(70, $result['balance']);
    }

    /** @test */
    public function test_it_fails_to_spend_coins_if_insufficient_balance(): void
    {
        $coin = new UserCoin(['balance' => 10]);
        $this->coinRepo->shouldReceive('findByUser')->andReturn($coin);

        $result = $this->service->spendCoins($this->user, 20, 'hint_purchase');

        $this->assertFalse($result['success']);
        $this->assertEquals('Insufficient coins balance.', $result['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
