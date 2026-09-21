<?php

use App\Modules\Contracts\Controllers\Api\ContractController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Contracts Management
    Route::prefix('contract')->name('contract.')->controller(ContractController::class)->group(function () {
        // Contract Creation Steps
        Route::post('/start', 'contractType')->name('start');
        Route::post('/step1', 'step1');
        Route::post('/step2', 'step2');
        Route::post('/step3', 'step3');
        Route::post('/step4', 'step4');
        Route::post('/step5', 'step5');
        Route::post('/step6', 'step6');

        // Uncompleted Contracts
        Route::get('/check-uncompleted-contract', 'checkUncompletedContract');
        Route::post('/uncompleted-contract', 'getUncompletedContractStep');
    });

    // Contracts Listing & Management
    Route::controller(ContractController::class)->group(function () {
        Route::get('/contracts', 'index')->name('contracts');
        Route::get('/contracts/{id}', 'show');
        Route::get('/getContracts/{uuid}', 'getContracts');
        Route::get('/search/{searchTerm}', 'search');
        Route::get('/financial/{uuid}', 'financial');
    });
});
