<?php

namespace Tests\Unit\Content;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\Lesson;
use App\Modules\Content\Services\LessonService;
use App\Modules\Content\Repositories\Contracts\LessonRepositoryInterface;

class LessonServiceTest extends TestCase
{
    private LessonRepositoryInterface $lessonRepo;
    private LessonService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lessonRepo = Mockery::mock(LessonRepositoryInterface::class);
        $this->service = new LessonService($this->lessonRepo);
        $this->user = User::factory()->make(['id' => 1, 'first_session_completed' => true]);
    }

    /** @test */
    public function test_it_retrieves_lessons_by_subject_and_exam(): void
    {
        $lesson = new Lesson();
        $lesson->forceFill(['id' => 3]);

        $this->lessonRepo->shouldReceive('getBySubjectAndExam')
            ->once()
            ->with(1, 2)
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([$lesson]));

        $result = $this->service->getLessonsBySubjectAndExam(1, 2);

        $this->assertCount(1, $result);
        $this->assertEquals(3, $result[0]->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
