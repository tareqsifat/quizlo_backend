<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Question;
use App\Models\ExamType;
use App\Models\ExamTypeSubject;
use App\Models\QuestionExamAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionAppearanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_search_similar_returns_matching_questions_for_admin(): void
    {
        Question::factory()->withOptions()->create(['question_text' => 'Who wrote the novel Gora?']);
        Question::factory()->withOptions()->create(['question_text' => 'What is the capital of France?']);

        $this->actingAsAdmin()
             ->getJson('/api/v1/admin/questions/search-similar?q=' . urlencode('wrote the novel Gora'))
             ->assertOk()
             ->assertJsonPath('success', true)
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.question_text', 'Who wrote the novel Gora?');
    }

    /** @test */
    public function test_search_similar_requires_admin_auth(): void
    {
        $this->getJson('/api/v1/admin/questions/search-similar?q=anything')
             ->assertUnauthorized();

        $this->actingAsUser()
             ->getJson('/api/v1/admin/questions/search-similar?q=anything')
             ->assertStatus(403);
    }

    /** @test */
    public function test_search_similar_validates_minimum_query_length(): void
    {
        $this->actingAsAdmin()
             ->getJson('/api/v1/admin/questions/search-similar?q=ab')
             ->assertStatus(422);
    }

    /** @test */
    public function test_admin_can_record_appearance_on_existing_question(): void
    {
        $question = Question::factory()->withOptions()->create();
        $examType = ExamType::factory()->create(['code' => 'BCS']);

        $this->actingAsAdmin()
             ->postJson("/api/v1/admin/questions/{$question->id}/appearances", [
                 'exam_type_id' => $examType->id,
                 'year'         => 2024,
                 'source_batch' => 'BCS-60',
             ])
             ->assertOk()
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.source_batch', 'BCS-60');

        $this->assertDatabaseHas('question_exam_appearances', [
            'question_id'  => $question->id,
            'exam_type_id' => $examType->id,
            'year'         => 2024,
            'source_batch' => 'BCS-60',
        ]);
        $this->assertDatabaseCount('questions', 1);
    }

    /** @test */
    public function test_recording_appearance_does_not_create_a_new_question(): void
    {
        $question = Question::factory()->withOptions()->create();
        $examType = ExamType::factory()->create();

        $countBefore = Question::count();

        $this->actingAsAdmin()
             ->postJson("/api/v1/admin/questions/{$question->id}/appearances", [
                 'exam_type_id' => $examType->id,
                 'year'         => 2022,
             ]);

        $this->assertSame($countBefore, Question::count());
    }

    /** @test */
    public function test_duplicate_appearance_does_not_create_a_second_row(): void
    {
        $question = Question::factory()->withOptions()->create();
        $examType = ExamType::factory()->create();

        $payload = ['exam_type_id' => $examType->id, 'year' => 2023, 'source_batch' => 'BCS-59'];

        $this->actingAsAdmin()->postJson("/api/v1/admin/questions/{$question->id}/appearances", $payload)->assertOk();
        $this->actingAsAdmin()->postJson("/api/v1/admin/questions/{$question->id}/appearances", $payload)->assertOk();

        $this->assertSame(1, QuestionExamAppearance::where('question_id', $question->id)->count());
    }

    /** @test */
    public function test_admin_can_delete_a_mistaken_appearance(): void
    {
        $question = Question::factory()->withOptions()->create();
        $appearance = QuestionExamAppearance::factory()->for($question)->create();

        $this->actingAsAdmin()
             ->deleteJson("/api/v1/admin/questions/{$question->id}/appearances/{$appearance->id}")
             ->assertOk();

        $this->assertDatabaseMissing('question_exam_appearances', ['id' => $appearance->id]);
    }

    /** @test */
    public function test_store_question_with_appearance_creates_both_atomically(): void
    {
        $examTypeSubject = ExamTypeSubject::factory()->create();
        $examType = ExamType::factory()->create();

        $this->actingAsAdmin()
             ->postJson('/api/v1/admin/questions', [
                 'exam_type_subject_id' => $examTypeSubject->id,
                 'question_text'        => 'A brand new question?',
                 'options'               => [
                     ['option_text' => 'A', 'is_correct' => true],
                     ['option_text' => 'B', 'is_correct' => false],
                 ],
                 'appearance' => [
                     'exam_type_id' => $examType->id,
                     'year'         => 2024,
                     'source_batch' => 'BCS-60',
                 ],
             ])
             ->assertOk();

        $question = Question::where('question_text', 'A brand new question?')->firstOrFail();
        $this->assertDatabaseHas('question_exam_appearances', [
            'question_id'  => $question->id,
            'exam_type_id' => $examType->id,
            'source_batch' => 'BCS-60',
        ]);
    }

    /** @test */
    public function test_import_persists_source_batch_and_year_as_appearance(): void
    {
        $examType = ExamType::factory()->create();
        $examTypeSubject = ExamTypeSubject::factory()->create([
            'exam_type_id' => $examType->id,
            'title'        => 'General Science',
        ]);

        $this->actingAsAdmin()
             ->postJson('/api/v1/admin/questions/import', [
                 'exam_type_id' => $examType->id,
                 'source_batch' => 'BCS-60',
                 'source_year'  => 2024,
                 'rows' => [[
                     'question'    => 'Imported question?',
                     'optionA'     => 'Opt A',
                     'optionB'     => 'Opt B',
                     'rightAnswer' => 'A',
                     'subject'     => 'General Science',
                 ]],
             ])
             ->assertOk()
             ->assertJsonPath('data.imported', 1);

        $question = Question::where('question_text', 'Imported question?')->firstOrFail();
        $this->assertDatabaseHas('question_exam_appearances', [
            'question_id'  => $question->id,
            'exam_type_id' => $examType->id,
            'source_batch' => 'BCS-60',
            'year'         => 2024,
        ]);
    }

    /** @test */
    public function test_question_resource_exposes_appearances_to_mobile_api(): void
    {
        $user = \App\Models\User::factory()->create(['first_session_completed' => true]);
        \Laravel\Passport\Passport::actingAs($user, ['user']);

        $lesson = \App\Models\Lesson::factory()->create();
        $question = Question::factory()->withOptions()->create(['lesson_id' => $lesson->id, 'is_active' => true]);
        $examType = ExamType::factory()->create(['code' => 'BCS', 'name' => 'BCS Preliminary']);
        QuestionExamAppearance::factory()->for($question)->create([
            'exam_type_id' => $examType->id,
            'year'         => 2022,
            'source_batch' => 'BCS-58',
        ]);

        $this->getJson("/api/v1/lessons/{$lesson->id}/questions")
             ->assertOk()
             ->assertJsonPath('data.0.appearances.0.source_batch', 'BCS-58')
             ->assertJsonPath('data.0.appearances.0.exam_type_code', 'BCS');
    }
}
