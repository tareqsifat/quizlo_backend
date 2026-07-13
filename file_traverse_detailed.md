# Quizlo Backend Detailed File Traverse Documentation

This document explains the file execution path of every single backend API endpoint in the Quizlo system, tracing the control flow from route definitions to controllers, request validation classes, service layers, repositories, database models, and event-driven side effects.

---

## 1. Authentication Module

### A. Send OTP
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/send-otp`)
      ↳ AuthController@sendOtp
         ↳ SendOtpRequest (validation)
            ↳ AuthService@sendOtp
               ↳ AuthRepository@createOtp
                  └── OtpVerification (inserts/updates)
* **Flow Branching**:
  * If phone is `'01711223344'` -> Mock code `'123456'`
  * Else -> Generate random 6-digit integer `rand(100000, 999999)`
* **Output**: Returns JSON `{ success: true, message, otp_code }`

### B. Verify OTP
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/verify-otp`)
      ↳ AuthController@verifyOtp
         ↳ VerifyOtpRequest (validation)
            ↳ AuthService@verifyOtp
               ↳ AuthRepository@findLatestOtp -> markOtpVerified -> findUserByPhone
                  ├── OtpVerification (updates verified_at)
                  └── User (queries/inserts)
                     └── AuthService@issueTokensForUser (internal helper)
                        └── Requests Passport token from `/oauth/token`
* **Flow Branching**:
  * If OTP not found or expired -> Returns `success = false` (400 response)
  * Else -> Marks OTP as verified. Checks if user exists. If not, calls `AuthRepository@createUser` to register a new user.
* **Output**: Returns TokenResource and User JSON.

### C. Register User
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/register`)
      ↳ AuthController@register
         ↳ Inline Validator
            ↳ AuthService@register
               ↳ AuthRepository@createUser
                  ├── User (inserts with email_verified_at set to now)
                  └── AuthService@issueTokensForUser (internal login helper)
* **Output**: Returns TokenResource and User details.

### D. Email Login
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/login`)
      ↳ AuthController@login
         ↳ Inline Validator
            ↳ AuthService@login
               ├── User (queries email)
               └── AuthService@issueTokensForUser (internal helper)
* **Flow Branching**:
  * If user not found or password check `Hash::check` fails -> Return invalid email/password (400)
  * If `email_verified_at` is null -> Return `needs_verification = true` (403)
* **Output**: Returns TokenResource and User details.

### E. Admin Login
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/admin-login`)
      ↳ AuthController@adminLogin
         ↳ Inline Validator
            ↳ AuthService@adminLogin
               ├── User (queries email and verifies is_admin = true)
               └── AuthService@issueTokensForUser (internal helper)
* **Flow Branching**:
  * If user not found or `is_admin` is false -> Return credentials invalid (400)
  * If password check fails -> Return invalid password (400)
* **Output**: Returns TokenResource and User details.

### F. Refresh Token
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/refresh-token`)
      ↳ AuthController@refreshToken
         ↳ Inline Validator
            ↳ AuthService@refreshToken
               ↳ AuthService@getPasswordClient
                  ├── oauth_clients (queries)
                  └── Makes Passport request to `/oauth/token` (grant_type: refresh_token)
* **Flow Branching**:
  * If password client not configured -> Returns `success = false`
* **Output**: Returns TokenResource JSON.

### G. Send Email Verification OTP
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/send-verification`)
      ↳ AuthController@sendVerification
         ↳ Inline Validator
            ↳ AuthService@sendVerification
               ├── EmailVerification (inserts purpose email_verification)
               └── Dispatches EmailVerificationRequested Event
                  └── SendEmailVerificationMail (listener)
                     └── VerifyEmailMail (mailable)
                        └── resources/views/emails/verify-email.blade.php (renders template)
* **Output**: Returns status success.

### H. Verify Email OTP
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/verification`)
      ↳ AuthController@verification
         ↳ Inline Validator
            ↳ AuthService@verifyEmail
               ├── EmailVerification (updates verified_at)
               ├── User (updates email_verified_at)
               └── AuthService@issueTokensForUser (internal helper)
* **Flow Branching**:
  * If `EmailVerification` not found/expired -> Returns invalid OTP (400)
* **Output**: Returns TokenResource and User details.

### I. Web Verification Link Handler
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (GET `/auth/verification-link`)
      ↳ AuthController@verificationLink
         ├── EmailVerification (queries)
         └── AuthService@verifyEmail (if email verification)
* **Flow Branching**:
  * If purpose is `password_reset` -> Verifies OTP, renders success HTML with reset code.
  * Else -> Verifies email, renders success HTML page.
* **Output**: Custom styled HTML template response page.

### J. Send Forget Password OTP
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/send-forget-password-otp`)
      ↳ AuthController@sendForgetPasswordOtp
         ↳ Inline Validator
            ↳ AuthService@sendForgetPasswordOtp
               ├── EmailVerification (inserts purpose password_reset)
               └── Dispatches PasswordResetRequested Event
                  └── SendPasswordResetMail (listener)
                     └── ResetPasswordMail (mailable)
                        └── resources/views/emails/reset-password.blade.php (renders template)
* **Output**: Returns success message.

### K. Update Password (Reset)
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/update-password`)
      ↳ AuthController@updatePassword
         ↳ Inline Validator
            ↳ AuthService@updatePassword
               ├── EmailVerification (updates verified_at)
               └── User (updates password Hash)
* **Flow Branching**:
  * If `EmailVerification` mismatch/expired -> Returns error (400)
* **Output**: Returns success message.

### L. Change Password (Authenticated)
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Auth/Routes/api.php (POST `/auth/change-password`)
      ↳ AuthController@changePassword
         ↳ Inline Validator
            ↳ AuthService@changePassword
               └── User (updates password Hash)
* **Flow Branching**:
  * If Hash checks on current password fail -> Returns mismatch error (400)
* **Output**: Returns success message.

### M. Google OAuth Flow
* **Endpoints**: 
  * `GET /auth/google` (Socialite redirect)
  * `GET /auth/google/callback` (Fetches user -> calls `AuthService@loginWithGoogleUser`)
  * `POST /auth/google/exchange` (Verifies client token -> calls `AuthService@loginWithGoogleUser`)
* **Execution Flow**:
  AuthController
   ↳ AuthService@loginWithGoogleUser
      └── User (queries by google_id or email, inserts if missing)
* **Output**: Returns access token/user details.

---

## 2. Exam Type Module

### A. Get Active Exam Types
* **Execution Flow**:
  routes/api.php
   ↳ app/Modules/ExamType/Routes/api.php (GET `/`)
      ↳ ExamTypeController@index
         ↳ ExamTypeService@getActiveExamTypes
            └── ExamType (queries is_active = true sorted by sort_order)
* **Output**: JSON collection of ExamTypes.

---

## 3. User Profile & Settings Module

### A. Get User Profile
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/User/Routes/api.php (GET `/profile`)
      ↳ UserController@profile
         ↳ UserService@getProfile
            └── User (queries with examTypes relationship)
* **Output**: JSON details of profile.

### B. Update User Profile
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/User/Routes/api.php (PUT `/profile`)
      ↳ UserController@updateProfile
         ↳ Inline Validator
            ↳ UserService@updateProfile
               ↳ UserRepository@update
                  └── User (updates columns)
* **Flow Branching**:
  * If `name` is provided -> Sanitizes it against XSS using `strip_tags($name)`.
* **Output**: JSON profile details.

### C. Set Daily Goal
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/User/Routes/api.php (PUT `/daily-goal`)
      ↳ UserController@setDailyGoal
         ↳ Inline Validator
            ↳ UserService@setDailyGoal
               ↳ UserRepository@update
                  └── User (updates daily_goal)
* **Output**: JSON returns updated daily goal value.

### D. Enroll in Exam Type
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/User/Routes/api.php (POST `/exam-types`)
      ↳ UserController@enroll
         ↳ Inline Validator
            ↳ UserService@enrollExamType
               ↳ UserRepository@enrollExamType
                  └── UserExamType (inserts pivot record)
* **Flow Branching**:
  * If user is already enrolled -> Throws `ValidationException` (prevents duplicates).
* **Output**: Return success status.

### E. Disenroll from Exam Type
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/User/Routes/api.php (DELETE `/exam-types/{examType}`)
      ↳ UserController@disenroll
         ↳ UserService@disenrollExamType
            ↳ UserRepository@disenrollExamType
               └── UserExamType (deletes row)
* **Output**: Return success status.

### F. Set Primary Exam Type
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/User/Routes/api.php (PATCH `/exam-types/{examType}/set-primary`)
      ↳ UserController@setPrimary
         ↳ UserService@setPrimaryExamType
            ↳ UserRepository@setPrimaryExamType
               └── UserExamType (sets other fields to primary=false, target to true)
* **Output**: Return success status.

---

## 4. Content & Learning Module

### A. Get Subjects by Exam Type
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Content/Routes/api.php (GET `/subjects`)
      ↳ SubjectController@index
         ↳ Inline Validator
            ↳ SubjectService@getSubjects
               └── ExamTypeSubject (queries mapping)
* **Output**: JSON collection of subjects.

### B. Get Lessons under Subject
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Content/Routes/api.php (GET `/subjects/{subject}/lessons`)
      ↳ LessonController@index
         ↳ Inline Validator
            ↳ LessonService@getLessons
               └── Lesson (queries subject/exam type)
* **Output**: JSON list of lessons.

### C. Get Questions for Lesson
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Content/Routes/api.php (GET `/lessons/{lesson}/questions`)
      ↳ QuestionController@index
         ↳ QuestionService@getQuestionsByLesson
            ↳ QuestionRepository@getByLesson
               └── Question & QuestionOption (queries)
* **Flow Branching**:
  * If user `first_session_completed` is false -> Sets `$difficulty` = `'easy'` (adaptive tutorial).
  * Else -> Queries all questions.
* **Output**: Returns JSON list of questions (answers stripped).

### D. Complete Lesson
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Content/Routes/api.php (POST `/lessons/{lesson}/complete`)
      ↳ LessonController@complete
         ↳ Inline Validator
            ↳ LessonService@completeLesson
               ├── UserLessonCompletion (inserts completion)
               └── Dispatches LessonCompleted Event
                  └── AwardCoinOnLessonComplete (listener)
                     └── UserCoin (updates) & CoinTransaction (inserts log)
* **Output**: Returns coins/XP earned.

### E. Answer Submission
* **Execution Flow**:
  routes/api.php (auth:api & EnsureUserHasHearts middleware)
   ↳ app/Modules/Content/Routes/api.php (POST `/questions/answer`)
      ↳ QuestionController@answer
         ↳ SubmitAnswerRequest
            ↳ QuestionService@processAnswer
               ├── QuestionRepository@findQuestionWithOptions
               ├── QuestionRepository@saveAnswer
               │    └── UserAnswer (inserts answer record)
               └── Dispatches QuestionAnswered Event
                    ├── AwardXpOnAnswer (listener) ──> UserXp (updates) & XpTransaction (inserts)
                    ├── DeductHeartOnWrongAnswer (listener) ──> UserHeart (decrements if wrong)
                    ├── UpdateStreakOnAnswer (listener) ──> UserStreak (increments count)
                    ├── UpdateDailyProgress (listener) ──> UserDailyProgress (updates count)
                    ├── UpdateLeagueXp (listener) ──> UserLeague (updates rank standing)
                    └── UpdateSubjectMastery (listener) ──> UserSubjectMastery (updates correctness ratio)
* **Output**: Returns correctness, correct option id, explanation, XP, heart count, streaks, and subject mastery percentage metrics.

---

## 5. Gamification Module

### A. Get Gamification Dashboard
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (GET `/gamification/dashboard`)
      ↳ XpController@dashboard
         └── status helper methods call:
            ├── UserXp (queries)
            ├── UserStreak (queries)
            ├── UserHeart (queries)
            ├── UserCoin (queries)
            └── UserDailyProgress (queries)
* **Output**: JSON gamification statistics.

### B. Streak Status
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (GET `/gamification/streak`)
      ↳ StreakController@show
         ↳ StreakService@getStreak
            └── UserStreak (queries)
* **Output**: JSON streak data.

### C. Spend Streak Freeze
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (POST `/gamification/streak/freeze`)
      ↳ StreakController@spendFreeze
         ↳ StreakService@spendFreeze
            └── UserStreak (decrements freeze_count)
* **Flow Branching**:
  * If freeze count is 0 -> Throws Exception.
* **Output**: Success status response.

### D. Hearts Status
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (GET `/gamification/hearts`)
      ↳ HeartController@show
         ↳ HeartService@getHearts
            └── UserHeart (queries)
* **Output**: JSON hearts count.

### E. Refill Hearts
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (POST `/gamification/hearts/refill`)
      ↳ HeartController@refill
         ↳ HeartService@refill
            ├── UserCoin (decrements 100)
            ├── UserHeart (resets to 5)
            └── CoinTransaction (inserts)
* **Flow Branching**:
  * If user has < 100 coins -> Throws exception.
* **Output**: Success status response.

### F. Coins Balance
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (GET `/gamification/coins`)
      ↳ CoinController@show
         └── UserCoin (queries)
* **Output**: JSON coins balance.

### G. Spend Coins
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Gamification/Routes/api.php (POST `/gamification/coins/spend`)
      ↳ CoinController@spend
         ↳ Inline Validator
            ↳ CoinService@spend
               ├── UserCoin (decrements amount)
               └── CoinTransaction (inserts)
* **Flow Branching**:
  * If user coins balance < amount -> Throws exception.
* **Output**: Returns updated balance details.

### H. System Achievements & Earned Achievements
* **Endpoints**:
  * `GET /achievements` (Queries all `Achievement` records)
  * `GET /achievements/earned` (Queries `UserAchievement` mappings)
* **Execution Flow**:
  XpController
   ├── Achievement (queries)
   └── UserAchievement (queries)
* **Output**: JSON array of achievements list.

### I. Progress Stats
* **Endpoints**:
  * `GET /progress/subjects` (Queries `UserSubjectMastery`)
  * `GET /progress/daily` (Queries `UserDailyProgress`)
  * `GET /progress/personal-best` (Finds max correct in `UserDailyProgress`)
* **Execution Flow**:
  XpController
   ├── UserSubjectMastery (queries)
   └── UserDailyProgress (queries)
* **Output**: JSON progress analytics.

---

## 6. League & Standings Module

### A. Get Current Standings
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/League/Routes/api.php (GET `/current`)
      ↳ LeagueController@current
         ↳ Inline Validator
            ↳ LeagueService@getCurrentStandings
               ├── LeagueSeason (queries active season)
               └── UserLeague & User (queries and sorts standings)
* **Output**: Paginated rankings array.

### B. Standings History
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/League/Routes/api.php (GET `/history`)
      ↳ LeagueController@history
         ↳ Inline Validator
            └── UserLeague (queries closed season entries)
* **Output**: Historical ranks list.

---

## 7. Exam & Model Test Module

### A. Get Active Model Tests & Countdown
* **Endpoints**:
  * `GET /model-tests` (Queries `ModelTest` configurations)
  * `GET /exam-countdown` (Queries `ExamSchedule` deadlines)
* **Execution Flow**:
  ModelTestController
   ↳ ExamService@getActiveModelTests / getExamCountdown
      ├── ModelTest (queries active tests)
      └── ExamSchedule (queries closest deadline)
* **Output**: JSON lists or countdown seconds.

### B. Start Model Test Session
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Exam/Routes/api.php (POST `/model-tests/{test}/start`)
      ↳ ExamSessionController@start
         ↳ ExamService@startExamSession
            ↳ ExamRepository@findModelTest -> createExamSession
               └── ExamSession (inserts record with status in_progress)
* **Output**: Session ID and exam question list (stripped answers).

### C. Submit Model Test Answers
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Exam/Routes/api.php (POST `/exam-sessions/{session}/submit`)
      ↳ ExamSessionController@submit
         ↳ Inline Validator
            ↳ ExamService@submitExamSession
               ↳ ExamRepository@findExamSession -> saveExamSessionResult
                  ├── ExamSession (updates status to completed, saves score)
                  ├── UserAnswer (inserts answers)
                  └── XpService@awardXp
                     └── UserXp (updates) & XpTransaction (inserts)
* **Flow Branching**:
  * Validates session ownership. Checks if session is already completed.
  * Loops answers: verifies correctness of each question and selected option.
* **Output**: Session score, answers correctness counts, and finished timestamps.

### D. Exam Countdown
* **Execution Flow**:
  routes/api.php (auth:api middleware)
   ↳ app/Modules/Exam/Routes/api.php (GET `/exam-countdown`)
      ↳ ModelTestController@countdown
         ↳ Inline Validator
            ↳ ExamService@getExamCountdown
               ↳ ExamRepository@getCountdown
                  └── ExamSchedule (queries upcoming schedule)
* **Output**: Countdown seconds and scheduling info.

---

## 8. Admin Management Module (Requires admin middleware)

### A. Dashboard Stats
* **Execution Flow**:
  routes/api.php (auth:api & AdminOnly middleware)
   ↳ app/Modules/Admin/Routes/api.php (GET `/dashboard/stats`)
      ↳ DashboardController@stats
         ├── User (aggregates count)
         ├── Question (aggregates count)
         └── ExamSession (aggregates count)
* **Output**: Counts summary details.

### B. Exam Types Admin CRUD
* **Endpoints**:
  * `GET /admin/exam-types` (Queries all `ExamType` rows)
  * `POST /admin/exam-types` (Inserts new `ExamType`)
  * `PUT /admin/exam-types/{examType}` (Updates `ExamType`)
* **Execution Flow**:
  ExamTypeManagementController
   └── ExamType (queries or updates table)

### C. Subject Assignment mappings
* **Endpoints**:
  * `GET /admin/exam-types/{examType}/subjects` (Queries mappings)
  * `POST /admin/exam-types/assign-subject` (Inserts pivot mappings)
  * `DELETE /admin/exam-types/{examType}/subjects/{subject}` (Deletes pivot mappings)
* **Execution Flow**:
  ExamTypeManagementController
   ├── ExamTypeSubject (queries)
   └── ExamTypeSubjectPivot (inserts/deletes mappings)

### D. Global Subjects CRUD
* **Endpoints**:
  * `GET /admin/subjects` (Queries & searches subjects)
  * `GET /admin/subjects/all` (Queries flat list of active subjects)
  * `POST /admin/subjects` (Inserts subject; uses `StoreSubjectRequest`)
  * `PUT /admin/subjects/{subject}` (Updates subject; uses `UpdateSubjectRequest`)
  * `DELETE /admin/subjects/{subject}` (Deletes subject)
* **Execution Flow**:
  SubjectManagementController
   └── Subject (queries, updates, or deletes record)
* **Flow Branching**:
  * Delete blocks if pivot mappings exist.

### E. Exam Type Subjects CRUD (Scoped mappings)
* **Endpoints**:
  * `GET /admin/exam-type-subjects` (Queries rows)
  * `GET /admin/exam-type-subjects/for-exam-type/{examType}` (Queries list)
  * `POST /admin/exam-type-subjects` (Inserts row; uses `StoreExamTypeSubjectRequest`)
  * `PUT /admin/exam-type-subjects/{examTypeSubject}` (Updates row; uses `UpdateExamTypeSubjectRequest`)
  * `DELETE /admin/exam-type-subjects/{examTypeSubject}` (Deletes row)
* **Execution Flow**:
  ExamTypeSubjectManagementController
   └── ExamTypeSubject (queries, updates, or deletes mappings)
* **Flow Branching**:
  * Delete blocks if questions map to the scoped subject.

### F. Questions CRUD
* **Endpoints**:
  * `GET /admin/questions` (Queries paginated questions)
  * `POST /admin/questions` (Inserts question and options)
  * `PUT /admin/questions/{question}` (Updates question and options)
  * `DELETE /admin/questions/{question}` (Deletes question and options)
* **Execution Flow**:
  ContentManagementController
   ├── Question (queries, updates, deletes)
   └── QuestionOption (queries, updates, deletes)

### G. Bulk Import Questions
* **Execution Flow**:
  routes/api.php (auth:api & AdminOnly middleware)
   ↳ app/Modules/Admin/Routes/api.php (POST `/questions/import`)
      ↳ ContentManagementController@import
         ↳ Inline Validator
            ├── ExamTypeSubject (queries & validates title)
            ├── Question (bulk inserts)
            └── QuestionOption (bulk inserts options)
* **Flow Branching**:
  * Matches subject name case-insensitive.
  * If subject matches -> Inserts question and options, sets correct option reference.
  * Else -> Skips row, adds to skip notices array.
* **Output**: Returns imported rows count, skipped notices array.

### H. Lessons CRUD
* **Endpoints**:
  * `GET /admin/lessons` (Queries paginated lessons)
  * `POST /admin/lessons` (Inserts lesson row)
  * `PUT /admin/lessons/{lesson}` (Updates lesson row)
* **Execution Flow**:
  ContentManagementController
   └── Lesson (queries, updates, or deletes record)

### I. User Management CRUD
* **Endpoints**:
  * `GET /admin/users` (Queries accounts)
  * `GET /admin/users/{user}` (Aggregates scores/streaks)
  * `PATCH /admin/users/{user}/toggle-active` (Toggles is_active)
* **Execution Flow**:
  UserManagementController
   └── User (queries or updates table)

### J. Schedules CRUD
* **Endpoints**:
  * `GET /admin/exam-schedules` (Queries schedules)
  * `POST /admin/exam-schedules` (Inserts schedules)
* **Execution Flow**:
  ExamTypeManagementController
   └── ExamSchedule (queries or updates table)

### K. Admin Analytics
* **Endpoints**:
  * `GET /admin/analytics/retention` (Groups user activity sessions)
  * `GET /admin/analytics/daily-actives` (Aggregates daily active users)
  * `GET /admin/analytics/subject-performance` (Groups correctness from answers)
* **Execution Flow**:
  DashboardController
   ├── User (queries logins)
   ├── ExamSession (queries session counts)
   └── UserAnswer (queries and groups correctness)
