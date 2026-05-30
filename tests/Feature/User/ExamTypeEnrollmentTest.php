<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\ExamType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamTypeEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_can_enroll_in_exam_type(): void
    {
        $user     = User::factory()->create();
        $examType = ExamType::factory()->create(['code' => 'BCS']);

        $this->actingAsUser($user)
             ->postJson('/api/v1/user/exam-types', [
                 'exam_type_id' => $examType->id,
                 'is_primary'   => true,
                 'target_year'  => 2026,
             ])
             ->assertOk();

        $this->assertDatabaseHas('user_exam_types', [
            'user_id'      => $user->id,
            'exam_type_id' => $examType->id,
            'is_primary'   => true,
        ]);
    }

    /** @test */
    public function test_user_cannot_enroll_in_same_exam_type_twice(): void
    {
        $user     = User::factory()->create();
        $examType = ExamType::factory()->create();

        $user->examTypes()->attach($examType->id);

        $this->actingAsUser($user)
             ->postJson('/api/v1/user/exam-types', [
                 'exam_type_id' => $examType->id,
             ])
             ->assertUnprocessable();
    }

    /** @test */
    public function test_only_one_primary_exam_type_allowed_per_user(): void
    {
        $user   = User::factory()->create();
        $bcs    = ExamType::factory()->create(['code' => 'BCS']);
        $ssc    = ExamType::factory()->create(['code' => 'SSC']);

        $user->examTypes()->attach($bcs->id, ['is_primary' => true]);

        $this->actingAsUser($user)
             ->postJson('/api/v1/user/exam-types', [
                 'exam_type_id' => $ssc->id,
                 'is_primary'   => true,
             ])
             ->assertOk();

        // Previous primary should now be false
        $this->assertDatabaseHas('user_exam_types', [
            'user_id'      => $user->id,
            'exam_type_id' => $bcs->id,
            'is_primary'   => false,
        ]);
    }
}
