# Quizlo Backend - Changes Summary

We have fully implemented, refactored, and tested the Quizlo backend. Below is a detailed record of the modifications, bug fixes, and feature additions applied to the main codebase.

---

## 1. Database & Schema Enhancements
- **Admin Column Creation**: Modified the user creation migration ([0001_01_01_000000_create_users_table.php](file:///Users/sajid/Documents/Quizlo/database/migrations/0001_01_01_000000_create_users_table.php)) to add the `is_admin` column:
  ```php
  $table->boolean('is_admin')->default(false);
  ```
- **User Casts**: Updated [User.php](file:///Users/sajid/Documents/Quizlo/app/Models/User.php) to cast `is_admin` to `boolean` properly.

---

## 2. Service Layer Refinement

### Profile & Security Sanitization
- **XSS Input Sanitization**: Added automatic tag stripping inside [UserService::updateProfile](file:///Users/sajid/Documents/Quizlo/app/Modules/User/Services/UserService.php) to protect user name changes against XSS scripts:
  ```php
  if (isset($data['name'])) {
      $data['name'] = strip_tags($data['name']);
  }
  ```

### Exam Enrollments
- **Duplicate Prevention**: Modified `UserService::enrollExamType` to check if a user is already enrolled in the target exam type before inserting, throwing a `ValidationException` if a duplicate enrollment is attempted.

### Gamification & Streak Processing
- **Carbon Object Comparisons**: Fixed date evaluation in [StreakService::processUserActivity](file:///Users/sajid/Documents/Quizlo/app/Modules/Gamification/Services/StreakService.php). Previously, the code compared the `last_activity_date` (which is cast to a `Carbon` object) directly with string formatted dates (e.g. `'Y-m-d'`), which always failed strict comparison (`===`). We resolved this by extracting and comparing date strings:
  ```php
  $lastDateStr = $streak->last_activity_date?->format('Y-m-d');
  ```
- **Strict Mismatch Resolution**: Cast `daysMissed` in `canUseFreeze()` to an integer to ensure that comparisons like `(int) $daysMissed === 2` evaluate correctly (preventing float vs. integer comparison failures).

---

## 3. Router Mapping Fixes
- **User Prefixing**: Corrected the route registration in [routes/api.php](file:///Users/sajid/Documents/Quizlo/routes/api.php). The user group registration was missing the route prefix, resulting in `/api/v1/exam-types` instead of `/api/v1/user/exam-types`. We updated it to:
  ```php
  Route::prefix('user')->group(base_path('app/Modules/User/Routes/api.php'));
  ```

---

## 4. Testing Framework Alignment

### Test Auto-Discovery
- **Method Renaming**: Prepend `test_` to unit and security test methods that were previously named starting with `it_` (e.g., `it_deducts_heart_on_wrong_answer` -> `test_it_deducts_heart_on_wrong_answer`), allowing PHPUnit 12 to detect and run all 43 tests.

### Factory Trait Implementations
- Enabled `HasFactory` across all database models that had corresponding testing factories:
  - `LeagueSeason`, `UserLeague`, `Achievement`, `ExamSession`, `ModelTest`, `UserAnswer`, `UserDailyProgress`, and `UserSubjectMastery`.

### Unit Test Execution Adjustments
- **Eloquent Collections**: Updated mock returns in `LessonServiceTest` and `QuestionServiceTest` to return `Illuminate\Database\Eloquent\Collection` instead of generic `Illuminate\Support\Collection` to adhere to exact method signatures.
- **Mass Assignment Guard Bypass**: Handled mass-assignment protection bypass in unit tests (e.g., assigning custom IDs to new models) by replacing `new Model(['id' => X])` with `$model->forceFill(['id' => X])`.

---

## 5. Verification Results
Running `php artisan test` now successfully discovers and runs the entire suite:

```json
{"tool":"phpunit","result":"passed","tests":43,"passed":43,"assertions":113,"duration_ms":820}
```
All **43 tests** executed and passed successfully.
