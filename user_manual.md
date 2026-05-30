# Quizlo - User Manual & API Guide

Welcome to Quizlo! This user manual guides you through setting up the backend application, running the test suite, and utilizing the RESTful API endpoints for the mobile client.

---

## 1. Setup & Installation

### Prerequisites
- PHP >= 8.3
- Composer
- SQLite (or another relational database)

### Installation Steps
1. **Install Dependencies**:
   ```bash
   composer install
   ```
2. **Environment File**:
   Copy `.env.example` to `.env` and configure your database settings (default is SQLite `:memory:` or `database/database.sqlite`).
3. **Database Migrations & Seeders**:
   Run database migrations and seed default values (such as League Tiers and Exam Types):
   ```bash
   php artisan migrate:fresh --seed
   ```
4. **OAuth Client Installation (Passport)**:
   Generate encryption keys for Passport authentication tokens:
   ```bash
   php artisan passport:install
   ```
5. **Start Dev Server**:
   ```bash
   php artisan serve
   ```

---

## 2. Running Tests
To execute the comprehensive unit, feature, integration, and security test suite:
```bash
php artisan test
```

---

## 3. API Guide & Flow

All endpoints are prefixed with `/api/v1`. Authenticated requests must include the `Authorization: Bearer <token>` header.

### 3.1 Authentication Flow (Passwordless OTP)
Quizlo uses a secure phone-based verification mechanism:

1. **Send OTP Code**:
   - **Endpoint**: `POST /api/v1/auth/send-otp`
   - **Payload**:
     ```json
     { "phone": "01700000001" }
     ```
2. **Verify OTP & Login**:
   - **Endpoint**: `POST /api/v1/auth/verify-otp`
   - **Payload**:
     ```json
     {
       "phone": "01700000001",
       "otp_code": "123456"
     }
     ```
   - **Response**: Returns your OAuth access token (`access_token`) and refresh token.

---

### 3.2 User Management & Enrollment

- **Get Profile**: `GET /api/v1/user/profile`
- **Update Profile**: `PUT /api/v1/user/profile`
  - *Payload*: `{ "name": "Sajid", "email": "sajid@example.com" }`
- **Set Daily Question Goal**: `PUT /api/v1/user/daily-goal`
  - *Payload*: `{ "daily_goal": 20 }`
- **Enroll in Exam Type**: `POST /api/v1/user/exam-types`
  - *Payload*:
    ```json
    {
      "exam_type_id": 1,
      "is_primary": true,
      "target_year": 2026
    }
    ```
- **Set Primary Exam Type**: `PATCH /api/v1/user/exam-types/{examType}/set-primary`
- **Disenroll**: `DELETE /api/v1/user/exam-types/{examType}`

---

### 3.3 Learning & Answering Questions

1. **Get Subjects**: `GET /api/v1/subjects?exam_type_id=1`
2. **Get Subject Lessons**: `GET /api/v1/subjects/{subject}/lessons?exam_type_id=1`
3. **Get Questions**: `GET /api/v1/lessons/{lesson}/questions`
4. **Submit Answer**:
   - **Endpoint**: `POST /api/v1/questions/answer`
   - **Payload**:
     ```json
     {
       "exam_type_id": 1,
       "question_id": 5,
       "selected_option_id": 19,
       "session_type": "practice"
     }
     ```
   - **Behavior**:
     - *Correct Answer*: Awards XP (defined by question value) and increments your active streak.
     - *Incorrect Answer*: Deducts 1 heart and outputs the explanation text.
5. **Complete Lesson**: `POST /api/v1/lessons/{lesson}/complete`
   - Awards bonus XP and coins.

---

### 3.4 Gamification Controls

- **Get Gamification Dashboard**: `GET /api/v1/gamification/dashboard?exam_type_id=1` (returns level, current XP, hearts, coins, streak, and daily progress)
- **Get Streak Status**: `GET /api/v1/gamification/streak`
- **Buy/Use Streak Freeze**: `POST /api/v1/gamification/streak/freeze`
- **Get Hearts Count**: `GET /api/v1/gamification/hearts`
- **Refill Hearts**: `POST /api/v1/gamification/hearts/refill` (spends coins to refill back to 5 max)

---

### 3.5 Leagues (Weekly Competition)
Users competing in the same exam type are auto-assigned to groups of 30 within tiers (Bronze, Silver, Gold, etc.).

- **Get Current Standings**: `GET /api/v1/league/current?exam_type_id=1`
- **Get League History**: `GET /api/v1/league/history?exam_type_id=1`

*Note: A background scheduler evaluates promotions and relegations at the end of each week.*

---

### 3.6 Model Tests (Exams)
- **List Available Model Tests**: `GET /api/v1/model-tests?exam_type_id=1`
- **Start Model Test**: `POST /api/v1/model-tests/{test}/start`
- **Submit Test Session**: `POST /api/v1/exam-sessions/{session}/submit`
- **Get Test Result**: `GET /api/v1/exam-sessions/{session}/result`

---

## 4. Admin Administration (Scope: Admin)

Endpoints prefixed with `/api/v1/admin/` require the admin passport token scope.

- **List All Users**: `GET /api/v1/admin/users`
- **Toggle User Status**: `PATCH /api/v1/admin/users/{userId}/toggle-active`
- **Create Exam Type**: `POST /api/v1/admin/exam-types`
- **Assign Subject**: `POST /api/v1/admin/exam-types/assign-subject`
