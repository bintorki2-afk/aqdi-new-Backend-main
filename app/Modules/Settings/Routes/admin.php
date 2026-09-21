<?php

use App\Modules\Settings\Controllers\Admin\AppStatusController;
use App\Modules\Settings\Controllers\Admin\GeneralSettingController;
use App\Modules\Settings\Controllers\Admin\MeterFeeSettingController;
use App\Modules\Settings\Controllers\Admin\SettingContractController;
use App\Modules\Settings\Controllers\Admin\SettingController;
use App\Modules\Settings\Controllers\Admin\SmsSettingController;
use Illuminate\Support\Facades\Route;

// Contract settings per instrument type (SMS + buttons)
Route::prefix('setting-contracts')->name('setting-contracts.')->controller(SettingContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:instrument_settings.view')->name('index');
    Route::post('/', 'store')->middleware('permission:instrument_settings.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:instrument_settings.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:instrument_settings.edit')->name('update');
});

// Alias used by admin frontend
Route::prefix('instrument-type-settings')->name('instrument-type-settings.')->controller(SettingContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:instrument_settings.view')->name('index');
    Route::post('/', 'store')->middleware('permission:instrument_settings.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:instrument_settings.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:instrument_settings.edit')->name('update');
});

// Project-wide SMS message templates (single settings row)
Route::prefix('sms-settings')->name('sms-settings.')->controller(SmsSettingController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'show')->middleware('permission:settings.view')->name('show');
    Route::post('/', 'update')->middleware('permission:settings.edit')->name('update');
});

// Project-wide meter fees (housing/commercial only)
Route::prefix('meter-fee-settings')->name('meter-fee-settings.')->controller(MeterFeeSettingController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'show')->middleware('permission:settings.view')->name('show');
    Route::post('/', 'update')->middleware('permission:settings.edit')->name('update');
});

// App settings (taxes, social, banner, terms & privacy)
Route::prefix('settings')->name('settings.')->controller(SettingController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:settings.view')->name('index');
    Route::post('/', 'update')->middleware('permission:settings.edit')->name('update');
    Route::post('/image-banner', 'updateImageBanner')->middleware('permission:settings.edit')->name('image-banner.update');
    Route::post('/cover', 'updateCover')->middleware('permission:settings.edit')->name('cover.update');
});

// General settings toggles (/home/settings page: website, stores, thank-you card)
Route::prefix('settings/general')->name('settings.general.')
    ->controller(GeneralSettingController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:settings.view')->name('index');
        Route::put('/', 'bulkUpdate')->middleware('permission:settings.edit')->name('bulk-update');
        Route::put('/{key}', 'update')->middleware('permission:settings.edit')->name('update');
    });

Route::prefix('settings/app-status')->name('settings.app-status.')
    ->controller(AppStatusController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'show')->middleware('permission:settings.view')->name('show');
        Route::match(['put', 'post', 'patch'], '/', 'update')->middleware('permission:settings.edit')->name('update');
    });
