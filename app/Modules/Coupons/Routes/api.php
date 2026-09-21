<?php

use App\Modules\Coupons\Controllers\Api\CouponController;
use App\Modules\Coupons\Controllers\Api\UserCouponController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(UserCouponController::class)->group(function () {
        Route::get('/coupons/mine', 'mine');
        Route::post('/coupons/login-notification/ack', 'acknowledge');
    });

    Route::controller(CouponController::class)->group(function () {
        Route::post('/Coupon/{uuid}', 'Coupon');
    });
});
