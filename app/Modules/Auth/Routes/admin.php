<?php

use App\Modules\Auth\Controllers\Admin\EmployeeSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')->name('employees.')->controller(EmployeeSessionController::class)->group(function () {
    Route::post('/login', 'login_check')->middleware('throttle:login')->name('login');
    Route::post('/refresh-token', 'refreshToken')->name('refresh-token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', 'profile')->name('me');
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/fcm', 'updateFcmToken')->name('fcm');
        Route::post('/logout', 'logout')->name('logout');
    });
});
