<?php

use App\Modules\Coupons\Controllers\Admin\CouponAdminController;
use Illuminate\Support\Facades\Route;

// Coupons Management
Route::prefix('coupons')->name('coupons.')->controller(CouponAdminController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:coupons.view')->name('index');
    Route::post('/', 'store')->middleware('permission:coupons.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:coupons.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:coupons.edit')->name('update');
    Route::post('/{id}/inactive', 'inactive')->whereNumber('id')->middleware('permission:coupons.edit')->name('inactive');
    Route::post('/{id}/activate', 'activate')->whereNumber('id')->middleware('permission:coupons.edit')->name('activate');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:coupons.delete')->name('destroy');
});
