<?php

namespace Tests\Feature\Content;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnswerSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_can_submit_correct_answer_and_earn_xp(): void
    {
        $user     = User::factory()->hasXp()->hasStreak()->hasHearts()->create();
        $examType = $this->bcsExamType();
        $question = Question::factory()->withOptions()->create();
        $correct  = $question->options()->where('is_correct', true)->first();

        $user->examTypes()->attach($examType->id);

        $response = $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => $examType->id,
                 'question_id'        => $question->id,
                 'selected_option_id' => $correct->id,
                 'session_type'       => 'practice',
             ]);

        $response->assertOk()
             ->assertJson([
                 'success' => true,
                 'data'    => [
                     'is_correct' => true,
                 ],
             ])
             ->assertJsonStructure([
                 'data' => [
                     'is_correct',
                     'xp_earned',
                     'total_xp',
                     'streak',
                     'hearts',
                     'mastery',
                 ],
             ]);

        $this->assertDatabaseHas('user_answers', [
            'user_id'     => $user->id,
            'question_id' => $question->id,
            'is_correct'  => true,
        ]);
    }

    /** @test */
    public function test_wrong_answer_deducts_heart_and_shows_explanation(): void
    {
        $user     = User::factory()->hasXp()->hasStreak()->hasHearts(5)->create();
        $examType = $this->bcsExamType();
        $question = Question::factory()->withOptions()->create([
            'explanation' => 'Because X is the correct answer.',
        ]);
        $wrong = $question->options()->where('is_correct', false)->first();

        $user->examTypes()->attach($examType->id);

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => $examType->id,
                 'question_id'        => $question->id,
                 'selected_option_id' => $wrong->id,
                 'session_type'       => 'practice',
             ])
             ->assertOk()
             ->assertJson([
                 'data' => [
                     'is_correct' => false,
                     'hearts'     => ['deducted' => true, 'current' => 4],
                 ],
             ]);
    }

    /** @test */
    public function test_it_requires_exam_type_id_in_answer_request(): void
    {
        $this->actingAsUser()
             ->postJson('/api/v1/questions/answer', [
                 'question_id'        => 1,
                 'selected_option_id' => 1,
                 'session_type'       => 'practice',
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['exam_type_id']);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_submit_answer(): void
    {
        $this->postJson('/api/v1/questions/answer', [])
             ->assertUnauthorized();
    }

    /** @test */
    public function test_user_without_hearts_cannot_answer(): void
    {
        $user = User::factory()->hasHearts(0)->create();
        $examType = $this->bcsExamType();
        $user->examTypes()->attach($examType->id);

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => $examType->id,
                 'question_id'        => 1,
                 'selected_option_id' => 1,
                 'session_type'       => 'practice',
             ])
             ->assertStatus(403);
    }
}
