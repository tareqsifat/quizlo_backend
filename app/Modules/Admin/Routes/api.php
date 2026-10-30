<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\ExamTypeManagementController;
use App\Modules\Admin\Controllers\ContentManagementController;
use App\Modules\Admin\Controllers\UserManagementController;
use App\Modules\Admin\Controllers\SubjectManagementController;
use App\Modules\Admin\Controllers\ExamTypeSubjectManagementController;
use App\Modules\Admin\Controllers\TopicManagementController;
use App\Modules\Admin\Controllers\QuestionAppearanceController;
use App\Modules\Admin\Controllers\ActivityLogController;

Route::middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard Stats
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Activity Log
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/admins', [ActivityLogController::class, 'admins']);
    Route::get('/activity-logs/settings', [ActivityLogController::class, 'settings']);
    Route::put('/activity-logs/settings', [ActivityLogController::class, 'updateSettings']);

    // Exam Type Management
    Route::get('/exam-types', [ExamTypeManagementController::class, 'index']);
    Route::post('/exam-types', [ExamTypeManagementController::class, 'store']);
    Route::put('/exam-types/{examType}', [ExamTypeManagementController::class, 'update']);
    Route::get('/exam-types/{examType}/subjects', [ExamTypeManagementController::class, 'listAssignedSubjects']);
    Route::post('/exam-types/assign-subject', [ExamTypeManagementController::class, 'assignSubject']);
    Route::post('/exam-types/{examType}/subjects', [ExamTypeManagementController::class, 'assignSubject']);
    Route::delete('/exam-types/{examType}/subjects/{subject}', [ExamTypeManagementController::class, 'removeSubject']);

    // Subjects CRUD (Feature 1)
    Route::get('/subjects', [SubjectManagementController::class, 'index']);
    Route::get('/subjects/all', [SubjectManagementController::class, 'all']);
    Route::post('/subjects', [SubjectManagementController::class, 'store']);
    Route::put('/subjects/{subject}', [SubjectManagementController::class, 'update']);
    Route::delete('/subjects/{subject}', [SubjectManagementController::class, 'destroy']);

    // Exam Type Subjects CRUD (Feature 2)
    Route::get('/exam-type-subjects', [ExamTypeSubjectManagementController::class, 'index']);
    Route::get('/exam-type-subjects/for-exam-type/{examType}', [ExamTypeSubjectManagementController::class, 'allForExamType']);
    Route::post('/exam-type-subjects', [ExamTypeSubjectManagementController::class, 'store']);
    Route::put('/exam-type-subjects/{examTypeSubject}', [ExamTypeSubjectManagementController::class, 'update']);
    Route::delete('/exam-type-subjects/{examTypeSubject}', [ExamTypeSubjectManagementController::class, 'destroy']);

    // Topics CRUD (Feature 3)
    Route::get('/topics', [TopicManagementController::class, 'index']);
    Route::get('/topics/for-exam-type-subject/{examTypeSubject}', [TopicManagementController::class, 'allForExamTypeSubject']);
    Route::post('/topics', [TopicManagementController::class, 'store']);
    Route::put('/topics/{topic}', [TopicManagementController::class, 'update']);
    Route::delete('/topics/{topic}', [TopicManagementController::class, 'destroy']);

    // Content Management (Questions)
    Route::get('/questions', [ContentManagementController::class, 'listQuestions']);
    Route::get('/questions/years', [ContentManagementController::class, 'listQuestionYears']);
    Route::post('/questions', [ContentManagementController::class, 'storeQuestion']);
    Route::put('/questions/{question}', [ContentManagementController::class, 'updateQuestion']);
    Route::delete('/questions/{question}', [ContentManagementController::class, 'destroyQuestion']);
    Route::post('/questions/import', [ContentManagementController::class, 'import']);

    // Question Exam-Year Appearances (duplicate tracking / search-similar)
    Route::get('/questions/search-similar', [QuestionAppearanceController::class, 'searchSimilar'])->middleware('throttle:30,1');
    Route::get('/questions/{question}/appearances', [QuestionAppearanceController::class, 'index']);
    Route::post('/questions/{question}/appearances', [QuestionAppearanceController::class, 'store']);
    Route::delete('/questions/{question}/appearances/{appearance}', [QuestionAppearanceController::class, 'destroy']);

    // Content Management (Lessons)
    Route::get('/lessons', [ContentManagementController::class, 'listLessons']);
    Route::post('/lessons', [ContentManagementController::class, 'storeLesson']);
    Route::put('/lessons/{lesson}', [ContentManagementController::class, 'updateLesson']);
    Route::delete('/lessons/{lesson}', [ContentManagementController::class, 'destroyLesson']);

    // Content Management (Model Tests)
    Route::get('/model-tests', [ContentManagementController::class, 'listModelTests']);
    Route::post('/model-tests', [ContentManagementController::class, 'storeModelTest']);
    Route::put('/model-tests/{modelTest}', [ContentManagementController::class, 'updateModelTest']);
    Route::delete('/model-tests/{modelTest}', [ContentManagementController::class, 'destroyModelTest']);

    // User Management
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::get('/users/{user}', [UserManagementController::class, 'show']);
    Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive']);

    // Exam Schedules
    Route::get('/exam-schedules', [ExamTypeManagementController::class, 'listSchedules']);
    Route::post('/exam-schedules', [ExamTypeManagementController::class, 'storeSchedule']);

    // Analytics
    Route::get('/analytics/retention', [DashboardController::class, 'retention']);
    Route::get('/analytics/daily-actives', [DashboardController::class, 'dailyActives']);
    Route::get('/analytics/subject-performance', [DashboardController::class, 'subjectPerformance']);
});
