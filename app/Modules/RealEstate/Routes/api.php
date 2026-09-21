<?php

use App\Modules\RealEstate\Controllers\Api\RealEstateControllor;
use App\Modules\RealEstate\Controllers\Api\SavedRealEstateController;
use App\Modules\RealEstate\Controllers\Api\UnitEstateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Saved Properties
    Route::controller(SavedRealEstateController::class)->group(function () {
        Route::post('/save/property', 'SavedRealEstate');
    });

    // Real Estate Management
    Route::prefix('realState')->controller(RealEstateControllor::class)->group(function () {
        // Real Estate Creation Steps
        Route::post('/step1', 'step1');
        Route::post('/step2', 'step2');
        Route::post('/step3', 'step3');

        // Real Estate Updates
        Route::post('/update/step1', 'updateStep1');
        Route::post('/update/step2', 'updateStep2');
        Route::post('/update/step3', 'updateStep3');
    });

    // Real Estate Operations
    Route::controller(RealEstateControllor::class)->group(function () {
        Route::get('/realState/index', 'index');
        Route::get('/realState/show/{id}', 'show');
        Route::get('/real-estates/units/{id}', 'showUnits');
        Route::delete('/realState/delete/{id}', 'delete');
        Route::get('/realState/all', 'all');
    });

    // Units Management
    Route::prefix('unit')->controller(UnitEstateController::class)->group(function () {
        Route::post('/create', 'create');
        Route::get('/index/{id}', 'index');
        Route::get('/show/{id}', 'show');
        Route::post('/update/{id}', 'update');
        Route::delete('/delete/{id}', 'delete');
        Route::get('/all/{id}', 'all');
    });
});
