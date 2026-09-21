<?php

use App\Modules\Users\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->controller(UserController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/export', 'export')->middleware('permission:users.view')->name('export');
    Route::get('/', 'allusers')->middleware('permission:users.view')->name('index');
    Route::get('/new', 'newcommersUser')->middleware('permission:users.view')->name('new');
    Route::get('/contracts-complete', 'usersCompleteContracts')->middleware('permission:users.view')->name('contracts-complete');
    Route::get('/{id}/properties/{propertyId}/deed', 'downloadDeed')->whereNumber('id')->whereNumber('propertyId')->middleware('permission:users.view')->name('properties.deed');
    Route::get('/{id}/properties', 'properties')->whereNumber('id')->middleware('permission:users.view')->name('properties.index');
    Route::delete('/{id}/properties/{propertyId}', 'destroyProperty')->whereNumber('id')->whereNumber('propertyId')->middleware('permission:users.delete')->name('properties.destroy');
    Route::delete('/{id}/units/{unitId}', 'destroyUnit')->whereNumber('id')->whereNumber('unitId')->middleware('permission:users.delete')->name('units.destroy');
    Route::post('/{id}/discount', 'applyDiscount')->whereNumber('id')->middleware('permission:users.edit')->name('discount');
    Route::get('/{id}/coupons', 'coupons')->whereNumber('id')->middleware('permission:users.view')->name('coupons.index');
    Route::post('/{id}/coupons', 'storeCoupon')->whereNumber('id')->middleware('permission:users.create')->name('coupons.store');
    Route::get('/{id}/coupons/{couponId}', 'showCoupon')->whereNumber('id')->whereNumber('couponId')->middleware('permission:users.view')->name('coupons.show');
    Route::post('/{id}/coupons/{couponId}/deactivate', 'deactivateCoupon')->whereNumber('id')->whereNumber('couponId')->middleware('permission:users.delete')->name('coupons.deactivate');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:users.view')->name('show');
    Route::post('/{id}/block', 'block')->whereNumber('id')->middleware('permission:users.edit')->name('block');
    Route::post('/{id}/delete', 'deleteUser')->whereNumber('id')->middleware('permission:users.delete')->name('delete');
});
