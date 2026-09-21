<?php

use App\Modules\Catalog\Controllers\Admin\CityController;
use App\Modules\Catalog\Controllers\Admin\ContractPeriodController;
use App\Modules\Catalog\Controllers\Admin\PaperworkController;
use App\Modules\Catalog\Controllers\Admin\PaymentTypeController;
use App\Modules\Catalog\Controllers\Admin\ReaEstatUsageController;
use App\Modules\Catalog\Controllers\Admin\RegionController;
use App\Modules\Catalog\Controllers\Admin\TenantRoleController;
use App\Modules\Catalog\Controllers\Admin\TypeRealController;
use App\Modules\Catalog\Controllers\Admin\UnitTypeController;
use App\Modules\Catalog\Controllers\Admin\UnitUsageController;
use Illuminate\Support\Facades\Route;

Route::prefix('regions')->name('regions.')->controller(RegionController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:regions.view')->name('index');
    Route::post('/', 'store')->middleware('permission:regions.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:regions.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:regions.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:regions.delete')->name('destroy');
});

Route::prefix('cities')->name('cities.')->controller(CityController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:cities.view')->name('index');
    Route::post('/', 'store')->middleware('permission:cities.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:cities.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:cities.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:cities.delete')->name('destroy');
});

Route::prefix('real-estate-types')->name('real-estate-types.')->controller(TypeRealController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

Route::prefix('real-estate-usages')->name('real-estate-usages.')->controller(ReaEstatUsageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:property_reference.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:property_reference.delete')->name('destroy');
});

Route::prefix('unit-types')->name('unit-types.')->controller(UnitTypeController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/search', 'search')->middleware('permission:property_reference.view')->name('search');
    Route::get('/create', 'create')->middleware('permission:property_reference.view')->name('create');
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:property_reference.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

Route::prefix('unit-usages')->name('unit-usages.')->controller(UnitUsageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/create', 'create')->middleware('permission:property_reference.view')->name('create');
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:property_reference.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

Route::prefix('tenant-roles')->name('tenant-roles.')->controller(TenantRoleController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:tenant_roles.view')->name('index');
    Route::post('/', 'store')->middleware('permission:tenant_roles.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:tenant_roles.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:tenant_roles.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:tenant_roles.delete')->name('destroy');
});

Route::prefix('contract-periods')->name('contract-periods.')->controller(ContractPeriodController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:contract_periods.view')->name('index');
    Route::post('/create', 'create')->middleware('permission:contract_periods.create')->name('create');
    Route::post('/', 'store')->middleware('permission:contract_periods.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:contract_periods.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:contract_periods.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:contract_periods.delete')->name('destroy');
});

Route::prefix('paperworks')->name('paperworks.')->controller(PaperworkController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:paperworks.view')->name('index');
    Route::post('/', 'store')->middleware('permission:paperworks.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:paperworks.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:paperworks.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:paperworks.delete')->name('destroy');
});

Route::prefix('payment-types')->name('payment-types.')->controller(PaymentTypeController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:app_content.view')->name('index');
    Route::post('/', 'store')->middleware('permission:app_content.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:app_content.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:app_content.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:app_content.delete')->name('destroy');
});
