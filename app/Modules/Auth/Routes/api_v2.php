<?php

use App\Modules\Auth\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/signup', 'signup')->middleware('throttle:otp-send');
    Route::post('/verification', 'verification')->middleware('throttle:otp-verify');
    Route::post('/resend', 'resend')->middleware('throttle:otp-send');
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:otp-send');
    Route::post('/reset-password-code', 'resetPasswordCode')->middleware('throttle:otp-verify');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:otp-verify');
});

Route::middleware(['auth:sanctum', 'ensure.customer'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/auth/logout', 'logout');
    });
});
