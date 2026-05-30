<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\ExamSession;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_scope_token_cannot_access_admin_endpoints(): void
    {
        $routes = [
            ['GET',   '/api/v1/admin/users'],
            ['POST',  '/api/v1/admin/questions'],
            ['PATCH', '/api/v1/admin/users/1/toggle-active'],
        ];

        foreach ($routes as [$method, $uri]) {
            $this->actingAsUser()
                 ->json($method, $uri)
                 ->assertForbidden();
        }
    }

    /** @test */
    public function test_user_cannot_access_another_users_exam_session(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $session = ExamSession::factory()->create(['user_id' => $owner->id]);

        $this->actingAsUser($other)
             ->getJson("/api/v1/exam-sessions/{$session->id}/result")
             ->assertForbidden();
    }

    /** @test */
    public function test_user_cannot_submit_option_that_belongs_to_different_question(): void
    {
        $user      = User::factory()->hasHearts(5)->create();
        $examType  = $this->bcsExamType();
        $user->examTypes()->attach($examType->id);

        $question1 = Question::factory()->withOptions()->create();
        $question2 = Question::factory()->withOptions()->create();
        $wrongOption = $question2->options()->first();

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                  'exam_type_id'       => $examType->id,
                  'question_id'        => $question1->id,
                  'selected_option_id' => $wrongOption->id,
                  'session_type'       => 'practice',
             ])
             ->assertStatus(400); // QuestionService throws InvalidArgumentException which returns 400
    }
}
