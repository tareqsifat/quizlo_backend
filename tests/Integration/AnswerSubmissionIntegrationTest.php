<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Question;
use App\Models\Lesson;
use App\Models\UserDailyProgress;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnswerSubmissionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Full answer submission chain:
     * HTTP → Service → Event → All Listeners → DB state
     */
    public function test_submitting_correct_answer_updates_all_systems(): void
    {
        $user     = User::factory()
                        ->hasXp(['total_xp' => 100])
                        ->hasStreak(['current_streak' => 4, 'last_activity_date' => Carbon::yesterday()->toDateString()])
                        ->hasHearts(5)
                        ->hasCoins(['balance' => 50])
                        ->create();

        $examType = $this->bcsExamType();
        $subject  = Subject::factory()->create();
        $question = Question::factory()->withOptions()->create(['subject_id' => $subject->id, 'xp_value' => 10]);
        $correct  = $question->options()->where('is_correct', true)->first();

        $user->examTypes()->attach($examType->id);

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                  'exam_type_id'       => $examType->id,
                  'question_id'        => $question->id,
                  'selected_option_id' => $correct->id,
                  'session_type'       => 'practice',
             ])
             ->assertOk();

        // 1. XP increased
        $this->assertDatabaseHas('user_xp', [
            'user_id'  => $user->id,
            'total_xp' => 110,
        ]);

        // 2. XP transaction logged
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount'  => 10,
            'reason'  => 'correct_answer',
        ]);

        // 3. Streak incremented (was day 4, now day 5)
        $this->assertDatabaseHas('user_streaks', [
            'user_id'        => $user->id,
            'current_streak' => 5,
        ]);

        // 4. Hearts NOT deducted (correct answer)
        $this->assertDatabaseHas('user_hearts', [
            'user_id'       => $user->id,
            'current_hearts'=> 5,
        ]);

        // 5. Subject mastery updated
        $this->assertDatabaseHas('user_subject_mastery', [
            'user_id'      => $user->id,
            'exam_type_id' => $examType->id,
            'subject_id'   => $subject->id,
        ]);

        // 6. Daily progress updated
        $progress = \App\Models\UserDailyProgress::where('user_id', $user->id)
            ->where('exam_type_id', $examType->id)
            ->first();
        $this->assertNotNull($progress);
        $this->assertEquals(now()->toDateString(), $progress->date->toDateString());
        $this->assertEquals(1, $progress->answered_questions);
    }

    /** @test */
    public function test_wrong_answer_deducts_heart_but_does_not_award_xp(): void
    {
        $user    = User::factory()->hasXp(['total_xp' => 100])->hasHearts(5)->create();
        $examType = $this->bcsExamType();
        $user->examTypes()->attach($examType->id);

        $question = Question::factory()->withOptions()->create();
        $wrong    = $question->options()->where('is_correct', false)->first();

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                  'exam_type_id'       => $examType->id,
                  'question_id'        => $question->id,
                  'selected_option_id' => $wrong->id,
                  'session_type'       => 'practice',
             ]);

        $this->assertDatabaseHas('user_xp', ['user_id' => $user->id, 'total_xp' => 100]);
        $this->assertDatabaseHas('user_hearts', ['user_id' => $user->id, 'current_hearts' => 4]);
    }

    /** @test */
    public function test_first_session_serves_easy_questions_only(): void
    {
        $user = User::factory()->create(['first_session_completed' => false]);
        $examType = $this->bcsExamType();
        $user->examTypes()->attach($examType->id);

        $lesson = Lesson::factory()->create(['exam_type_id' => $examType->id]);

        Question::factory()->count(10)->create([
            'lesson_id' => $lesson->id,
            'difficulty' => 'hard',
        ]);
        Question::factory()->count(5)->create([
            'lesson_id' => $lesson->id,
            'difficulty' => 'easy',
        ]);

        $response = $this->actingAsUser($user)
             ->getJson("/api/v1/lessons/{$lesson->id}/questions")
             ->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $question) {
            $this->assertEquals('easy', $question['difficulty']);
        }
    }
}
