<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\GoogleAuthController;

Route::post('/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:5,1');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// Google OAuth routes
// Browser/web redirect flow (testing): GET /v1/auth/google → GET /v1/auth/google/callback
Route::get('/google', [GoogleAuthController::class, 'redirect']);
Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

// Flutter mobile flow: POST /v1/auth/google/token  { access_token: "<google_access_token>" }
Route::post('/google/token', [GoogleAuthController::class, 'loginWithToken']);
