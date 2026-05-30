<?php

namespace Tests\Unit\Exam;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\ModelTest;
use App\Modules\Exam\Services\ExamService;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;

class ExamServiceTest extends TestCase
{
    private ExamRepositoryInterface $examRepo;
    private XpServiceInterface $xpService;
    private ExamService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->examRepo = Mockery::mock(ExamRepositoryInterface::class);
        $this->xpService = Mockery::mock(XpServiceInterface::class);
        $this->service = new ExamService($this->examRepo, $this->xpService);
        $this->user = new User(['id' => 1]);
    }

    /** @test */
    public function test_it_lists_active_model_tests(): void
    {
        $modelTest = new ModelTest();
        $modelTest->forceFill(['id' => 4]);

        $this->examRepo->shouldReceive('getActiveModelTests')
            ->once()
            ->with(1)
            ->andReturn(collect([$modelTest]));

        $result = $this->service->getActiveModelTests($this->user, 1);

        $this->assertCount(1, $result);
        $this->assertEquals(4, $result[0]['id']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
