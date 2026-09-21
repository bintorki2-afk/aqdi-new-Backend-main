<?php

use App\Modules\Users\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AccountController::class)->group(function () {
        Route::get('/profile', 'profile');
        Route::post('/profile', 'updateProfile');
        Route::post('/update/password', 'updatePassword');
        Route::post('/fcm', 'updateFCMToken');
        Route::get('/notifications', 'notifications');
        Route::post('/user/deactivate', 'deactivateUser');
    });
});
