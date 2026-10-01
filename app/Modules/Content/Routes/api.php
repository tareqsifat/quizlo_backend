<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Content\Controllers\SubjectController;
use App\Modules\Content\Controllers\LessonController;
use App\Modules\Content\Controllers\QuestionController;
use App\Modules\Content\Controllers\TopicController;

Route::middleware(['auth:api'])->group(function () {
    // Subjects
    Route::get('/subjects', [SubjectController::class, 'index']);

    // Lessons under Subject
    Route::get('/subjects/{subject}/lessons', [LessonController::class, 'index']);

    // Questions under Lesson
    Route::get('/lessons/{lesson}/questions', [QuestionController::class, 'index']);

    // Complete Lesson
    Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete']);

    // Topics under Subject (checkpoint journey)
    Route::get('/subjects/{subject}/topics', [TopicController::class, 'index']);

    // Questions under Topic
    Route::get('/topics/{topic}/questions', [TopicController::class, 'questions']);

    // Complete Topic
    Route::post('/topics/{topic}/complete', [TopicController::class, 'complete']);

    // Answer Submission
    Route::post('/questions/answer', [QuestionController::class, 'answer'])->middleware('hearts');
});
