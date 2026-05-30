<?php

namespace Tests\Unit\Content;

use Tests\TestCase;
use Mockery;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Question;
use App\Modules\Content\Services\QuestionService;
use App\Modules\Content\Repositories\Contracts\QuestionRepositoryInterface;

class QuestionServiceTest extends TestCase
{
    private QuestionRepositoryInterface $questionRepo;
    private QuestionService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->questionRepo = Mockery::mock(QuestionRepositoryInterface::class);
        $this->service = new QuestionService($this->questionRepo);
        $this->user = new User(['id' => 1, 'first_session_completed' => true]);
    }

    public function test_it_retrieves_questions_by_lesson(): void
    {
        $lesson = new Lesson();
        $lesson->forceFill(['id' => 1]);

        $question = new Question();
        $question->forceFill(['id' => 5]);
        
        $this->questionRepo->shouldReceive('getByLesson')
            ->once()
            ->with($lesson->id, null)
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([$question]));

        $result = $this->service->getQuestionsByLesson($this->user, $lesson);

        $this->assertCount(1, $result);
        $this->assertEquals(5, $result[0]->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
