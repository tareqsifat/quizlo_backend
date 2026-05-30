# Quizlo — Testing Strategy Document
### Canvas 1b · Backend Team · Addendum to Canvas 1 v2.0
**Stack:** PHPUnit · Laravel Pest · OWASP ZAP · Postman
**Version:** 1.0 · May 2026

---

## 1. Test Types Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    QUIZLO TEST PYRAMID                          │
│                                                                 │
│                         ▲ VAPT                                  │
│                        ▲▲▲ E2E                                  │
│                       ▲▲▲▲▲ Integration                         │
│                      ▲▲▲▲▲▲▲ Feature                            │
│                     ▲▲▲▲▲▲▲▲▲ Unit  ← largest volume            │
│                                                                 │
│  Unit: fast, isolated, no DB     Feature: full HTTP, uses DB    │
│  Integration: service+repo layer  VAPT: security-focused        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Directory Structure

```
tests/
├── Unit/
│   ├── Gamification/
│   │   ├── XpServiceTest.php
│   │   ├── StreakServiceTest.php
│   │   ├── HeartServiceTest.php
│   │   ├── CoinServiceTest.php
│   │   └── MasteryServiceTest.php
│   ├── Content/
│   │   ├── QuestionServiceTest.php
│   │   └── LessonServiceTest.php
│   ├── Exam/
│   │   └── ExamServiceTest.php
│   └── League/
│       └── LeagueServiceTest.php
│
├── Feature/
│   ├── Auth/
│   │   ├── SendOtpTest.php
│   │   ├── VerifyOtpTest.php
│   │   └── RefreshTokenTest.php
│   ├── User/
│   │   ├── ProfileTest.php
│   │   ├── DailyGoalTest.php
│   │   └── ExamTypeEnrollmentTest.php
│   ├── Content/
│   │   ├── SubjectListTest.php
│   │   ├── LessonListTest.php
│   │   └── AnswerSubmissionTest.php
│   ├── Gamification/
│   │   ├── StreakApiTest.php
│   │   ├── HeartApiTest.php
│   │   ├── CoinApiTest.php
│   │   └── GamificationDashboardTest.php
│   ├── League/
│   │   ├── LeagueStandingsTest.php
│   │   └── WeeklyResetTest.php
│   ├── Exam/
│   │   ├── ModelTestTest.php
│   │   └── ExamSessionTest.php
│   └── Admin/
│       ├── UserManagementTest.php
│       ├── ContentManagementTest.php
│       └── ExamTypeManagementTest.php
│
├── Integration/
│   ├── AnswerSubmissionIntegrationTest.php    ← full event chain
│   ├── LeaguePromotionIntegrationTest.php
│   └── FirstWinGuaranteeIntegrationTest.php
│
├── Security/
│   ├── AuthorizationTest.php                 ← scope enforcement
│   ├── InputSanitizationTest.php
│   └── RateLimitTest.php
│
└── TestCase.php
```

---

## 3. Unit Tests

Unit tests mock all dependencies. No database. No HTTP. Fast.
Every Service method gets its own test. Repository calls are mocked via `Mockery`.

### 3.1 StreakService Unit Test

```php
<?php
// tests/Unit/Gamification/StreakServiceTest.php

namespace Tests\Unit\Gamification;

use Tests\TestCase;
use Mockery;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserStreak;
use App\Modules\Gamification\Services\StreakService;
use App\Modules\Gamification\Repositories\Contracts\StreakRepositoryInterface;
use App\Modules\Gamification\Events\StreakMilestoneReached;
use Illuminate\Support\Facades\Event;

class StreakServiceTest extends TestCase
{
    private StreakRepositoryInterface $streakRepo;
    private StreakService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streakRepo = Mockery::mock(StreakRepositoryInterface::class);
        $this->service    = new StreakService($this->streakRepo);
        $this->user       = User::factory()->make(['id' => 1]);
        Event::fake();
    }

    /** @test */
    public function it_does_not_update_streak_if_already_active_today(): void
    {
        $streak = new UserStreak([
            'current_streak'     => 5,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->streakRepo->shouldReceive('findByUser')
                         ->once()
                         ->with($this->user->id)
                         ->andReturn($streak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertFalse($result['streak_updated']);
        $this->assertEquals(5, $result['current_streak']);
    }

    /** @test */
    public function it_increments_streak_on_consecutive_day(): void
    {
        $streak = new UserStreak([
            'current_streak'     => 6,
            'longest_streak'     => 6,
            'last_activity_date' => Carbon::yesterday()->toDateString(),
        ]);

        $updatedStreak = new UserStreak([
            'current_streak'     => 7,
            'longest_streak'     => 7,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('incrementStreak')->once()->andReturn($updatedStreak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertTrue($result['streak_updated']);
        $this->assertEquals(7, $result['current_streak']);
    }

    /** @test */
    public function it_fires_milestone_event_on_day_7(): void
    {
        $streak = new UserStreak([
            'current_streak'     => 6,
            'last_activity_date' => Carbon::yesterday()->toDateString(),
        ]);

        $updatedStreak = new UserStreak([
            'current_streak'     => 7,
            'longest_streak'     => 7,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('incrementStreak')->andReturn($updatedStreak);

        $this->service->processUserActivity($this->user);

        Event::assertDispatched(StreakMilestoneReached::class, function ($event) {
            return $event->milestoneDay === 7;
        });
    }

    /** @test */
    public function it_resets_streak_when_more_than_one_day_missed_and_no_freeze(): void
    {
        $streak = new UserStreak([
            'current_streak'      => 10,
            'last_activity_date'  => Carbon::now()->subDays(3)->toDateString(),
            'streak_freeze_count' => 0,
            'freeze_used_today'   => false,
        ]);

        $resetStreak = new UserStreak([
            'current_streak'     => 1,
            'longest_streak'     => 10,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('resetStreak')->once();
        $this->streakRepo->shouldReceive('incrementStreak')->andReturn($resetStreak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertTrue($result['was_reset']);
        $this->assertEquals(1, $result['current_streak']);
    }

    /** @test */
    public function it_applies_freeze_when_one_day_missed_and_freeze_available(): void
    {
        $streak = new UserStreak([
            'current_streak'      => 10,
            'last_activity_date'  => Carbon::now()->subDays(2)->toDateString(),
            'streak_freeze_count' => 1,
            'freeze_used_today'   => false,
        ]);

        $updatedStreak = new UserStreak([
            'current_streak'     => 11,
            'longest_streak'     => 11,
            'streak_freeze_count'=> 0,
        ]);

        $this->streakRepo->shouldReceive('findByUser')->andReturn($streak);
        $this->streakRepo->shouldReceive('useStreakFreeze')->once()->andReturn(true);
        $this->streakRepo->shouldReceive('incrementStreak')->andReturn($updatedStreak);

        $result = $this->service->processUserActivity($this->user);

        $this->assertTrue($result['freeze_used']);
        $this->assertFalse($result['was_reset']);
        $this->assertEquals(11, $result['current_streak']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### 3.2 XpService Unit Test

```php
<?php
// tests/Unit/Gamification/XpServiceTest.php

class XpServiceTest extends TestCase
{
    /** @test */
    public function it_awards_correct_xp_for_a_correct_answer(): void
    {
        $this->xpRepo->shouldReceive('incrementTotal')
                     ->once()
                     ->with($this->user->id, config('quizlo.gamification.xp_per_correct_answer'));

        $this->xpRepo->shouldReceive('logTransaction')->once();

        $earned = $this->service->awardForAnswer($this->user, isCorrect: true);

        $this->assertEquals(config('quizlo.gamification.xp_per_correct_answer'), $earned);
    }

    /** @test */
    public function it_awards_zero_xp_for_wrong_answer(): void
    {
        $this->xpRepo->shouldNotReceive('incrementTotal');

        $earned = $this->service->awardForAnswer($this->user, isCorrect: false);

        $this->assertEquals(0, $earned);
    }

    /** @test */
    public function it_calculates_correct_level_from_xp(): void
    {
        // Level thresholds: 1=0, 2=100, 3=300, 4=600, 5=1000
        $this->assertEquals(1, $this->service->calculateLevel(0));
        $this->assertEquals(1, $this->service->calculateLevel(99));
        $this->assertEquals(2, $this->service->calculateLevel(100));
        $this->assertEquals(3, $this->service->calculateLevel(300));
        $this->assertEquals(5, $this->service->calculateLevel(1500));
    }
}
```

### 3.3 HeartService Unit Test

```php
<?php
// tests/Unit/Gamification/HeartServiceTest.php

class HeartServiceTest extends TestCase
{
    /** @test */
    public function it_deducts_heart_on_wrong_answer(): void
    {
        $hearts = new UserHeart(['current_hearts' => 5, 'max_hearts' => 5]);
        $this->heartRepo->shouldReceive('findByUser')->andReturn($hearts);
        $this->heartRepo->shouldReceive('decrement')->once()->with($this->user->id);

        $result = $this->service->deduct($this->user);

        $this->assertTrue($result['deducted']);
        $this->assertEquals(4, $result['remaining']);
    }

    /** @test */
    public function it_does_not_deduct_when_hearts_already_zero(): void
    {
        $hearts = new UserHeart(['current_hearts' => 0, 'max_hearts' => 5]);
        $this->heartRepo->shouldReceive('findByUser')->andReturn($hearts);
        $this->heartRepo->shouldNotReceive('decrement');

        $result = $this->service->deduct($this->user);

        $this->assertFalse($result['deducted']);
    }

    /** @test */
    public function it_does_not_refill_above_max(): void
    {
        $hearts = new UserHeart(['current_hearts' => 5, 'max_hearts' => 5]);
        $this->heartRepo->shouldReceive('findByUser')->andReturn($hearts);
        $this->heartRepo->shouldNotReceive('refill');

        $this->service->refill($this->user);
    }

    /** @test */
    public function it_respects_30_minute_refill_window(): void
    {
        $hearts = new UserHeart([
            'current_hearts' => 3,
            'max_hearts'     => 5,
            'last_refill_at' => now()->subMinutes(10), // too soon
        ]);

        $this->heartRepo->shouldReceive('findByUser')->andReturn($hearts);
        $this->heartRepo->shouldNotReceive('refill');

        $result = $this->service->refill($this->user);

        $this->assertFalse($result['refilled']);
        $this->assertArrayHasKey('refill_available_in_minutes', $result);
    }
}
```

---

## 4. Feature Tests

Feature tests hit real HTTP endpoints. They use `RefreshDatabase`, real DB seeding, and Passport tokens.

### 4.1 Base Test Setup

```php
<?php
// tests/TestCase.php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;
use App\Models\User;
use App\Models\ExamType;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsUser(?User $user = null): static
    {
        $user ??= User::factory()->create();
        Passport::actingAs($user, ['user']);
        return $this;
    }

    protected function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['admin']);
        return $this;
    }

    protected function bcsExamType(): ExamType
    {
        return ExamType::where('code', 'BCS')->firstOrFail();
    }
}
```

### 4.2 Auth Feature Tests

```php
<?php
// tests/Feature/Auth/SendOtpTest.php

class SendOtpTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_otp_to_valid_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '01711000001',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['success', 'data' => ['expires_in']]);

        $this->assertDatabaseHas('otp_verifications', [
            'phone' => '01711000001',
        ]);
    }

    /** @test */
    public function it_rejects_invalid_phone_format(): void
    {
        $this->postJson('/api/v1/auth/send-otp', ['phone' => '123'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function it_rate_limits_otp_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/send-otp', ['phone' => '01711000002']);
        }

        $this->postJson('/api/v1/auth/send-otp', ['phone' => '01711000002'])
             ->assertTooManyRequests();
    }

    /** @test */
    public function it_rejects_expired_otp(): void
    {
        OtpVerification::factory()->create([
            'phone'      => '01711000003',
            'otp_code'   => '123456',
            'expires_at' => now()->subMinutes(10),
        ]);

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone'    => '01711000003',
            'otp_code' => '123456',
        ])->assertUnprocessable();
    }
}
```

### 4.3 Answer Submission Feature Tests

```php
<?php
// tests/Feature/Content/AnswerSubmissionTest.php

class AnswerSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_submit_correct_answer_and_earn_xp(): void
    {
        $user     = User::factory()->hasXp()->hasStreak()->hasHearts()->create();
        $examType = $this->bcsExamType();
        $question = Question::factory()->withOptions()->create();
        $correct  = $question->options()->where('is_correct', true)->first();

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => $examType->id,
                 'question_id'        => $question->id,
                 'selected_option_id' => $correct->id,
                 'session_type'       => 'practice',
             ])
             ->assertOk()
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
    public function wrong_answer_deducts_heart_and_shows_explanation(): void
    {
        $user     = User::factory()->hasXp()->hasStreak()->hasHearts(5)->create();
        $examType = $this->bcsExamType();
        $question = Question::factory()->withOptions()->create([
            'explanation' => 'Because X is the correct answer.',
        ]);
        $wrong = $question->options()->where('is_correct', false)->first();

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
    public function it_requires_exam_type_id_in_answer_request(): void
    {
        $this->actingAsUser()
             ->postJson('/api/v1/questions/answer', [
                 'question_id'        => 1,
                 'selected_option_id' => 1,
                 'session_type'       => 'practice',
                 // exam_type_id missing
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['exam_type_id']);
    }

    /** @test */
    public function unauthenticated_user_cannot_submit_answer(): void
    {
        $this->postJson('/api/v1/questions/answer', [])
             ->assertUnauthorized();
    }

    /** @test */
    public function user_without_hearts_cannot_answer(): void
    {
        $user = User::factory()->hasHearts(0)->create();

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => 1,
                 'question_id'        => 1,
                 'selected_option_id' => 1,
                 'session_type'       => 'practice',
             ])
             ->assertForbidden()
             ->assertJson(['message' => 'No hearts remaining.']);
    }
}
```

### 4.4 Exam Type Enrollment Feature Tests

```php
<?php
// tests/Feature/User/ExamTypeEnrollmentTest.php

class ExamTypeEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_enroll_in_exam_type(): void
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
    public function user_cannot_enroll_in_same_exam_type_twice(): void
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
    public function only_one_primary_exam_type_allowed_per_user(): void
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
```

### 4.5 League Feature Tests

```php
<?php
// tests/Feature/League/LeagueStandingsTest.php

class LeagueStandingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_see_their_current_league_standings(): void
    {
        $user   = User::factory()->create();
        $season = LeagueSeason::factory()->active()->bcs()->create();
        $tier   = LeagueTier::factory()->bronze()->create();

        UserLeague::factory()->create([
            'user_id'          => $user->id,
            'league_season_id' => $season->id,
            'league_tier_id'   => $tier->id,
            'group_number'     => 1,
            'weekly_xp'        => 150,
        ]);

        $this->actingAsUser($user)
             ->getJson('/api/v1/league/current?exam_type_id=1')
             ->assertOk()
             ->assertJsonStructure([
                 'data' => [
                     'tier',
                     'group_number',
                     'weekly_xp',
                     'rank',
                     'standings' => [['user_id', 'name', 'weekly_xp', 'rank']],
                     'promotion_spots',
                     'relegation_spots',
                 ],
             ]);
    }

    /** @test */
    public function league_standings_are_exam_type_scoped(): void
    {
        $user    = User::factory()->create();
        $bcsSeason = LeagueSeason::factory()->active()->create(['exam_type_id' => 1]);
        $sscSeason = LeagueSeason::factory()->active()->create(['exam_type_id' => 2]);

        // Only enrolled in BCS league
        UserLeague::factory()->create([
            'user_id'          => $user->id,
            'league_season_id' => $bcsSeason->id,
        ]);

        $this->actingAsUser($user)
             ->getJson('/api/v1/league/current?exam_type_id=2')
             ->assertOk()
             ->assertJson(['data' => null]);            // not enrolled in SSC league
    }
}
```

### 4.6 Admin Authorization Feature Tests

```php
<?php
// tests/Feature/Admin/UserManagementTest.php

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_list_users(): void
    {
        User::factory()->count(5)->create();

        $this->actingAsAdmin()
             ->getJson('/api/v1/admin/users')
             ->assertOk()
             ->assertJsonStructure(['data' => [['id', 'name', 'phone']]]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_routes(): void
    {
        $this->actingAsUser()
             ->getJson('/api/v1/admin/users')
             ->assertForbidden();
    }

    /** @test */
    public function unauthenticated_request_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/v1/admin/users')
             ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_toggle_user_active_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAsAdmin()
             ->patchJson("/api/v1/admin/users/{$user->id}/toggle-active")
             ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'is_active' => false,
        ]);
    }
}
```

---

## 5. Integration Tests

Integration tests verify that multiple layers work correctly together — the event chain after answer submission is the most critical.

```php
<?php
// tests/Integration/AnswerSubmissionIntegrationTest.php

class AnswerSubmissionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Full answer submission chain:
     * HTTP → Service → Event → All Listeners → DB state
     */
    public function submitting_correct_answer_updates_all_systems(): void
    {
        $user     = User::factory()
                        ->hasXp(['total_xp' => 100])
                        ->hasStreak(['current_streak' => 4, 'last_activity_date' => Carbon::yesterday()])
                        ->hasHearts(['current_hearts' => 5])
                        ->hasCoins(['balance' => 50])
                        ->create();

        $examType = ExamType::where('code', 'BCS')->first();
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
        $this->assertDatabaseHas('user_daily_progress', [
            'user_id'           => $user->id,
            'exam_type_id'      => $examType->id,
            'date'              => now()->toDateString(),
            'answered_questions'=> 1,
        ]);
    }

    /** @test */
    public function wrong_answer_deducts_heart_but_does_not_award_xp(): void
    {
        $user    = User::factory()->hasXp(['total_xp' => 100])->hasHearts(['current_hearts' => 5])->create();
        $question = Question::factory()->withOptions()->create();
        $wrong    = $question->options()->where('is_correct', false)->first();

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => 1,
                 'question_id'        => $question->id,
                 'selected_option_id' => $wrong->id,
                 'session_type'       => 'practice',
             ]);

        $this->assertDatabaseHas('user_xp', ['user_id' => $user->id, 'total_xp' => 100]);
        $this->assertDatabaseHas('user_hearts', ['user_id' => $user->id, 'current_hearts' => 4]);
    }

    /** @test */
    public function first_session_serves_easy_questions_only(): void
    {
        $user = User::factory()->create(['first_session_completed' => false]);

        Question::factory()->count(10)->create(['difficulty' => 'hard']);
        $lesson = Lesson::factory()->create();

        $this->actingAsUser($user)
             ->getJson("/api/v1/lessons/{$lesson->id}/questions")
             ->assertOk()
             ->assertJson(fn ($json) =>
                 $json->each(fn ($question) =>
                     $question->where('difficulty', 'easy')
                 )
             );
    }
}
```

---

## 6. Security Tests (VAPT)

### 6.1 Authorization & Token Scope Tests

```php
<?php
// tests/Security/AuthorizationTest.php

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_scope_token_cannot_access_admin_endpoints(): void
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
    public function user_cannot_access_another_users_exam_session(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $session = ExamSession::factory()->create(['user_id' => $owner->id]);

        $this->actingAsUser($other)
             ->getJson("/api/v1/exam-sessions/{$session->id}/result")
             ->assertForbidden();
    }

    /** @test */
    public function user_cannot_submit_option_that_belongs_to_different_question(): void
    {
        $user      = User::factory()->hasHearts(5)->create();
        $question1 = Question::factory()->withOptions()->create();
        $question2 = Question::factory()->withOptions()->create();
        $wrongOption = $question2->options()->first();

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => 1,
                 'question_id'        => $question1->id,
                 'selected_option_id' => $wrongOption->id,  // option from different question
                 'session_type'       => 'practice',
             ])
             ->assertUnprocessable();
    }

    /** @test */
    public function expired_passport_token_is_rejected(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test', ['user'], now()->subMinute())->accessToken;

        $this->withToken($token)
             ->getJson('/api/v1/user/profile')
             ->assertUnauthorized();
    }
}
```

### 6.2 Input Sanitization Tests

```php
<?php
// tests/Security/InputSanitizationTest.php

class InputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sql_injection_attempt_in_phone_field_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/send-otp', [
            'phone' => "01700000000'; DROP TABLE users; --",
        ])->assertUnprocessable();
    }

    /** @test */
    public function xss_attempt_in_name_field_is_sanitized(): void
    {
        $user = User::factory()->create();

        $this->actingAsUser($user)
             ->putJson('/api/v1/user/profile', [
                 'name' => '<script>alert("xss")</script>',
             ])
             ->assertOk();

        $this->assertDatabaseMissing('users', [
            'name' => '<script>alert("xss")</script>',
        ]);
    }

    /** @test */
    public function mass_assignment_protection_prevents_role_escalation(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAsUser($user)
             ->putJson('/api/v1/user/profile', [
                 'name'     => 'Legitimate Name',
                 'is_admin' => true,          // mass assignment attack
             ])
             ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'is_admin' => false,              // unchanged
        ]);
    }

    /** @test */
    public function oversized_payload_is_rejected(): void
    {
        $this->actingAsUser()
             ->putJson('/api/v1/user/profile', [
                 'name' => str_repeat('A', 10000),
             ])
             ->assertUnprocessable();
    }
}
```

### 6.3 Rate Limit Tests

```php
<?php
// tests/Security/RateLimitTest.php

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function otp_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/send-otp', ['phone' => '01700000001']);
        }

        $this->postJson('/api/v1/auth/send-otp', ['phone' => '01700000001'])
             ->assertTooManyRequests();
    }

    /** @test */
    public function answer_submission_is_rate_limited_to_prevent_brute_force(): void
    {
        $user     = User::factory()->hasHearts(100)->create();
        $question = Question::factory()->withOptions()->create();
        $option   = $question->options()->first();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAsUser($user)->postJson('/api/v1/questions/answer', [
                'exam_type_id'       => 1,
                'question_id'        => $question->id,
                'selected_option_id' => $option->id,
                'session_type'       => 'practice',
            ]);
        }

        $this->actingAsUser($user)
             ->postJson('/api/v1/questions/answer', [
                 'exam_type_id'       => 1,
                 'question_id'        => $question->id,
                 'selected_option_id' => $option->id,
                 'session_type'       => 'practice',
             ])
             ->assertTooManyRequests();
    }
}
```

---

## 7. Additional Test Types

### 7.1 Console / Scheduled Job Tests

```php
<?php
// tests/Feature/League/WeeklyResetTest.php

class WeeklyResetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function weekly_league_reset_creates_new_season(): void
    {
        $examType = ExamType::where('code', 'BCS')->first();
        LeagueSeason::factory()->active()->create(['exam_type_id' => $examType->id]);

        $this->artisan('quizlo:reset-weekly-league')->assertSuccessful();

        $this->assertDatabaseHas('league_seasons', [
            'exam_type_id' => $examType->id,
            'is_active'    => true,
            'processed'    => false,
        ]);
    }

    /** @test */
    public function promotion_moves_top_10_users_up_one_tier(): void
    {
        $season = LeagueSeason::factory()->create(['processed' => false]);
        $bronze = LeagueTier::factory()->bronze()->create(['tier_order' => 1]);
        $silver = LeagueTier::factory()->silver()->create(['tier_order' => 2]);

        // Create 30 users in bronze, top 10 should be promoted
        $users = User::factory()->count(30)->create();
        foreach ($users as $i => $user) {
            UserLeague::factory()->create([
                'user_id'          => $user->id,
                'league_season_id' => $season->id,
                'league_tier_id'   => $bronze->id,
                'group_number'     => 1,
                'weekly_xp'        => (30 - $i) * 10,  // descending XP
            ]);
        }

        $this->artisan('quizlo:process-league-promotion', [
            '--season' => $season->id,
        ])->assertSuccessful();

        // Top 10 should be promoted
        $topUsers = $users->take(10);
        foreach ($topUsers as $user) {
            $this->assertDatabaseHas('user_leagues', [
                'user_id'   => $user->id,
                'promoted'  => true,
            ]);
        }
    }
}
```

### 7.2 Notification Job Tests

```php
<?php

class NotificationJobTest extends TestCase
{
    /** @test */
    public function daily_reminder_is_sent_only_to_users_who_have_not_studied_today(): void
    {
        Notification::fake();

        $studiedUser    = User::factory()->create();
        $nonStudiedUser = User::factory()->create();

        // Mark one user as already studied today
        UserDailyProgress::factory()->create([
            'user_id'           => $studiedUser->id,
            'date'              => now()->toDateString(),
            'answered_questions'=> 10,
        ]);

        (new SendDailyReminderNotification)->handle();

        Notification::assertSentTo($nonStudiedUser, DailyStudyReminder::class);
        Notification::assertNotSentTo($studiedUser, DailyStudyReminder::class);
    }

    /** @test */
    public function streak_warning_sent_to_users_with_active_streak_not_yet_studied(): void
    {
        Notification::fake();

        $atRiskUser = User::factory()->hasStreak([
            'current_streak'    => 10,
            'last_activity_date'=> Carbon::yesterday()->toDateString(),
        ])->create();

        (new SendStreakWarningNotification)->handle();

        Notification::assertSentTo($atRiskUser, StreakWarning::class);
    }
}
```

### 7.3 API Contract / Response Shape Tests

Ensures the Flutter app never receives unexpected response shape changes.

```php
<?php
// tests/Feature/Content/ApiContractTest.php

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function gamification_dashboard_response_matches_contract(): void
    {
        $user = User::factory()->withAllGamificationData()->create();

        $this->actingAsUser($user)
             ->getJson('/api/v1/gamification/dashboard?exam_type_id=1')
             ->assertOk()
             ->assertJsonStructure([
                 'data' => [
                     'xp'     => ['total', 'level', 'next_level_at'],
                     'streak' => ['current', 'longest', 'freeze_count'],
                     'hearts' => ['current', 'max', 'refill_in_minutes'],
                     'coins'  => ['balance'],
                     'league' => ['tier', 'weekly_xp', 'rank'],
                 ],
             ]);
    }

    /** @test */
    public function subject_list_response_includes_mastery_ring_data(): void
    {
        $user = User::factory()->create();

        $this->actingAsUser($user)
             ->getJson('/api/v1/subjects?exam_type_id=1')
             ->assertOk()
             ->assertJsonStructure([
                 'data' => [[
                     'id', 'name', 'name_bn', 'icon', 'color_hex',
                     'mastery' => ['percentage', 'badge_earned'],
                 ]],
             ]);
    }
}
```

### 7.4 Performance / Stress Test Notes (Non-PHPUnit)

These are run via external tools before major releases — not part of the PHPUnit suite.

```
Tool: k6 (https://k6.io) or Apache JMeter

Critical endpoints to load test:
┌──────────────────────────────────────┬──────────────┬────────────────┐
│ Endpoint                             │ Target RPS   │ Max Latency    │
├──────────────────────────────────────┼──────────────┼────────────────┤
│ POST /api/v1/questions/answer        │ 500 req/s    │ < 300ms p95    │
│ GET  /api/v1/league/current          │ 300 req/s    │ < 200ms p95    │
│ GET  /api/v1/gamification/dashboard  │ 300 req/s    │ < 200ms p95    │
│ POST /api/v1/auth/verify-otp         │ 200 req/s    │ < 500ms p95    │
└──────────────────────────────────────┴──────────────┴────────────────┘

Run before: every production release
Run during: BCS exam season (traffic spikes expected)
```

---

## 8. VAPT Checklist (Vulnerability Assessment & Penetration Testing)

Run this checklist before every major release using OWASP ZAP + manual review.

```
OWASP TOP 10 — QUIZLO SPECIFIC COVERAGE

✅ A01 Broken Access Control
   - User scope cannot hit admin routes          → AuthorizationTest
   - User cannot view other user's exam session  → AuthorizationTest
   - Option-question mismatch rejected           → AuthorizationTest

✅ A02 Cryptographic Failures
   - OTP stored hashed, never plain              → Code review
   - Passport tokens use RS256 signing           → Passport default
   - HTTPS enforced in production                → nginx config

✅ A03 Injection
   - SQL injection in all input fields           → InputSanitizationTest
   - All DB queries via Eloquent ORM             → No raw queries
   - XSS in name/text fields sanitized           → InputSanitizationTest

✅ A04 Insecure Design
   - First win guarantee not exposed via API     → FirstWinGuaranteeTest
   - Coin economy cannot be exploited via replay → IdempotencyTest

✅ A05 Security Misconfiguration
   - .env never committed                        → .gitignore
   - APP_DEBUG=false in production               → Deploy checklist
   - Passport keys in storage, not public        → Deploy checklist

✅ A06 Vulnerable Components
   - composer audit in CI pipeline              → GitHub Actions
   - npm audit for Vue admin panel              → GitHub Actions

✅ A07 Auth Failures
   - OTP brute-force rate limited               → RateLimitTest
   - Expired tokens rejected                    → AuthorizationTest
   - Refresh token rotation enforced            → Passport config

✅ A08 Integrity Failures
   - Question answers validated server-side      → Never trust client
   - XP calculated server-side only             → Never trust client
   - Streak logic server-side only              → Never trust client

✅ A09 Logging Failures
   - All auth events logged                     → Laravel Log
   - Failed OTP attempts logged                 → OtpVerification model
   - Admin actions logged                       → Activity log package

✅ A10 SSRF
   - No user-supplied URLs in backend           → N/A for this app
```

---

## 9. CI Pipeline — Test Execution Order

```yaml
# .github/workflows/tests.yml

name: Quizlo Test Suite

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: quizlo_test
          MYSQL_ROOT_PASSWORD: secret
      redis:
        image: redis:7

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.3
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: pdo_mysql, redis

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Run Unit Tests (fast, no DB)
        run: php artisan test --testsuite=Unit --parallel

      - name: Run Feature Tests
        run: php artisan test --testsuite=Feature --parallel
        env:
          DB_DATABASE: quizlo_test

      - name: Run Integration Tests
        run: php artisan test --testsuite=Integration
        env:
          DB_DATABASE: quizlo_test

      - name: Run Security Tests
        run: php artisan test --testsuite=Security

      - name: Security audit (composer)
        run: composer audit
```

---

## 10. Test Configuration (phpunit.xml)

```xml
<phpunit>
  <testsuites>
    <testsuite name="Unit">
      <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
      <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Integration">
      <directory>tests/Integration</directory>
    </testsuite>
    <testsuite name="Security">
      <directory>tests/Security</directory>
    </testsuite>
  </testsuites>

  <php>
    <env name="APP_ENV"       value="testing"/>
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE"   value="quizlo_test"/>
    <env name="CACHE_DRIVER"  value="redis"/>
    <env name="QUEUE_CONNECTION" value="sync"/>   <!-- sync in tests — no async -->
    <env name="PASSPORT_PRIVATE_KEY" value="testing_key"/>
  </php>
</phpunit>
```

---

*End of Canvas 1b — Testing Strategy*
*References Canvas 1 v2.0 (Backend SDD)*
*Canvas 2: Flutter App SDD · Canvas 3: Vue Admin Panel SDD*
