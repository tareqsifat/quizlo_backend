# Quizlo — Backend Software Development Document
### Canvas 1 of 3 · Backend Team · **v2.0**
**Stack:** PHP 8.3 · Laravel 13 · MySQL 8 · Redis · Laravel Passport (OAuth2)
**Updated:** May 2026 · Changelog at bottom

---

## 1. System Architecture Overview

```
┌──────────────────────────────────────────────────────────────────┐
│                          CLIENT LAYER                            │
│     Flutter App (Users)              Vue Admin Panel             │
└───────────────┬──────────────────────────────┬───────────────────┘
                │  HTTPS / REST API             │  HTTPS / REST API
                │  Bearer Token (Passport)      │  Bearer Token (Passport)
┌───────────────▼──────────────────────────────▼───────────────────┐
│                      LARAVEL 13 API SERVER                        │
│                                                                   │
│   ┌──────────────────────────────────────────────────────────┐   │
│   │                    HTTP LAYER                            │   │
│   │  Controller → ServiceInterface → (bound) → Service      │   │
│   │                                  Service → RepoInterface │   │
│   │                                  RepoInterface → Model   │   │
│   └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│   Modules: Auth · ExamType · User · Content · Gamification        │
│            League · Exam · Notification · Admin                   │
└────────────────────────────┬─────────────────────────────────────┘
                             │
               ┌─────────────┴─────────────┐
     ┌─────────▼──────────┐   ┌────────────▼────────────┐
     │    MySQL 8          │   │    Redis                 │
     │  Primary Database   │   │  Cache · Sessions        │
     │                    │   │  Queues · Pub/Sub         │
     └────────────────────┘   └─────────────────────────┘
```

---

## 2. Coding Principles & Rules

### 2.1 SOLID Enforcement

| Principle | How it applies in Quizlo |
|---|---|
| **S** — Single Responsibility | Each class does one job. `XpService` only handles XP. `StreakService` only handles streaks. Controllers only handle HTTP request/response. |
| **O** — Open/Closed | New exam type (SSC, HSC) = new config/seed entry, not a code change. New gamification reward = new class implementing `RewardInterface`. |
| **L** — Liskov Substitution | Any repository or service implementation is swappable without the calling class knowing. |
| **I** — Interface Segregation | Interfaces are small and focused. `StreakServiceInterface` has only streak methods. Admin concerns are in separate admin interfaces. |
| **D** — Dependency Inversion | Every class depends on an interface, never a concrete class. Bindings live in `AppServiceProvider`. |

### 2.2 Dependency Flow — The Law

This is the **only** allowed call chain. No exceptions.

```
Controller
    │
    └── depends on → ServiceInterface
                           │
                           └── (AppServiceProvider binds to) → ConcreteService
                                       │
                                       └── depends on → RepositoryInterface
                                                               │
                                                               └── (AppServiceProvider binds to) → ConcreteRepository
                                                                               │
                                                                               └── Eloquent Model
```

**Controllers never instantiate or typehint concrete Services.**
**Services never instantiate or typehint concrete Repositories.**
**Everything flows through interfaces. Concrete classes exist only in bindings.**

```php
// ✅ CORRECT — Controller typehints the Interface
public function __construct(
    private readonly StreakServiceInterface $streakService
) {}

// ❌ WRONG — Controller typehints the concrete Service
public function __construct(
    private readonly StreakService $streakService
) {}
```

### 2.3 Other Non-Negotiable Rules

- Every request with user input has its own `FormRequest` class. No `$request->validate()` inside controllers.
- Use API Resources (`JsonResource`) for every response. No raw `->toArray()`.
- Use Events + queued Listeners for all side effects after an answer submission.
- Use Jobs for all async operations (notifications, league processing, heart refill).
- Use Observers for model lifecycle hooks (auto-create wallet/streak record when User is created).
- Use Policies for all authorization logic.
- All magic numbers (XP values, heart counts, league sizes) live in `config/quizlo.php` only.

### 2.4 Authentication — Laravel Passport (OAuth2)

Passport replaces Sanctum for all authentication. Reasons: supports token scopes for separating user vs admin access, supports refresh tokens for mobile apps (Flutter), more robust for a product that will scale.

```
User App     → Password Grant Token (phone + OTP exchange)
Admin Panel  → Client Credentials Grant (scoped admin access)
Refresh      → Refresh Token Grant (auto-renew, no re-login)
```

Token scopes defined in `AuthServiceProvider`:
- `user` — standard user access
- `admin` — admin panel access
- `read-content` — read-only content (future: public API)

---

## 3. Directory Structure

```
app/
├── Modules/
│   │
│   ├── Auth/
│   │   ├── Controllers/
│   │   │   └── AuthController.php
│   │   ├── Requests/
│   │   │   ├── SendOtpRequest.php
│   │   │   └── VerifyOtpRequest.php
│   │   ├── Resources/
│   │   │   └── TokenResource.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   └── AuthServiceInterface.php       ← Controller binds to this
│   │   │   └── AuthService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   └── AuthRepositoryInterface.php    ← Service binds to this
│   │   │   └── AuthRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── ExamType/                                  ← NEW DEDICATED MODULE
│   │   ├── Controllers/
│   │   │   └── ExamTypeController.php
│   │   ├── Resources/
│   │   │   ├── ExamTypeResource.php
│   │   │   └── ExamTypeSubjectResource.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   └── ExamTypeServiceInterface.php
│   │   │   └── ExamTypeService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   └── ExamTypeRepositoryInterface.php
│   │   │   └── ExamTypeRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── User/
│   │   ├── Controllers/
│   │   │   └── UserController.php
│   │   ├── Requests/
│   │   │   ├── UpdateProfileRequest.php
│   │   │   ├── SetDailyGoalRequest.php
│   │   │   └── EnrollExamTypeRequest.php          ← User picks their exam type(s)
│   │   ├── Resources/
│   │   │   └── UserResource.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   └── UserServiceInterface.php
│   │   │   └── UserService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   └── UserRepositoryInterface.php
│   │   │   └── UserRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── Content/
│   │   ├── Controllers/
│   │   │   ├── SubjectController.php
│   │   │   ├── LessonController.php
│   │   │   └── QuestionController.php
│   │   ├── Requests/
│   │   │   ├── Admin/
│   │   │   │   ├── StoreQuestionRequest.php
│   │   │   │   ├── UpdateQuestionRequest.php
│   │   │   │   └── StoreLessonRequest.php
│   │   │   └── User/
│   │   │       └── SubmitAnswerRequest.php
│   │   ├── Resources/
│   │   │   ├── SubjectResource.php
│   │   │   ├── LessonResource.php
│   │   │   └── QuestionResource.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   ├── SubjectServiceInterface.php
│   │   │   │   ├── LessonServiceInterface.php
│   │   │   │   └── QuestionServiceInterface.php
│   │   │   ├── SubjectService.php
│   │   │   ├── LessonService.php
│   │   │   └── QuestionService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   ├── SubjectRepositoryInterface.php
│   │   │   │   ├── LessonRepositoryInterface.php
│   │   │   │   └── QuestionRepositoryInterface.php
│   │   │   ├── SubjectRepository.php
│   │   │   ├── LessonRepository.php
│   │   │   └── QuestionRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── Gamification/
│   │   ├── Controllers/
│   │   │   ├── XpController.php
│   │   │   ├── StreakController.php
│   │   │   ├── HeartController.php
│   │   │   └── CoinController.php
│   │   ├── Events/
│   │   │   ├── QuestionAnswered.php
│   │   │   ├── LessonCompleted.php
│   │   │   └── StreakMilestoneReached.php
│   │   ├── Listeners/
│   │   │   ├── AwardXpOnAnswer.php
│   │   │   ├── UpdateStreakOnAnswer.php
│   │   │   ├── DeductHeartOnWrongAnswer.php
│   │   │   ├── UpdateSubjectMastery.php           ← now exam-type scoped
│   │   │   ├── UpdateDailyProgress.php
│   │   │   └── AwardCoinOnLessonComplete.php
│   │   ├── Jobs/
│   │   │   ├── ProcessXpAward.php
│   │   │   └── RefillHearts.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   ├── XpServiceInterface.php
│   │   │   │   ├── StreakServiceInterface.php
│   │   │   │   ├── HeartServiceInterface.php
│   │   │   │   ├── CoinServiceInterface.php
│   │   │   │   └── MasteryServiceInterface.php
│   │   │   ├── XpService.php
│   │   │   ├── StreakService.php
│   │   │   ├── HeartService.php
│   │   │   ├── CoinService.php
│   │   │   └── MasteryService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   ├── XpRepositoryInterface.php
│   │   │   │   ├── StreakRepositoryInterface.php
│   │   │   │   ├── HeartRepositoryInterface.php
│   │   │   │   ├── CoinRepositoryInterface.php
│   │   │   │   └── MasteryRepositoryInterface.php
│   │   │   ├── XpRepository.php
│   │   │   ├── StreakRepository.php
│   │   │   ├── HeartRepository.php
│   │   │   ├── CoinRepository.php
│   │   │   └── MasteryRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── League/
│   │   ├── Controllers/
│   │   │   └── LeagueController.php
│   │   ├── Jobs/
│   │   │   ├── ProcessWeeklyLeaguePromotion.php
│   │   │   └── ResetWeeklyLeague.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   └── LeagueServiceInterface.php
│   │   │   └── LeagueService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   └── LeagueRepositoryInterface.php
│   │   │   └── LeagueRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── Exam/
│   │   ├── Controllers/
│   │   │   ├── ModelTestController.php
│   │   │   └── ExamSessionController.php
│   │   ├── Requests/
│   │   │   ├── StartExamRequest.php
│   │   │   └── SubmitExamRequest.php
│   │   ├── Resources/
│   │   │   └── ExamResultResource.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   └── ExamServiceInterface.php
│   │   │   └── ExamService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   └── ExamRepositoryInterface.php
│   │   │   └── ExamRepository.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   ├── Notification/
│   │   ├── Controllers/
│   │   │   └── NotificationController.php
│   │   ├── Jobs/
│   │   │   ├── SendDailyReminderNotification.php
│   │   │   └── SendStreakWarningNotification.php
│   │   ├── Services/
│   │   │   ├── Contracts/
│   │   │   │   └── NotificationServiceInterface.php
│   │   │   └── PushNotificationService.php
│   │   └── Routes/
│   │       └── api.php
│   │
│   └── Admin/
│       ├── Controllers/
│       │   ├── DashboardController.php
│       │   ├── UserManagementController.php
│       │   ├── ContentManagementController.php
│       │   └── ExamTypeManagementController.php
│       ├── Requests/
│       │   ├── StoreExamTypeRequest.php
│       │   └── AssignSubjectToExamTypeRequest.php
│       └── Routes/
│           └── api.php
│
├── Models/
│   ├── User.php
│   ├── ExamType.php                               ← NEW
│   ├── Subject.php
│   ├── Topic.php
│   ├── Lesson.php
│   ├── Question.php
│   ├── QuestionOption.php
│   ├── UserAnswer.php
│   ├── UserExamType.php                           ← NEW pivot model
│   ├── ExamTypeSubject.php                        ← NEW pivot model
│   ├── UserXp.php
│   ├── XpTransaction.php
│   ├── UserStreak.php
│   ├── UserHeart.php
│   ├── UserCoin.php
│   ├── CoinTransaction.php
│   ├── UserSubjectMastery.php                     ← updated: exam-type scoped
│   ├── UserDailyProgress.php
│   ├── UserLessonCompletion.php
│   ├── League.php
│   ├── LeagueTier.php
│   ├── LeagueSeason.php
│   ├── UserLeague.php
│   ├── ModelTest.php
│   ├── ExamSession.php
│   ├── Achievement.php
│   ├── UserAchievement.php
│   └── ExamSchedule.php
│
├── Providers/
│   ├── AppServiceProvider.php                     ← ALL bindings here
│   ├── AuthServiceProvider.php                    ← Passport scopes here
│   └── EventServiceProvider.php                   ← All event→listener maps
│
└── Http/
    ├── Middleware/
    │   ├── EnsureUserHasHearts.php
    │   ├── EnsureUserEnrolledInExamType.php       ← NEW
    │   └── AdminOnly.php
    └── Resources/
        └── (shared base resources)

config/
└── quizlo.php                                     ← All game constants

routes/
└── api.php                                        ← Loads all module routes
```

---

## 4. Database Schema

### 4.1 Exam Type System (New Core Architecture)

```sql
-- Master exam type registry
-- Launch with 1 row: BCS. Future rows added via seeder/admin, zero code change.
CREATE TABLE exam_types (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,              -- "BCS Preliminary"
    name_bn     VARCHAR(100) NOT NULL,              -- "বিসিএস প্রিলিমিনারি"
    code        VARCHAR(30) UNIQUE NOT NULL,        -- 'BCS', 'SSC', 'HSC', 'UNIV_ADMIT'
    slug        VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    icon        VARCHAR(100) NULL,
    is_active   BOOLEAN DEFAULT TRUE,
    sort_order  TINYINT DEFAULT 0,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);

-- Initial seed (the only entry for now)
INSERT INTO exam_types (name, name_bn, code, slug, is_active, sort_order)
VALUES ('BCS Preliminary', 'বিসিএস প্রিলিমিনারি', 'BCS', 'bcs-preliminary', 1, 1);
```

```sql
-- Subjects (generic — not owned by any exam type)
-- "English Grammar" can belong to BCS, SSC, and HSC simultaneously
CREATE TABLE subjects (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    name_bn         VARCHAR(150) NOT NULL,
    slug            VARCHAR(150) UNIQUE NOT NULL,
    icon            VARCHAR(50) NULL,
    color_hex       VARCHAR(7) NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);

-- PIVOT: Which subjects belong to which exam type
-- Controls what the user sees when they are in BCS mode vs SSC mode
CREATE TABLE exam_type_subject (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_type_id    BIGINT UNSIGNED NOT NULL,
    subject_id      BIGINT UNSIGNED NOT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    sort_order      SMALLINT DEFAULT 0,
    -- Exam-type-specific metadata about this subject
    total_marks     SMALLINT NULL,                  -- BCS: Bangla = 35 marks
    syllabus_note   TEXT NULL,
    created_at      TIMESTAMP,
    UNIQUE KEY uq_exam_subject (exam_type_id, subject_id),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_exam_type (exam_type_id)
);

-- Topics within a subject (generic, no exam-type dependency)
CREATE TABLE topics (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id  BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    name_bn     VARCHAR(200) NOT NULL,
    is_active   BOOLEAN DEFAULT TRUE,
    sort_order  SMALLINT DEFAULT 0,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);
```

### 4.2 Users & Auth

```sql
-- Core user table — exam-type agnostic
-- bcs_target_year removed; exam preference now lives in user_exam_types pivot
CREATE TABLE users (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(100) NOT NULL,
    phone                   VARCHAR(15) UNIQUE NOT NULL,
    email                   VARCHAR(150) UNIQUE NULL,
    avatar                  VARCHAR(255) NULL,
    district                VARCHAR(100) NULL,
    division                VARCHAR(100) NULL,
    daily_goal              TINYINT DEFAULT 20,         -- questions per day (10/20/30)
    first_session_completed BOOLEAN DEFAULT FALSE,      -- first-win guarantee flag
    is_active               BOOLEAN DEFAULT TRUE,
    created_at              TIMESTAMP,
    updated_at              TIMESTAMP
);

-- PIVOT: User's exam type enrollments
-- A user can prepare for BCS AND bank jobs simultaneously
CREATE TABLE user_exam_types (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    exam_type_id    BIGINT UNSIGNED NOT NULL,
    is_primary      BOOLEAN DEFAULT FALSE,             -- one primary exam type per user
    target_year     YEAR NULL,                         -- when do they plan to sit?
    enrolled_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_exam (user_id, exam_type_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    INDEX idx_user (user_id),
    INDEX idx_exam_type (exam_type_id)
);

-- OTP table (phone-based, no password)
CREATE TABLE otp_verifications (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone       VARCHAR(15) NOT NULL,
    otp_code    VARCHAR(6) NOT NULL,
    purpose     ENUM('login','register') DEFAULT 'login',
    expires_at  TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at  TIMESTAMP,
    INDEX idx_phone_purpose (phone, purpose),
    INDEX idx_expires (expires_at)
);

-- Passport tables auto-generated by:
-- php artisan passport:install
-- Tables created: oauth_clients, oauth_access_tokens,
--                 oauth_refresh_tokens, oauth_personal_access_clients
```

### 4.3 Content

```sql
-- Lessons are now exam-type aware
-- Same subject can have different lessons per exam type
CREATE TABLE lessons (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_type_id    BIGINT UNSIGNED NOT NULL,           ← scoped to exam type
    subject_id      BIGINT UNSIGNED NOT NULL,
    topic_id        BIGINT UNSIGNED NULL,
    title           VARCHAR(200) NOT NULL,
    title_bn        VARCHAR(200) NOT NULL,
    description     TEXT NULL,
    xp_reward       SMALLINT DEFAULT 20,
    coin_reward     SMALLINT DEFAULT 10,
    difficulty      ENUM('easy','medium','hard') DEFAULT 'medium',
    question_count  TINYINT DEFAULT 10,
    sort_order      SMALLINT DEFAULT 0,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (topic_id) REFERENCES topics(id),
    INDEX idx_exam_subject (exam_type_id, subject_id)
);

-- Questions (generic — belong to subject+topic, reusable across exam types)
CREATE TABLE questions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id      BIGINT UNSIGNED NOT NULL,
    topic_id        BIGINT UNSIGNED NULL,
    lesson_id       BIGINT UNSIGNED NULL,
    question_text   TEXT NOT NULL,
    question_bn     TEXT NULL,
    explanation     TEXT NULL,                          -- shame-free explanation
    difficulty      ENUM('easy','medium','hard') DEFAULT 'medium',
    xp_value        TINYINT DEFAULT 10,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (topic_id) REFERENCES topics(id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id),
    INDEX idx_subject_difficulty (subject_id, difficulty)
);

-- PIVOT: Question to exam type mapping
-- A question can be tagged to specific exam types
-- If no row exists for a question = generic question (appears in all)
CREATE TABLE exam_type_question (
    exam_type_id    BIGINT UNSIGNED NOT NULL,
    question_id     BIGINT UNSIGNED NOT NULL,
    source_batch    VARCHAR(20) NULL,                   -- 'BCS-44', 'BCS-45', etc.
    source_year     YEAR NULL,
    PRIMARY KEY (exam_type_id, question_id),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_exam_type (exam_type_id),
    INDEX idx_batch (source_batch)
);

-- Answer options per question
CREATE TABLE question_options (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id     BIGINT UNSIGNED NOT NULL,
    option_text     TEXT NOT NULL,
    option_text_bn  TEXT NULL,
    is_correct      BOOLEAN DEFAULT FALSE,
    sort_order      TINYINT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
);
```

### 4.4 User Progress

```sql
-- Every answer a user gives — exam-type context captured
CREATE TABLE user_answers (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    exam_type_id        BIGINT UNSIGNED NOT NULL,       ← which exam context
    question_id         BIGINT UNSIGNED NOT NULL,
    selected_option_id  BIGINT UNSIGNED NOT NULL,
    is_correct          BOOLEAN NOT NULL,
    time_taken_ms       SMALLINT UNSIGNED NULL,
    xp_earned           TINYINT DEFAULT 0,
    session_type        ENUM('lesson','model_test','practice','exam_mode') NOT NULL,
    session_id          BIGINT UNSIGNED NULL,
    answered_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    FOREIGN KEY (question_id) REFERENCES questions(id),
    INDEX idx_user_exam_date (user_id, exam_type_id, answered_at),
    INDEX idx_user_question (user_id, question_id)
);

-- Subject mastery rings — PER USER, PER EXAM TYPE, PER SUBJECT
-- A user preparing for both BCS and SSC has separate mastery rows for each
CREATE TABLE user_subject_mastery (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    exam_type_id        BIGINT UNSIGNED NOT NULL,       ← KEY CHANGE
    subject_id          BIGINT UNSIGNED NOT NULL,
    total_answered      INT DEFAULT 0,
    total_correct       INT DEFAULT 0,
    mastery_percentage  DECIMAL(5,2) DEFAULT 0.00,
    badge_earned        BOOLEAN DEFAULT FALSE,
    badge_earned_at     TIMESTAMP NULL,
    last_activity_at    TIMESTAMP NULL,
    updated_at          TIMESTAMP,
    UNIQUE KEY uq_user_exam_subject (user_id, exam_type_id, subject_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    INDEX idx_user_exam (user_id, exam_type_id)
);

-- Daily study progress — per user, per exam type, per date
CREATE TABLE user_daily_progress (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    exam_type_id        BIGINT UNSIGNED NOT NULL,       ← exam-type scoped
    date                DATE NOT NULL,
    goal_questions      TINYINT DEFAULT 20,
    answered_questions  TINYINT DEFAULT 0,
    correct_questions   TINYINT DEFAULT 0,
    xp_earned_today     SMALLINT DEFAULT 0,
    goal_met            BOOLEAN DEFAULT FALSE,
    UNIQUE KEY uq_user_exam_date (user_id, exam_type_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    INDEX idx_date (date)
);

-- Lesson completion — exam type captured via lesson's exam_type_id
CREATE TABLE user_lesson_completions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    lesson_id       BIGINT UNSIGNED NOT NULL,
    score           TINYINT UNSIGNED NOT NULL,          -- 0–100
    xp_earned       SMALLINT DEFAULT 0,
    coins_earned    SMALLINT DEFAULT 0,
    completed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_lesson (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id)
);
```

### 4.5 Gamification

```sql
-- XP is global (not per exam type — one XP pool per user)
CREATE TABLE user_xp (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED UNIQUE NOT NULL,
    total_xp    INT DEFAULT 0,
    level       SMALLINT DEFAULT 1,
    updated_at  TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- XP ledger — exam_type_id captured for analytics
CREATE TABLE xp_transactions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    exam_type_id    BIGINT UNSIGNED NULL,
    amount          SMALLINT NOT NULL,
    reason          ENUM(
                        'correct_answer',
                        'lesson_complete',
                        'streak_bonus',
                        'daily_goal_met',
                        'model_test_complete',
                        'league_promotion',
                        'streak_milestone'
                    ) NOT NULL,
    reference_id    BIGINT UNSIGNED NULL,
    earned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_date (user_id, earned_at)
);

-- Streak is global (one streak per user, not per exam type)
CREATE TABLE user_streaks (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                 BIGINT UNSIGNED UNIQUE NOT NULL,
    current_streak          SMALLINT DEFAULT 0,
    longest_streak          SMALLINT DEFAULT 0,
    last_activity_date      DATE NULL,
    freeze_used_today       BOOLEAN DEFAULT FALSE,
    streak_freeze_count     TINYINT DEFAULT 0,
    updated_at              TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Hearts are global (one pool per user)
CREATE TABLE user_hearts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED UNIQUE NOT NULL,
    current_hearts  TINYINT DEFAULT 5,
    max_hearts      TINYINT DEFAULT 5,
    last_refill_at  TIMESTAMP NULL,
    updated_at      TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Coin wallet (global per user)
CREATE TABLE user_coins (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED UNIQUE NOT NULL,
    balance     INT DEFAULT 0,
    updated_at  TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Coin ledger
CREATE TABLE coin_transactions (
    id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    amount  SMALLINT NOT NULL,
    type    ENUM('earn','spend') NOT NULL,
    reason  ENUM(
                'lesson_complete',
                'streak_milestone',
                'daily_goal_met',
                'streak_freeze_purchase',
                'extra_heart_purchase',
                'hint_purchase'
            ) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id)
);

-- Achievement definitions
CREATE TABLE achievements (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key         VARCHAR(100) UNIQUE NOT NULL,
    title       VARCHAR(150) NOT NULL,
    title_bn    VARCHAR(150) NOT NULL,
    description TEXT NULL,
    icon        VARCHAR(100) NULL,
    xp_reward   SMALLINT DEFAULT 0,
    type        ENUM('streak','subject','exam','social','daily') NOT NULL,
    -- NULL exam_type_id = achievement applies to all exam types
    exam_type_id BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP,
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id)
);

CREATE TABLE user_achievements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    achievement_id  BIGINT UNSIGNED NOT NULL,
    earned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (achievement_id) REFERENCES achievements(id)
);
```

### 4.6 League System

```sql
CREATE TABLE league_tiers (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(50) NOT NULL,
    name_bn             VARCHAR(50) NOT NULL,
    tier_order          TINYINT UNIQUE NOT NULL,        -- 1=Bronze … 5=Diamond
    color_hex           VARCHAR(7) NULL,
    promotion_spots     TINYINT DEFAULT 10,
    relegation_spots    TINYINT DEFAULT 5,
    max_members         TINYINT DEFAULT 30
);

-- Seasons are per exam type — BCS league separate from SSC league
CREATE TABLE league_seasons (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_type_id    BIGINT UNSIGNED NOT NULL,           ← per exam type
    week_number     SMALLINT NOT NULL,
    year            YEAR NOT NULL,
    starts_at       TIMESTAMP NOT NULL,
    ends_at         TIMESTAMP NOT NULL,
    is_active       BOOLEAN DEFAULT FALSE,
    processed       BOOLEAN DEFAULT FALSE,
    UNIQUE KEY uq_exam_week_year (exam_type_id, week_number, year),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id)
);

CREATE TABLE user_leagues (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    league_season_id    BIGINT UNSIGNED NOT NULL,
    league_tier_id      BIGINT UNSIGNED NOT NULL,
    group_number        SMALLINT NOT NULL,
    weekly_xp           INT DEFAULT 0,
    rank                SMALLINT NULL,
    promoted            BOOLEAN NULL,
    relegated           BOOLEAN NULL,
    UNIQUE KEY uq_user_season (user_id, league_season_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (league_season_id) REFERENCES league_seasons(id),
    FOREIGN KEY (league_tier_id) REFERENCES league_tiers(id),
    INDEX idx_season_tier_group (league_season_id, league_tier_id, group_number)
);
```

### 4.7 Exam System

```sql
-- Model tests — now tied to exam_type (not exam_category)
CREATE TABLE model_tests (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_type_id        BIGINT UNSIGNED NOT NULL,       ← exam type reference
    title               VARCHAR(200) NOT NULL,
    title_bn            VARCHAR(200) NOT NULL,
    total_questions     SMALLINT DEFAULT 100,
    duration_minutes    SMALLINT DEFAULT 60,
    xp_reward           SMALLINT DEFAULT 100,
    is_active           BOOLEAN DEFAULT TRUE,
    created_at          TIMESTAMP,
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id)
);

CREATE TABLE model_test_questions (
    model_test_id   BIGINT UNSIGNED NOT NULL,
    question_id     BIGINT UNSIGNED NOT NULL,
    sort_order      SMALLINT DEFAULT 0,
    PRIMARY KEY (model_test_id, question_id),
    FOREIGN KEY (model_test_id) REFERENCES model_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

CREATE TABLE exam_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    exam_type_id    BIGINT UNSIGNED NOT NULL,           ← captured at session start
    model_test_id   BIGINT UNSIGNED NULL,
    session_type    ENUM('model_test','practice','exam_mode') NOT NULL,
    status          ENUM('in_progress','completed','abandoned') DEFAULT 'in_progress',
    total_questions SMALLINT NOT NULL,
    answered_count  SMALLINT DEFAULT 0,
    correct_count   SMALLINT DEFAULT 0,
    score_percent   DECIMAL(5,2) NULL,
    xp_earned       SMALLINT DEFAULT 0,
    started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at    TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    FOREIGN KEY (model_test_id) REFERENCES model_tests(id)
);

-- Exam schedule countdown — per exam type
CREATE TABLE exam_schedules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_type_id    BIGINT UNSIGNED NOT NULL,
    batch_label     VARCHAR(50) NULL,                   -- '47th BCS', 'SSC 2027'
    exam_stage      ENUM('preliminary','written','viva','main') NOT NULL,
    scheduled_date  DATE NOT NULL,
    is_confirmed    BOOLEAN DEFAULT FALSE,
    note            VARCHAR(255) NULL,
    created_at      TIMESTAMP,
    FOREIGN KEY (exam_type_id) REFERENCES exam_types(id),
    INDEX idx_exam_type_date (exam_type_id, scheduled_date)
);
```

### 4.8 Complete Pivot Table Summary

```
┌─────────────────────────────────────────────────────────────────┐
│                    ALL PIVOT TABLES IN QUIZLO                   │
├──────────────────────────┬──────────────────────────────────────┤
│ Table Name               │ Relationship                         │
├──────────────────────────┼──────────────────────────────────────┤
│ exam_type_subject        │ ExamType ←→ Subject                  │
│ exam_type_question       │ ExamType ←→ Question (with metadata) │
│ user_exam_types          │ User ←→ ExamType (enrollment)        │
│ model_test_questions     │ ModelTest ←→ Question                │
│ user_achievements        │ User ←→ Achievement                  │
│ user_subject_mastery     │ User + ExamType + Subject (3-way)    │
│ user_daily_progress      │ User + ExamType + Date (3-way)       │
└──────────────────────────┴──────────────────────────────────────┘
```

---

## 5. Service Interface Pattern — Full Implementation

### 5.1 Three-layer interface structure

```
ServiceInterface     ← Controller depends on this
    │
    └── ConcreteService (implements ServiceInterface)
            │
            └── RepositoryInterface ← Service depends on this
                        │
                        └── ConcreteRepository (implements RepositoryInterface)
                                    │
                                    └── Eloquent Model
```

### 5.2 Service Interface — Example (StreakServiceInterface)

```php
<?php
// app/Modules/Gamification/Services/Contracts/StreakServiceInterface.php

namespace App\Modules\Gamification\Services\Contracts;

use App\Models\User;

interface StreakServiceInterface
{
    public function processUserActivity(User $user): array;
    public function getStreakStatus(User $user): array;
    public function useStreakFreeze(User $user): bool;
    public function addStreakFreeze(User $user, int $count = 1): void;
}
```

### 5.3 Concrete Service — Implements the Interface

```php
<?php
// app/Modules/Gamification/Services/StreakService.php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use App\Modules\Gamification\Repositories\Contracts\StreakRepositoryInterface;
use App\Modules\Gamification\Events\StreakMilestoneReached;
use Carbon\Carbon;

class StreakService implements StreakServiceInterface
{
    private const MILESTONES = [7, 14, 30, 60, 100, 365];

    // Depends on RepositoryInterface — not concrete class
    public function __construct(
        private readonly StreakRepositoryInterface $streakRepository
    ) {}

    public function processUserActivity(User $user): array
    {
        $streak = $this->streakRepository->findByUser($user->id);
        $today  = now()->toDateString();

        if ($streak?->last_activity_date === $today) {
            return $this->buildResponse($streak);
        }

        $yesterday = Carbon::yesterday()->toDateString();

        if ($streak?->last_activity_date === $yesterday) {
            $streak = $this->streakRepository->incrementStreak($user->id);
            $this->checkMilestone($user, $streak->current_streak);
            return $this->buildResponse($streak, updated: true);
        }

        if ($this->canUseFreeze($streak)) {
            $this->streakRepository->useStreakFreeze($user->id);
            $streak = $this->streakRepository->incrementStreak($user->id);
            return $this->buildResponse($streak, updated: true, freezeUsed: true);
        }

        $this->streakRepository->resetStreak($user->id);
        $streak = $this->streakRepository->incrementStreak($user->id);
        return $this->buildResponse($streak, updated: true, wasReset: true);
    }

    public function getStreakStatus(User $user): array
    {
        $streak = $this->streakRepository->findByUser($user->id);
        return $this->buildResponse($streak);
    }

    public function useStreakFreeze(User $user): bool
    {
        return $this->streakRepository->useStreakFreeze($user->id);
    }

    public function addStreakFreeze(User $user, int $count = 1): void
    {
        $this->streakRepository->addStreakFreeze($user->id, $count);
    }

    private function canUseFreeze($streak): bool
    {
        if (!$streak || $streak->freeze_used_today) return false;
        if ($streak->streak_freeze_count < 1) return false;

        $daysMissed = $streak->last_activity_date
            ? Carbon::parse($streak->last_activity_date)->diffInDays(now())
            : 999;

        return $daysMissed === 2;
    }

    private function checkMilestone(User $user, int $currentStreak): void
    {
        if (in_array($currentStreak, self::MILESTONES)) {
            event(new StreakMilestoneReached($user, $currentStreak));
        }
    }

    private function buildResponse(
        $streak,
        bool $updated = false,
        bool $freezeUsed = false,
        bool $wasReset = false
    ): array {
        return [
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'freeze_count'   => $streak?->streak_freeze_count ?? 0,
            'streak_updated' => $updated,
            'freeze_used'    => $freezeUsed,
            'was_reset'      => $wasReset,
        ];
    }
}
```

### 5.4 Repository Interface

```php
<?php
// app/Modules/Gamification/Repositories/Contracts/StreakRepositoryInterface.php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\UserStreak;

interface StreakRepositoryInterface
{
    public function findByUser(int $userId): ?UserStreak;
    public function incrementStreak(int $userId): UserStreak;
    public function resetStreak(int $userId): void;
    public function useStreakFreeze(int $userId): bool;
    public function addStreakFreeze(int $userId, int $count = 1): void;
}
```

### 5.5 Controller — Calls ServiceInterface Only

```php
<?php
// app/Modules/Gamification/Controllers/StreakController.php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakController extends Controller
{
    // Typehinted to Interface — never to StreakService directly
    public function __construct(
        private readonly StreakServiceInterface $streakService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $result = $this->streakService->processUserActivity($request->user());

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function spendFreeze(Request $request): JsonResponse
    {
        $used = $this->streakService->useStreakFreeze($request->user());

        return response()->json([
            'success' => $used,
            'message' => $used ? 'Streak freeze applied.' : 'No freezes available.',
        ]);
    }
}
```

### 5.6 AppServiceProvider — All Bindings

```php
<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Auth
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\AuthRepository;

// ExamType
use App\Modules\ExamType\Services\Contracts\ExamTypeServiceInterface;
use App\Modules\ExamType\Services\ExamTypeService;
use App\Modules\ExamType\Repositories\Contracts\ExamTypeRepositoryInterface;
use App\Modules\ExamType\Repositories\ExamTypeRepository;

// User
use App\Modules\User\Services\Contracts\UserServiceInterface;
use App\Modules\User\Services\UserService;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;

// Content
use App\Modules\Content\Services\Contracts\{SubjectServiceInterface, LessonServiceInterface, QuestionServiceInterface};
use App\Modules\Content\Services\{SubjectService, LessonService, QuestionService};
use App\Modules\Content\Repositories\Contracts\{SubjectRepositoryInterface, LessonRepositoryInterface, QuestionRepositoryInterface};
use App\Modules\Content\Repositories\{SubjectRepository, LessonRepository, QuestionRepository};

// Gamification
use App\Modules\Gamification\Services\Contracts\{XpServiceInterface, StreakServiceInterface, HeartServiceInterface, CoinServiceInterface, MasteryServiceInterface};
use App\Modules\Gamification\Services\{XpService, StreakService, HeartService, CoinService, MasteryService};
use App\Modules\Gamification\Repositories\Contracts\{XpRepositoryInterface, StreakRepositoryInterface, HeartRepositoryInterface, CoinRepositoryInterface, MasteryRepositoryInterface};
use App\Modules\Gamification\Repositories\{XpRepository, StreakRepository, HeartRepository, CoinRepository, MasteryRepository};

// League
use App\Modules\League\Services\Contracts\LeagueServiceInterface;
use App\Modules\League\Services\LeagueService;
use App\Modules\League\Repositories\Contracts\LeagueRepositoryInterface;
use App\Modules\League\Repositories\LeagueRepository;

// Exam
use App\Modules\Exam\Services\Contracts\ExamServiceInterface;
use App\Modules\Exam\Services\ExamService;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\ExamRepository;

// Notification
use App\Modules\Notification\Services\Contracts\NotificationServiceInterface;
use App\Modules\Notification\Services\PushNotificationService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Auth ──────────────────────────────────────────
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);

        // ── ExamType ──────────────────────────────────────
        $this->app->bind(ExamTypeServiceInterface::class, ExamTypeService::class);
        $this->app->bind(ExamTypeRepositoryInterface::class, ExamTypeRepository::class);

        // ── User ──────────────────────────────────────────
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // ── Content ───────────────────────────────────────
        $this->app->bind(SubjectServiceInterface::class, SubjectService::class);
        $this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
        $this->app->bind(LessonServiceInterface::class, LessonService::class);
        $this->app->bind(LessonRepositoryInterface::class, LessonRepository::class);
        $this->app->bind(QuestionServiceInterface::class, QuestionService::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);

        // ── Gamification ──────────────────────────────────
        $this->app->bind(XpServiceInterface::class, XpService::class);
        $this->app->bind(XpRepositoryInterface::class, XpRepository::class);
        $this->app->bind(StreakServiceInterface::class, StreakService::class);
        $this->app->bind(StreakRepositoryInterface::class, StreakRepository::class);
        $this->app->bind(HeartServiceInterface::class, HeartService::class);
        $this->app->bind(HeartRepositoryInterface::class, HeartRepository::class);
        $this->app->bind(CoinServiceInterface::class, CoinService::class);
        $this->app->bind(CoinRepositoryInterface::class, CoinRepository::class);
        $this->app->bind(MasteryServiceInterface::class, MasteryService::class);
        $this->app->bind(MasteryRepositoryInterface::class, MasteryRepository::class);

        // ── League ────────────────────────────────────────
        $this->app->bind(LeagueServiceInterface::class, LeagueService::class);
        $this->app->bind(LeagueRepositoryInterface::class, LeagueRepository::class);

        // ── Exam ──────────────────────────────────────────
        $this->app->bind(ExamServiceInterface::class, ExamService::class);
        $this->app->bind(ExamRepositoryInterface::class, ExamRepository::class);

        // ── Notification ──────────────────────────────────
        $this->app->bind(NotificationServiceInterface::class, PushNotificationService::class);
    }
}
```

### 5.7 Passport AuthServiceProvider

```php
<?php
// app/Providers/AuthServiceProvider.php

use Laravel\Passport\Passport;

public function boot(): void
{
    Passport::tokensCan([
        'user'         => 'Standard user access',
        'admin'        => 'Admin panel access',
        'read-content' => 'Read-only public content',
    ]);

    Passport::setDefaultScope(['user']);

    Passport::tokensExpireIn(now()->addDays(15));
    Passport::refreshTokensExpireIn(now()->addDays(30));
    Passport::personalAccessTokensExpireIn(now()->addMonths(6));
}
```

---

## 6. Request Validation — Separate File Pattern

```php
<?php
// app/Modules/Content/Requests/User/SubmitAnswerRequest.php

namespace App\Modules\Content\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id'      => ['required', 'integer', 'exists:exam_types,id'],
            'question_id'       => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id'=> ['required', 'integer', 'exists:question_options,id'],
            'session_id'        => ['nullable', 'integer', 'exists:exam_sessions,id'],
            'session_type'      => ['required', 'in:lesson,model_test,practice,exam_mode'],
            'time_taken_ms'     => ['nullable', 'integer', 'min:0', 'max:300000'],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_type_id.exists'       => 'Invalid exam type.',
            'question_id.exists'        => 'Invalid question.',
            'selected_option_id.exists' => 'Invalid answer option.',
            'session_type.in'           => 'Invalid session type.',
        ];
    }
}
```

```php
<?php
// app/Modules/User/Requests/EnrollExamTypeRequest.php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollExamTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id'  => ['required', 'integer', 'exists:exam_types,id'],
            'is_primary'    => ['sometimes', 'boolean'],
            'target_year'   => ['nullable', 'integer', 'min:2024', 'max:2035'],
        ];
    }
}
```

---

## 7. Event-Driven Architecture — Answer Submission Chain

```
User submits answer (POST /api/v1/questions/answer)
         │
         ▼
SubmitAnswerRequest::validate()
         │
         ▼
QuestionController::answer()
         │  calls →
         ▼
QuestionServiceInterface::processAnswer($user, $request)
         │
         ├── Validates selected_option belongs to question
         ├── Resolves is_correct
         ├── Saves UserAnswer row
         ├── Fires → QuestionAnswered event (with exam_type context)
         │
         └── Returns immediate response to controller
                  │
                  ▼
         QuestionAnswered event dispatched
                  │
                  ├── AwardXpOnAnswer (queued)
                  │       → XpServiceInterface::awardForAnswer()
                  │
                  ├── UpdateStreakOnAnswer (queued)
                  │       → StreakServiceInterface::processUserActivity()
                  │
                  ├── DeductHeartOnWrongAnswer (queued, only if !is_correct)
                  │       → HeartServiceInterface::deduct()
                  │
                  ├── UpdateSubjectMastery (queued)
                  │       → MasteryServiceInterface::updateForExamType($examTypeId, $subjectId)
                  │
                  ├── UpdateDailyProgress (queued)
                  │       → Updates user_daily_progress for this exam_type + date
                  │
                  └── UpdateLeagueXp (queued)
                          → Updates user_leagues.weekly_xp for this exam type's season
```

---

## 8. Redis Caching Strategy

```php
// config/quizlo.php

return [

    'cache' => [
        'leaderboard_ttl'           => 300,     // 5 min
        'league_standings_ttl'      => 120,     // 2 min
        'user_streak_ttl'           => 60,      // 1 min
        'subject_list_ttl'          => 86400,   // 24 hours
        'exam_type_subjects_ttl'    => 86400,   // 24 hours (rarely changes)
        'user_stats_ttl'            => 300,     // 5 min
        'exam_countdown_ttl'        => 3600,    // 1 hour
        'user_mastery_ttl'          => 300,     // 5 min
    ],

    'gamification' => [
        'xp_per_correct_answer'     => 10,
        'xp_lesson_complete'        => 20,
        'xp_daily_goal_bonus'       => 50,
        'xp_streak_7_bonus'         => 100,
        'xp_streak_30_bonus'        => 300,
        'xp_league_promotion'       => 200,
        'coins_lesson_complete'     => 10,
        'coins_daily_goal'          => 25,
        'max_hearts'                => 5,
        'heart_refill_minutes'      => 30,
        'streak_grace_days'         => 1,
    ],

    'league' => [
        'group_size'                => 30,
        'promotion_spots'           => 10,
        'relegation_spots'          => 5,
        'reset_day'                 => 0,       // Sunday
        'reset_time'                => '00:00',
    ],

];
```

**Cache key conventions (always include exam_type_id for scoped data):**

```php
// Correct: exam-type scoped
"mastery:user:{$userId}:exam:{$examTypeId}"
"league:{$seasonId}:tier:{$tierId}:group:{$groupNumber}"
"subjects:exam:{$examTypeId}"

// Correct: global (not exam-type scoped)
"streak:user:{$userId}"
"xp:user:{$userId}"
"hearts:user:{$userId}"
```

---

## 9. API Routes Reference

### 9.1 Public Routes

```
POST   /api/v1/auth/send-otp
POST   /api/v1/auth/verify-otp
POST   /api/v1/auth/refresh-token
GET    /api/v1/exam-types                       ← public list for onboarding
```

### 9.2 User Routes (Passport — scope: user)

```
# Profile & Exam Enrollment
GET    /api/v1/user/profile
PUT    /api/v1/user/profile
PUT    /api/v1/user/daily-goal
POST   /api/v1/user/exam-types                  ← enroll in an exam type
DELETE /api/v1/user/exam-types/{examType}
PATCH  /api/v1/user/exam-types/{examType}/set-primary

# Content (all scoped by exam type via query param or header)
GET    /api/v1/subjects?exam_type_id=1
GET    /api/v1/subjects/{subject}/lessons?exam_type_id=1
GET    /api/v1/lessons/{lesson}/questions
POST   /api/v1/lessons/{lesson}/complete

# Answering
POST   /api/v1/questions/answer                 ← includes exam_type_id in body

# Gamification Dashboard (single call — all state)
GET    /api/v1/gamification/dashboard?exam_type_id=1

# Streak
GET    /api/v1/gamification/streak
POST   /api/v1/gamification/streak/freeze       ← spend freeze

# Hearts
GET    /api/v1/gamification/hearts
POST   /api/v1/gamification/hearts/refill

# Coins
GET    /api/v1/gamification/coins
POST   /api/v1/gamification/coins/spend

# League (per exam type)
GET    /api/v1/league/current?exam_type_id=1
GET    /api/v1/league/history?exam_type_id=1

# Exam
GET    /api/v1/model-tests?exam_type_id=1
POST   /api/v1/model-tests/{test}/start
POST   /api/v1/exam-sessions/{session}/submit
GET    /api/v1/exam-sessions/{session}/result
GET    /api/v1/exam-countdown?exam_type_id=1    ← BCS in 47 days

# Progress (all exam-type scoped)
GET    /api/v1/progress/subjects?exam_type_id=1     ← mastery rings
GET    /api/v1/progress/daily?exam_type_id=1        ← daily goal
GET    /api/v1/progress/personal-best?exam_type_id=1

# Achievements
GET    /api/v1/achievements
GET    /api/v1/achievements/earned

# Social
GET    /api/v1/social/activity-feed
POST   /api/v1/achievements/{id}/share
```

### 9.3 Admin Routes (Passport — scope: admin)

```
GET    /api/v1/admin/dashboard/stats

# Exam Type Management
GET    /api/v1/admin/exam-types
POST   /api/v1/admin/exam-types
PUT    /api/v1/admin/exam-types/{examType}
POST   /api/v1/admin/exam-types/{examType}/subjects     ← assign subjects
DELETE /api/v1/admin/exam-types/{examType}/subjects/{subject}

# Content
GET    /api/v1/admin/questions
POST   /api/v1/admin/questions
PUT    /api/v1/admin/questions/{question}
DELETE /api/v1/admin/questions/{question}
POST   /api/v1/admin/questions/import                   ← bulk BCS PDF import
POST   /api/v1/admin/questions/{question}/exam-types    ← tag to exam types

GET    /api/v1/admin/lessons
POST   /api/v1/admin/lessons
PUT    /api/v1/admin/lessons/{lesson}

# Users
GET    /api/v1/admin/users
GET    /api/v1/admin/users/{user}
PATCH  /api/v1/admin/users/{user}/toggle-active

# Exam Schedule
GET    /api/v1/admin/exam-schedules?exam_type_id=1
POST   /api/v1/admin/exam-schedules

# Analytics
GET    /api/v1/admin/analytics/retention?exam_type_id=1
GET    /api/v1/admin/analytics/daily-actives
GET    /api/v1/admin/analytics/subject-performance?exam_type_id=1
```

---

## 10. Scheduled Jobs

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // League reset — runs per active exam type (BCS now, SSC/HSC later)
    $schedule->job(new ResetWeeklyLeague)->weeklyOn(0, '00:00')->withoutOverlapping();
    $schedule->job(new ProcessWeeklyLeaguePromotion)->weeklyOn(0, '00:05')->withoutOverlapping();

    // Daily notifications — Bangladesh timezone
    $schedule->job(new SendDailyReminderNotification)
             ->dailyAt('08:00')->timezone('Asia/Dhaka');

    $schedule->job(new SendStreakWarningNotification)
             ->dailyAt('20:00')->timezone('Asia/Dhaka');

    // Heart refill — every 30 min
    $schedule->job(new RefillHearts)->everyThirtyMinutes();

    // Daily progress reset at midnight
    $schedule->command('quizlo:reset-daily-goals')
             ->dailyAt('00:00')->timezone('Asia/Dhaka');
}
```

---

## 11. Standard API Response Format

```json
// Success
{
    "success": true,
    "data": {},
    "message": null,
    "meta": { "pagination": {} }
}

// Error
{
    "success": false,
    "data": null,
    "message": "Validation failed",
    "errors": { "exam_type_id": ["Invalid exam type."] }
}

// Answer submission response (immediate — before queued side-effects complete)
{
    "success": true,
    "data": {
        "is_correct": true,
        "correct_option_id": 3,
        "explanation": "বাংলাদেশের প্রথম রাষ্ট্রপতি ছিলেন...",
        "xp_earned": 10,
        "total_xp": 1420,
        "streak": {
            "current": 7,
            "updated": true,
            "milestone_reached": true,
            "milestone_day": 7
        },
        "hearts": {
            "current": 5,
            "max": 5,
            "deducted": false
        },
        "mastery": {
            "subject_id": 2,
            "exam_type_id": 1,
            "new_percentage": 42.50
        }
    }
}
```

---

## 12. First Win Guarantee

```
1. users.first_session_completed = FALSE for all new users
2. QuestionService::getLessonQuestions() checks this flag
3. If FALSE → return only difficulty = 'easy' questions
4. After first lesson completion → set first_session_completed = TRUE
5. Session 2+ → normal difficulty distribution
6. This logic is NEVER exposed to any API response or UI
```

---

## 13. Developer Setup

```bash
# Requirements
PHP 8.3+  |  Laravel 13  |  MySQL 8.0+  |  Redis 7+  |  Composer 2+

# Setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Passport setup (run once)
php artisan passport:install
php artisan passport:keys

# Storage & cache
php artisan storage:link
php artisan config:cache
php artisan route:cache

# Queue worker (use Supervisor in production)
php artisan queue:work redis --queue=high,default,low

# Crontab entry
* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
```

---

## Changelog — v1.0 → v2.0

| # | Change | Reason |
|---|--------|--------|
| 1 | Replaced Sanctum with **Laravel Passport** (OAuth2) | Refresh tokens for Flutter, token scopes for user/admin separation |
| 2 | **Controller → ServiceInterface** (not concrete Service) | Full DIP compliance at HTTP layer |
| 3 | **Service → RepositoryInterface** (unchanged, now documented with ServiceInterface layer above it) | Consistency |
| 4 | Added `ServiceInterface` file for every module | Controllers depend on abstraction |
| 5 | Removed `bcs_target_year` from `users` table | Not relevant when user prepares for multiple exam types |
| 6 | Added `exam_types` table | Future-proof: SSC, HSC, Bank Jobs, University Admission |
| 7 | Added `exam_type_subject` pivot | Subjects are generic; assignment to exam types is flexible |
| 8 | Added `exam_type_question` pivot | Questions tagged to specific exams (BCS batch metadata) |
| 9 | Added `user_exam_types` pivot | User enrolls in one or more exam types with target year |
| 10 | `user_subject_mastery` — added `exam_type_id` | Mastery rings are now per exam type, not global |
| 11 | `user_daily_progress` — added `exam_type_id` | Daily goals tracked per exam type |
| 12 | `user_answers` — added `exam_type_id` | Every answer captures exam context for analytics |
| 13 | `league_seasons` — added `exam_type_id` | Separate leagues for BCS vs SSC candidates |
| 14 | `lessons` — added `exam_type_id` | Same subject, different lesson structure per exam |
| 15 | `exam_schedules` — added `exam_type_id` | Countdown works for any exam, not just BCS |
| 16 | Cache keys updated to include `exam_type_id` where scoped | Prevents data bleed between exam types |
| 17 | All API endpoints that return exam-scoped data now accept `?exam_type_id=` | Client controls context |
| 18 | Added `ExamType` module with its own Controller/Service/Repository | Clean separation |

---

*End of Canvas 1 — Backend SDD v2.0*
*Canvas 2: Flutter App (User Mobile) SDD*
*Canvas 3: Vue Admin Panel SDD*
