<?php

namespace Tests\Unit\Gamification;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\UserSubjectMastery;
use App\Modules\Gamification\Services\MasteryService;
use App\Modules\Gamification\Repositories\Contracts\MasteryRepositoryInterface;

class MasteryServiceTest extends TestCase
{
    private MasteryRepositoryInterface $masteryRepo;
    private MasteryService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masteryRepo = Mockery::mock(MasteryRepositoryInterface::class);
        $this->service = new MasteryService($this->masteryRepo);
        $this->user = User::factory()->make(['id' => 1]);
    }

    /** @test */
    public function test_it_updates_mastery_successfully(): void
    {
        $mastery = new UserSubjectMastery([
            'total_answered' => 9,
            'total_correct' => 7,
            'mastery_percentage' => 77.78,
            'badge_earned' => false,
        ]);

        $this->masteryRepo->shouldReceive('findByUserExamSubject')
            ->once()
            ->with($this->user->id, 1, 2)
            ->andReturn($mastery);

        $this->masteryRepo->shouldReceive('updateOrUpdateMastery')
            ->once()
            ->with($this->user->id, 1, 2, 10, 8, 80.00)
            ->andReturn($mastery);

        $result = $this->service->updateMastery($this->user, 1, 2, true);

        $this->assertTrue($result['success']);
        $this->assertEquals(80.00, $result['mastery_percentage']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
