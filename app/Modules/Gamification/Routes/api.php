<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Gamification\Controllers\XpController;
use App\Modules\Gamification\Controllers\StreakController;
use App\Modules\Gamification\Controllers\HeartController;
use App\Modules\Gamification\Controllers\CoinController;

Route::middleware('auth:api')->group(function () {
    // Gamification Dashboard
    Route::get('/gamification/dashboard', [XpController::class, 'dashboard']);

    // Streak
    Route::get('/gamification/streak', [StreakController::class, 'show']);
    Route::post('/gamification/streak/freeze', [StreakController::class, 'spendFreeze']);

    // Hearts
    Route::get('/gamification/hearts', [HeartController::class, 'show']);
    Route::post('/gamification/hearts/refill', [HeartController::class, 'refill']);

    // Coins
    Route::get('/gamification/coins', [CoinController::class, 'show']);
    Route::post('/gamification/coins/spend', [CoinController::class, 'spend']);

    // Achievements
    Route::get('/achievements', [XpController::class, 'achievements']);
    Route::get('/achievements/earned', [XpController::class, 'earnedAchievements']);

    // Progress
    Route::get('/progress/subjects', [XpController::class, 'subjectProgress']);
    Route::get('/progress/daily', [XpController::class, 'dailyProgress']);
    Route::get('/progress/personal-best', [XpController::class, 'personalBest']);
});
