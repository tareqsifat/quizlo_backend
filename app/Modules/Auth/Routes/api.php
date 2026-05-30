<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;

Route::post('/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:5,1');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
