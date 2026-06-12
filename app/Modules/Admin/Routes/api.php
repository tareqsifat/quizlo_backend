<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\ExamTypeManagementController;
use App\Modules\Admin\Controllers\ContentManagementController;
use App\Modules\Admin\Controllers\UserManagementController;

Route::middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard Stats
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Exam Type Management
    Route::get('/exam-types', [ExamTypeManagementController::class, 'index']);
    Route::post('/exam-types', [ExamTypeManagementController::class, 'store']);
    Route::put('/exam-types/{examType}', [ExamTypeManagementController::class, 'update']);
    Route::get('/exam-types/{examType}/subjects', [ExamTypeManagementController::class, 'listAssignedSubjects']);
    Route::post('/exam-types/assign-subject', [ExamTypeManagementController::class, 'assignSubject']);
    Route::post('/exam-types/{examType}/subjects', [ExamTypeManagementController::class, 'assignSubject']);
    Route::delete('/exam-types/{examType}/subjects/{subject}', [ExamTypeManagementController::class, 'removeSubject']);

    // General Subjects List
    Route::get('/subjects', [ContentManagementController::class, 'listSubjects']);

    // Content Management (Questions)
    Route::get('/questions', [ContentManagementController::class, 'listQuestions']);
    Route::post('/questions', [ContentManagementController::class, 'storeQuestion']);
    Route::put('/questions/{question}', [ContentManagementController::class, 'updateQuestion']);
    Route::delete('/questions/{question}', [ContentManagementController::class, 'destroyQuestion']);
    Route::post('/questions/import', [ContentManagementController::class, 'import']);
    Route::post('/questions/{question}/exam-types', [ContentManagementController::class, 'tagExamTypes']);

    // Content Management (Lessons)
    Route::get('/lessons', [ContentManagementController::class, 'listLessons']);
    Route::post('/lessons', [ContentManagementController::class, 'storeLesson']);
    Route::put('/lessons/{lesson}', [ContentManagementController::class, 'updateLesson']);

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
