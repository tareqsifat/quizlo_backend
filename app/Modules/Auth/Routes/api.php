<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Phone OTP Auth
Route::middleware('throttle:5,1')->post('send-otp', [AuthController::class, 'sendOtp']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

// Admin Auth
Route::post('admin-login', [AuthController::class, 'adminLogin']);
Route::post('refresh-token', [AuthController::class, 'refreshToken']);

// Google OAuth
Route::get('google', [AuthController::class, 'googleSignIn']);
Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('google/exchange', [AuthController::class, 'googleTokenExchange']);

// Email Auth & Verification Flow
Route::post('register', [AuthController::class, 'register']);
Route::middleware('throttle:5,1')->post('send-verification', [AuthController::class, 'sendVerification']);
Route::get('verification-link', [AuthController::class, 'verificationLink']);
Route::post('verification', [AuthController::class, 'verification']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('throttle:5,1')->post('send-forget-password-otp', [AuthController::class, 'sendForgetPasswordOtp']);
Route::post('update-password', [AuthController::class, 'updatePassword']);

// Authenticated Password Change
Route::middleware('auth:api')->post('change-password', [AuthController::class, 'changePassword']);
