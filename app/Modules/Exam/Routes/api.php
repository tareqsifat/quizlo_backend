<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Exam\Controllers\ModelTestController;
use App\Modules\Exam\Controllers\ExamSessionController;

Route::middleware('auth:api')->group(function () {
    Route::get('/model-tests', [ModelTestController::class, 'index']);
    Route::post('/model-tests/{test}/start', [ExamSessionController::class, 'start']);
    Route::post('/exam-sessions/{session}/submit', [ExamSessionController::class, 'submit']);
    Route::get('/exam-sessions/{session}/result', [ExamSessionController::class, 'result']);
    Route::get('/exam-countdown', [ModelTestController::class, 'countdown']);
});
