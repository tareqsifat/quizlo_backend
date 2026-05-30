<?php

use Illuminate\Support\Facades\Route;
use App\Modules\League\Controllers\LeagueController;

Route::middleware('auth:api')->group(function () {
    Route::get('/current', [LeagueController::class, 'current']);
    Route::get('/history', [LeagueController::class, 'history']);
});
