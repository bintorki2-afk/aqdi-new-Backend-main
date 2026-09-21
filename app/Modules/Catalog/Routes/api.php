<?php

use App\Modules\Catalog\Controllers\Api\CatalogLookupController;
use Illuminate\Support\Facades\Route;

Route::controller(CatalogLookupController::class)->group(function () {
    Route::get('/cities', 'cities');
    Route::get('/regions', 'regions');
    Route::get('/bank-accounts', 'bankAccounts');
    Route::get('/services-pricing', 'servicesPricing');
    Route::get('/paperwork', 'paperwork');
    Route::get('/real-estat-type', 'realEstatType');
    Route::get('/real-estat-usage', 'realEstatUsage');
    Route::get('/units-types', 'unitsTypes');
    Route::get('/units-usage', 'unitsUsages');
    Route::get('/payments-types', 'paymentsTypes');
    Route::get('/contract-periods', 'contractPeriods');
});
