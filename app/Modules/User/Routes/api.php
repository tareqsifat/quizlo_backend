<?php

use Illuminate\Support\Facades\Route;
use App\Modules\User\Controllers\UserController;

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/daily-goal', [UserController::class, 'setDailyGoal']);
    Route::post('/exam-types', [UserController::class, 'enroll']);
    Route::delete('/exam-types/{examType}', [UserController::class, 'disenroll']);
    Route::patch('/exam-types/{examType}/set-primary', [UserController::class, 'setPrimary']);
});
