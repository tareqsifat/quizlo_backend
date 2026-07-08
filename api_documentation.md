# Quizlo Backend API Documentation

Welcome to the Quizlo Backend API Documentation. This document describes all available API endpoints, authentication requirements, input parameters, validation rules, and sample JSON responses.

---

## Global API Configuration & Headers

- **Base URL**: `/api/v1`
- **Content-Type**: `application/json`
- **Accept**: `application/json`
- **Authorization**: `Bearer <access_token>` (Required for all authenticated endpoints)

---

## Table of Contents
1. [Authentication Module](#1-authentication-module)
2. [Exam Type Module](#2-exam-type-module)
3. [User Profile & Settings Module](#3-user-profile--settings-module)
4. [Content & Learning Module (Subjects, Lessons, Questions)](#4-content--learning-module-subjects-lessons-questions)
5. [Gamification Module (XP, Hearts, Coins, Streaks, Achievements)](#5-gamification-module-xp-hearts-coins-streaks-achievements)
6. [League & Standings Module](#6-league--standings-module)
7. [Exam & Model Test Module](#7-exam--model-test-module)
8. [Admin Management Module](#8-admin-management-module)

---

## 1. Authentication Module

### Send OTP
Generates and sends a 6-digit phone number verification code.
- **Endpoint**: `POST /api/v1/auth/send-otp`
- **Authentication**: None (Guest)
- **Rate Limit**: 5 requests per minute
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `phone` | String | Required, valid Bangladeshi number format (`/^(?:\+88\|88)?(01[3-9]\d{8})$/`) | The recipient mobile number. |
  | `purpose` | String | Optional, default: `login`. Must be `login` or `register`. | The context for OTP request. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "OTP sent successfully.",
    "data": {
      "otp_code": "482015"
    }
  }
  ```

### Verify OTP
Verifies the 6-digit code and logs the user in, returning access and refresh tokens.
- **Endpoint**: `POST /api/v1/auth/verify-otp`
- **Authentication**: None (Guest)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `phone` | String | Required, valid Bangladeshi mobile number | Registered phone number. |
  | `otp_code` | String | Required, exactly 6 characters | Received code. |
  | `purpose` | String | Required. Must be `login` or `register`. | Purpose of verification. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "OTP verified successfully.",
    "data": {
      "token": {
        "token_type": "Bearer",
        "expires_in": 31536000,
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIs...",
        "refresh_token": "def502008e33e9d8..."
      },
      "user": {
        "id": 1,
        "name": "Tareq Sifat",
        "phone": "+8801712345678"
      }
    }
  }
  ```

### Register User
Registers a user using name, email, and password. *Note: Upon registration, users are logged in immediately and issued tokens.*
- **Endpoint**: `POST /api/v1/auth/register`
- **Authentication**: None (Guest)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | String | Required, max:100 | The full name of the user. |
  | `email` | String | Required, valid email format, max:150, unique:users | Email address. |
  | `password` | String | Required, min:6, must match confirmation | Registration password. |
  | `password_confirmation` | String | Required, same as `password` | Re-entered password. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "User registered successfully.",
    "data": {
      "token": {
        "token_type": "Bearer",
        "expires_in": 31536000,
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIs...",
        "refresh_token": "def502008e33e9d8..."
      },
      "user": {
        "id": 15,
        "name": "John Doe",
        "email": "johndoe@example.com",
        "phone": null
      }
    }
  }
  ```

### Email Login
Logs a user in using email and password.
- **Endpoint**: `POST /api/v1/auth/login`
- **Authentication**: None (Guest)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `email` | String | Required, valid email | User email. |
  | `password` | String | Required, string | User password. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Logged in successfully.",
    "data": {
      "token": {
        "token_type": "Bearer",
        "expires_in": 31536000,
        "access_token": "eyJ0eX...",
        "refresh_token": "def50..."
      },
      "user": {
        "id": 15,
        "name": "John Doe",
        "email": "johndoe@example.com",
        "phone": null
      }
    }
  }
  ```

### Admin Login
Logs in administrative users.
- **Endpoint**: `POST /api/v1/auth/admin-login`
- **Authentication**: None (Guest)
- **Request Body**: Same as Email Login.
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Admin logged in successfully.",
    "data": {
      "token": { ... },
      "user": {
        "id": 2,
        "name": "Admin Manager",
        "email": "admin@quizlo.com",
        "phone": null
      }
    }
  }
  ```

### Refresh Token
Obtains a new Access Token using a valid Refresh Token.
- **Endpoint**: `POST /api/v1/auth/refresh-token`
- **Authentication**: None (Guest)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `refresh_token` | String | Required | The refresh token received at authentication. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Token refreshed successfully.",
    "data": {
      "token_type": "Bearer",
      "expires_in": 31536000,
      "access_token": "eyJ0eX...",
      "refresh_token": "def50..."
    }
  }
  ```

### Send Email Verification Link/OTP
Triggers a verification code to the user's email.
- **Endpoint**: `POST /api/v1/auth/send-verification`
- **Authentication**: None (Guest)
- **Rate Limit**: 5 requests per minute
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `email` | String | Required, email format, exists:users | User email. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Verification code sent to your email."
  }
  ```

### Verify Email
Verifies email address using an OTP.
- **Endpoint**: `POST /api/v1/auth/verification`
- **Authentication**: None (Guest)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `email` | String | Required, email format | User email. |
  | `otp` | String | Required, exactly 6 characters | OTP code. |
- **Sample Response**: Same structure as **Verify OTP**.

### Email Verification Page Link (Web Endpoint)
An HTML-based endpoint visited via verification email links. Returns custom HTML.
- **Endpoint**: `GET /api/v1/auth/verification-link`
- **Authentication**: None (Guest)
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `email` | String | Required | User email. |
  | `otp` | String | Required | OTP code. |
  | `purpose` | String | Optional (default: `email_verification`). Must be `email_verification` or `password_reset`. | Link purpose. |

### Send Forget Password OTP
Sends a password reset token.
- **Endpoint**: `POST /api/v1/auth/send-forget-password-otp`
- **Authentication**: None (Guest)
- **Rate Limit**: 5 requests per minute
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `email` | String | Required, email format | Registered email. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Password reset code sent to your email."
  }
  ```

### Reset Forgotten Password
Updates password using password reset OTP.
- **Endpoint**: `POST /api/v1/auth/update-password`
- **Authentication**: None (Guest)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `email` | String | Required, email | User email. |
  | `otp` | String | Required, exactly 6 characters | Received code. |
  | `password` | String | Required, min:6, confirmed | New password. |
  | `password_confirmation` | String | Required | Confirmation password. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Password reset successfully. You can now login with your new password."
  }
  ```

### Authenticated Change Password
Enables password change for logged-in users.
- **Endpoint**: `POST /api/v1/auth/change-password`
- **Authentication**: Required (`auth:api`)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `current_password` | String | Required | Current password. |
  | `password` | String | Required, min:6, confirmed | New password. |
  | `password_confirmation` | String | Required | Confirmation password. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Password changed successfully."
  }
  ```

### Google Sign-In & Callback (GoogleAuthController)
Integrations for Google Social Authentication.
- **Redirect Endpoint**: `GET /api/v1/auth/google` (Redirects user to Google OAuth Web Flow)
- **Callback Endpoint**: `GET /api/v1/auth/google/callback` (Processes web callback and outputs auth JSON)
- **Token Exchange**: `POST /api/v1/auth/google/exchange`
  - **Request Body**:
    | Field | Type | Rules | Description |
    | :--- | :--- | :--- | :--- |
    | `access_token` | String | Required | Google client/device authentication token. |
  - **Sample Response**: Returns Passport `token` object and `user` profile details.

---

## 2. Exam Type Module

### Get Active Exam Types
Fetches all configured exam categories (e.g. BCS, Primary Teacher, etc.).
- **Endpoint**: `GET /api/v1/exam-types`
- **Authentication**: None (Guest)
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Active exam types retrieved successfully.",
    "data": [
      {
        "id": 1,
        "name": "BCS Preliminary",
        "name_bn": "বিসিএস প্রিলিমিনারি",
        "code": "BCS_PRELIM",
        "slug": "bcs-preliminary",
        "description": "Comprehensive BCS preliminary preparation syllabus.",
        "icon": "bcs_vector.svg",
        "sort_order": 1
      }
    ]
  }
  ```

---

## 3. User Profile & Settings Module

### Get User Profile
Retrieves detailed profile data and enrollments.
- **Endpoint**: `GET /api/v1/user/profile`
- **Authentication**: Required (`auth:api`)
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "User profile retrieved successfully.",
    "data": {
      "id": 1,
      "name": "Tareq Sifat",
      "phone": "+8801712345678",
      "email": "tareq@example.com",
      "avatar": "https://storage.quizlo.com/avatars/user-1.png",
      "district": "Dhaka",
      "division": "Dhaka",
      "daily_goal": 20,
      "first_session_completed": true,
      "is_active": true,
      "exam_types": [
        {
          "id": 1,
          "name": "BCS Preliminary",
          "name_bn": "বিসিএস প্রিলিমিনারি",
          "code": "BCS_PRELIM",
          "slug": "bcs-preliminary",
          "is_primary": true,
          "target_year": 2026
        }
      ]
    }
  }
  ```

### Update User Profile
Modifies user configuration.
- **Endpoint**: `PUT /api/v1/user/profile`
- **Authentication**: Required (`auth:api`)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | String | Optional, max:100 | Full name (will be stripped of tags). |
  | `email` | String | Optional, email format, unique in users table | Email (excluding current user's). |
  | `avatar` | String | Optional, max:255 | Image URL. |
  | `district` | String | Optional, max:100 | District name. |
  | `division` | String | Optional, max:100 | Division name. |
- **Sample Response**: Same JSON layout as **Get User Profile** with updated details.

### Set Daily Goal
Updates target question quota.
- **Endpoint**: `PUT /api/v1/user/daily-goal`
- **Authentication**: Required (`auth:api`)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `daily_goal` | Integer | Required, must be `10`, `20`, or `30` | Target daily questions count. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Daily goal updated successfully.",
    "data": {
      "daily_goal": 20
    }
  }
  ```

### Enroll in Exam Type
Maps the user to an exam target structure.
- **Endpoint**: `POST /api/v1/user/exam-types`
- **Authentication**: Required (`auth:api`)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | The target exam. |
  | `is_primary` | Boolean | Optional (default: `false`) | Set as primary exam interface. |
  | `target_year` | Integer | Optional, between `2024` and `2035` | Intended completion/exam year. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Enrolled in exam type successfully.",
    "data": null
  }
  ```

### Disenroll from Exam Type
Unenrolls the user.
- **Endpoint**: `DELETE /api/v1/user/exam-types/{examType}`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `examType` (Integer) - ID of the exam type.
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Disenrolled from exam type successfully.",
    "data": null
  }
  ```

### Set Primary Exam Type
Updates the primary category dashboard.
- **Endpoint**: `PATCH /api/v1/user/exam-types/{examType}/set-primary`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `examType` (Integer) - ID of the exam type to mark primary.
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Primary exam type updated.",
    "data": null
  }
  ```

---

## 4. Content & Learning Module (Subjects, Lessons, Questions)

### Get Subjects by Exam Type
Lists subjects (mappings) belonging to a specific Exam Type.
- **Endpoint**: `GET /api/v1/subjects`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Filter subjects by this exam type. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Subjects retrieved successfully.",
    "data": [
      {
        "id": 1,
        "name": "Bangla",
        "name_bn": "বাংলা",
        "slug": "bangla",
        "icon": "icon_bangla.png",
        "color_hex": "#EC4899",
        "total_marks": 35,
        "syllabus_note": "Language & Literature segments",
        "sort_order": 1
      }
    ]
  }
  ```

### Get Lessons under Subject
Lists topic lessons scoped by subject and exam configuration.
- **Endpoint**: `GET /api/v1/subjects/{subject}/lessons`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `subject` (Integer) - The global subject ID.
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Scoping category. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Lessons retrieved successfully.",
    "data": [
      {
        "id": 3,
        "exam_type_id": 1,
        "subject_id": 1,
        "topic_id": null,
        "title": "Nouns and Classification",
        "title_bn": "বিশেষ্য ও এর শ্রেণিবিভাগ",
        "description": "Learn the basics of nouns in Bangla grammar.",
        "xp_reward": 50,
        "coin_reward": 5,
        "difficulty": "easy",
        "question_count": 10,
        "sort_order": 1
      }
    ]
  }
  ```

### Get Questions for Lesson
Retrieves all questions mapped to a specific lesson.
- **Endpoint**: `GET /api/v1/lessons/{lesson}/questions`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `lesson` (Integer) - Lesson ID.
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Questions retrieved successfully.",
    "data": [
      {
        "id": 12,
        "subject_id": 1,
        "topic_id": null,
        "lesson_id": 3,
        "question_text": "Which of the following is a collective noun?",
        "question_bn": "নিচের কোনটি সমষ্টিবাচক বিশেষ্য?",
        "explanation": "‘বহর’ represents a group, making it collective.",
        "difficulty": "medium",
        "xp_value": 10,
        "options": [
          { "id": 45, "option_text": "Flock", "option_text_bn": "বহর", "sort_order": 0 },
          { "id": 46, "option_text": "Water", "option_text_bn": "পানি", "sort_order": 1 }
        ]
      }
    ]
  }
  ```

### Complete Lesson
Marks a lesson complete and logs the score.
- **Endpoint**: `POST /api/v1/lessons/{lesson}/complete`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `lesson` (Integer) - Lesson ID.
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `score` | Integer | Required, min:0, max:100 | The percentage score achieved. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Lesson completed successfully.",
    "data": {
      "xp_earned": 50,
      "coins_earned": 5,
      "score": 90,
      "is_first_completion": true
    }
  }
  ```

### Answer Submission
Processes a single question attempt. Updates XP, hearts, and streaks in real-time.
- **Endpoint**: `POST /api/v1/questions/answer`
- **Authentication**: Required (`auth:api`)
- **Middlewares**: `hearts` (Ensures the user has active hearts to play)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Core category context. |
  | `question_id` | Integer | Required, exists:questions | Question ID. |
  | `selected_option_id` | Integer | Required, exists:question_options | Attempted choice ID. |
  | `session_id` | Integer | Optional, exists:exam_sessions | Exam session relation (if during a model test). |
  | `session_type` | String | Required. Must be `lesson`, `model_test`, `practice`, or `exam_mode` | Mode of delivery. |
  | `time_taken_ms` | Integer | Optional, min:0, max:300000 | Submission latency. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Answer processed.",
    "data": {
      "is_correct": true,
      "correct_option_id": 45,
      "xp_earned": 10,
      "coins_earned": 1,
      "hearts_status": {
        "hearts": 5,
        "max_hearts": 5,
        "infinite_until": null
      }
    }
  }
  ```

---

## 5. Gamification Module (XP, Hearts, Coins, Streaks, Achievements)

### Get Gamification Dashboard
Calculates and bundles all gamification indicators.
- **Endpoint**: `GET /api/v1/gamification/dashboard`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Optional, exists:exam_types | Scoping category to fetch user's daily goals. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": {
      "xp": 1540,
      "level": 4,
      "streak": {
        "current_streak": 8,
        "last_activity_date": "2026-07-08",
        "freeze_count": 2
      },
      "hearts": {
        "hearts": 5,
        "max_hearts": 5,
        "infinite_until": null
      },
      "coins": 230,
      "daily_progress": {
        "goal_questions": 20,
        "answered_questions": 15,
        "correct_questions": 12,
        "xp_earned_today": 120,
        "goal_met": false
      }
    }
  }
  ```

### Get Streak Status
Retrieves current streak activity statistics.
- **Endpoint**: `GET /api/v1/gamification/streak`
- **Authentication**: Required (`auth:api`)
- **Sample Response**: Returns the `streak` node from the Gamification Dashboard.

### Spend Streak Freeze
Uses a streak freeze item to preserve active daily streak.
- **Endpoint**: `POST /api/v1/gamification/streak/freeze`
- **Authentication**: Required (`auth:api`)
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Streak freeze applied."
  }
  ```

### Get Hearts Status
Retrieves active heart count.
- **Endpoint**: `GET /api/v1/gamification/hearts`
- **Authentication**: Required (`auth:api`)
- **Sample Response**: Returns the `hearts` node from the Gamification Dashboard.

### Refill Hearts
Refills hearts by spending coins.
- **Endpoint**: `POST /api/v1/gamification/hearts/refill`
- **Authentication**: Required (`auth:api`)
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Hearts refilled successfully.",
    "data": {
      "hearts": 5,
      "coins_spent": 100
    }
  }
  ```

### Get Coins Balance
- **Endpoint**: `GET /api/v1/gamification/coins`
- **Authentication**: Required (`auth:api`)
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": {
      "balance": 230
    }
  }
  ```

### Spend Coins
Purchases items or power-ups.
- **Endpoint**: `POST /api/v1/gamification/coins/spend`
- **Authentication**: Required (`auth:api`)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `amount` | Integer | Required, min:1 | Cost in coins. |
  | `reason` | String | Required | Description/key for transaction. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Coins spent successfully.",
    "data": {
      "new_balance": 130,
      "amount_spent": 100
    }
  }
  ```

### Get All System Achievements
Lists all available badges.
- **Endpoint**: `GET /api/v1/achievements`
- **Authentication**: Required (`auth:api`)
- **Sample Response**: Lists array of global achievements containing titles, descriptions, requirements, and icons.

### Get User Earned Achievements
Lists badges earned by the user.
- **Endpoint**: `GET /api/v1/achievements/earned`
- **Authentication**: Required (`auth:api`)

### Get Subject Mastery Progress
Gets subject-wise mastery stats.
- **Endpoint**: `GET /api/v1/progress/subjects`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Category filter. |

### Get Daily Goal Progress
Retrieves today's current milestone details.
- **Endpoint**: `GET /api/v1/progress/daily`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**: Same as above.

### Get Personal Best Stats
Retrieves the record for most correct questions submitted in a single day.
- **Endpoint**: `GET /api/v1/progress/personal-best`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**: Same as above.
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": {
      "max_correct_in_one_day": 45,
      "date": "2026-07-04"
    }
  }
  ```

---

## 6. League & Standings Module

### Get Current Standings
Lists standings for the weekly division.
- **Endpoint**: `GET /api/v1/league/current`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Scopes division ranking. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": [
      { "user_id": 15, "name": "John Doe", "xp": 1420, "rank": 1 },
      { "user_id": 1, "name": "Tareq Sifat", "xp": 1200, "rank": 2 }
    ]
  }
  ```

### Get User League History
Retrieves historical division milestones.
- **Endpoint**: `GET /api/v1/league/history`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**: Same as above.

---

## 7. Exam & Model Test Module

### Get Active Model Tests
Retrieves scheduled mock exams.
- **Endpoint**: `GET /api/v1/model-tests`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**:
  | Parameter | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Category scoping. |

### Start Model Test Session
Initializes a full mock exam session.
- **Endpoint**: `POST /api/v1/model-tests/{test}/start`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `test` (Integer) - ID of the model test.
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": {
      "session_id": 48,
      "model_test_id": 5,
      "title": "BCS Mock Test 1",
      "duration_minutes": 120,
      "questions": [
        { "id": 105, "question_text": "Sample Question...", "options": [...] }
      ]
    }
  }
  ```

### Submit Model Test Answers
Submits responses to calculate overall score.
- **Endpoint**: `POST /api/v1/exam-sessions/{session}/submit`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**:
  - `session` (Integer) - Session ID.
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `answers` | Array | Required | List of answers. |
  | `answers.*.question_id` | Integer | Required, exists:questions | Question ID. |
  | `answers.*.selected_option_id` | Integer | Required, exists:question_options | Chosen Option ID. |
  | `answers.*.time_taken_ms` | Integer | Optional | Time spent on question. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": {
      "session_id": 48,
      "status": "completed",
      "score": 75.0,
      "correct_answers": 15,
      "wrong_answers": 5,
      "completed_at": "2026-07-08 16:30:00"
    }
  }
  ```

### Get Exam Session Result
Retrieves detailed analysis of a completed exam session.
- **Endpoint**: `GET /api/v1/exam-sessions/{session}/result`
- **Authentication**: Required (`auth:api`)
- **Path Parameters**: Same as above.

### Get Next Exam Countdown
Fetches schedule countdown for the closest upcoming exam.
- **Endpoint**: `GET /api/v1/exam-countdown`
- **Authentication**: Required (`auth:api`)
- **Query Parameters**: Same as above.

---

## 8. Admin Management Module

*Note: All endpoints under this module require administrative access (`auth:api` and `admin` middleware).*

### Admin Dashboard Stats
- **Endpoint**: `GET /api/v1/admin/dashboard/stats`
- **Sample Response**:
  ```json
  {
    "success": true,
    "data": {
      "total_users": 1540,
      "total_questions": 12000,
      "total_sessions": 8500,
      "completed_sessions": 7900
    }
  }
  ```

### List Exam Types (Admin)
Lists all exam types including inactive ones.
- **Endpoint**: `GET /api/v1/admin/exam-types`

### Store Exam Type
Creates a category.
- **Endpoint**: `POST /api/v1/admin/exam-types`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | String | Required, max:100 | Name (En). |
  | `name_bn` | String | Required, max:100 | Name (Bn). |
  | `code` | String | Required, unique:exam_types | Core code string. |
  | `slug` | String | Required, unique:exam_types | URL slug. |
  | `description`| String | Optional | Description. |
  | `icon` | String | Optional, max:100 | Icon class or vector name. |
  | `is_active` | Boolean| Optional (default: `true`) | Status toggler. |
  | `sort_order` | Integer| Optional | Ordering digit. |

### Update Exam Type
- **Endpoint**: `PUT /api/v1/admin/exam-types/{examType}`
- **Request Body**: Same fields as POST, all optional.

### Get Exam Type Assigned Subjects
- **Endpoint**: `GET /api/v1/admin/exam-types/{examType}/subjects`

### Assign Subject to Exam Type
Maps global subject resources under an exam.
- **Endpoint**: `POST /api/v1/admin/exam-types/assign-subject` (Also supports: `POST /api/v1/admin/exam-types/{examType}/subjects`)
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Optional/Required (unless path contains `{examType}`) | Target exam. |
  | `subject_id` | Integer | Required, exists:subjects | Subject ID. |
  | `is_active` | Boolean | Optional (default: `true`) | Active status. |
  | `sort_order` | Integer | Optional (default: `0`) | Sorting order. |
  | `total_marks` | Integer | Optional | Marks allocated for this subject. |
  | `syllabus_note`| String | Optional | Syllabus highlights. |

### Remove Subject from Exam Type
- **Endpoint**: `DELETE /api/v1/admin/exam-types/{examType}/subjects/{subject}`

### List Subjects (Admin)
Lists global subjects.
- **Endpoint**: `GET /api/v1/admin/subjects`
- **Query Parameters**:
  - `search` (String) - Matches `name` or `name_bn` fields.
  - `is_active` (Boolean) - Filter by status.
- **Sample Response**: Array of subjects.

### Get All Active Subjects (Flat List)
Lite list for admin dropdown selectors.
- **Endpoint**: `GET /api/v1/admin/subjects/all`

### Store Subject
Creates a global subject resource.
- **Endpoint**: `POST /api/v1/admin/subjects`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | String | Required, max:150 | Subject name. |
  | `name_bn` | String | Required, max:150 | Bengali name. |
  | `slug` | String | Required, max:150, unique:subjects | URL slug. |
  | `icon` | String | Optional, max:50 | Icon identifier. |
  | `color_hex` | String | Optional, hex color code `/^#[0-9A-Fa-f]{6}$/` | Hex color code. |
  | `is_active` | Boolean | Optional | Active toggler. |

### Update Subject
- **Endpoint**: `PUT /api/v1/admin/subjects/{subject}`

### Delete Subject
Deletes subject if not mapped to active exam type subjects.
- **Endpoint**: `DELETE /api/v1/admin/subjects/{subject}`

### List Exam Type Subjects (Admin)
- **Endpoint**: `GET /api/v1/admin/exam-type-subjects`
- **Query Parameters**: `exam_type_id`, `search`.

### Get Active Exam Type Subjects
Dropdown helper for bulk question imports.
- **Endpoint**: `GET /api/v1/admin/exam-type-subjects/for-exam-type/{examType}`

### Store Exam Type Subject
Creates a scoped sub-subject under an exam type.
- **Endpoint**: `POST /api/v1/admin/exam-type-subjects`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Category relation. |
  | `subject_id` | Integer | Required, exists:subjects | Global subject. |
  | `title` | String | Required, max:150 | Mapping Title (English). Unique under selected Exam Type. |
  | `title_bn` | String | Optional, max:150 | Bengali Title. |
  | `is_active` | Boolean | Optional | Status. |

### Update Exam Type Subject
- **Endpoint**: `PUT /api/v1/admin/exam-type-subjects/{examTypeSubject}`

### Delete Exam Type Subject
- **Endpoint**: `DELETE /api/v1/admin/exam-type-subjects/{examTypeSubject}` (Fails if questions exist in mapping).

### List Questions (Admin)
- **Endpoint**: `GET /api/v1/admin/questions`

### Store Question
- **Endpoint**: `POST /api/v1/admin/questions`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_subject_id` | Integer | Required, exists:exam_type_subjects | Exam Subject ID. |
  | `topic_id` | Integer | Optional, exists:topics | Subtopic ID. |
  | `lesson_id` | Integer | Optional, exists:lessons | Lesson ID. |
  | `question_text` | String | Required | English content. |
  | `question_bn` | String | Optional | Bengali content. |
  | `explanation` | String | Optional | Explanation details. |
  | `difficulty` | String | Optional (default: `medium`). Must be `easy`, `medium`, `hard` | Difficulty tag. |
  | `xp_value` | Integer | Optional (default: `10`) | Earned XP weight. |

### Update Question
- **Endpoint**: `PUT /api/v1/admin/questions/{question}`

### Delete Question
- **Endpoint**: `DELETE /api/v1/admin/questions/{question}`

### Bulk Import Questions
Enables importing spreadsheet rows. Parses subject fields to associate them automatically.
- **Endpoint**: `POST /api/v1/admin/questions/import`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Scope of target category. |
  | `source_batch` | String | Optional, max:50 | Batch label. |
  | `source_year` | Integer | Optional, range: `1900`-`2100` | Year context. |
  | `rows` | Array | Required, max 500 rows | Array of question records. |
  | `rows.*.question` | String | Required, max: 2000 chars | Question text. |
  | `rows.*.optionA` | String | Required, max: 500 chars | Option A text. |
  | `rows.*.optionB` | String | Required, max: 500 chars | Option B text. |
  | `rows.*.optionC` | String | Optional, max: 500 chars | Option C text. |
  | `rows.*.optionD` | String | Optional, max: 500 chars | Option D text. |
  | `rows.*.rightAnswer` | String | Required. Must be `A`, `B`, `C`, or `D` | Correct option choice. |
  | `rows.*.subject` | String | Required | Case-insensitive matched against ExamTypeSubject title. |
  | `rows.*.explanation` | String | Optional | Question explanation. |
- **Sample Response**:
  ```json
  {
    "success": true,
    "message": "Import complete: 10 imported, 1 skipped, 0 failed (unmatched Exam Subject).",
    "data": {
      "imported": 10,
      "skipped": 1,
      "failed": [],
      "notices": [
        { "row": 3, "reason": "Missing required options (columns B / C)" }
      ]
    }
  }
  ```

### List Lessons (Admin)
- **Endpoint**: `GET /api/v1/admin/lessons`

### Store Lesson
- **Endpoint**: `POST /api/v1/admin/lessons`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Category reference. |
  | `subject_id` | Integer | Required, exists:subjects | Subject ID. |
  | `topic_id` | Integer | Optional, exists:topics | Subtopic ID. |
  | `title` | String | Required, max:200 | Title. |
  | `title_bn` | String | Required, max:200 | Title (Bn). |
  | `description` | String | Optional | Description. |
  | `xp_reward` | Integer | Optional | XP reward weight. |
  | `coin_reward` | Integer | Optional | Coins reward weight. |
  | `difficulty` | String | Optional (`easy`, `medium`, `hard`) | Difficulty. |
  | `question_count` | Integer | Optional | Number of questions. |
  | `sort_order` | Integer | Optional | Sorting order. |
  | `is_active` | Boolean | Optional | Status. |

### Update Lesson
- **Endpoint**: `PUT /api/v1/admin/lessons/{lesson}`

### User Accounts List (Admin)
- **Endpoint**: `GET /api/v1/admin/users`

### Get User Detail Stats (Admin)
- **Endpoint**: `GET /api/v1/admin/users/{user}`

### Toggle User Active Status (Admin)
- **Endpoint**: `PATCH /api/v1/admin/users/{user}/toggle-active`

### List Schedules (Admin)
- **Endpoint**: `GET /api/v1/admin/exam-schedules`
- **Query Parameters**:
  - `exam_type_id` (Integer) - Required, exists:exam_types.

### Create Exam Schedule
- **Endpoint**: `POST /api/v1/admin/exam-schedules`
- **Request Body**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `exam_type_id` | Integer | Required, exists:exam_types | Target Exam Type. |
  | `batch_label` | String | Optional, max:50 | Scoped target batch (e.g. 45th BCS). |
  | `exam_stage` | String | Required. Must be `preliminary`, `written`, `viva`, `main` | Schedule stage. |
  | `scheduled_date` | Date | Required, date format | Date of target exam. |
  | `is_confirmed` | Boolean | Optional | Confirmation status. |
  | `note` | String | Optional, max:255 | Admin notes. |

### Analytics Endpoints (Admin)
- **`GET /api/v1/admin/analytics/retention`**: Gets user retention rates. Query: `exam_type_id` (required).
- **`GET /api/v1/admin/analytics/daily-actives`**: Gets DAU.
- **`GET /api/v1/admin/analytics/subject-performance`**: Aggregate correctness counts. Query: `exam_type_id` (required).
