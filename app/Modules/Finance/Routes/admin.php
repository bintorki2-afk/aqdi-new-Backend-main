<?php

use App\Modules\Finance\Controllers\Admin\FinanceController;
use App\Modules\Finance\Controllers\Admin\OperatingExpenseController;
use Illuminate\Support\Facades\Route;

// Finance Management
Route::prefix('finance')->name('finance.')->controller(FinanceController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/expenses', 'index')->middleware('permission:finance_expenses.view')->name('expenses.index');
    Route::post('/expenses', 'store')->middleware('permission:finance_expenses.create')->name('expenses.store');
    Route::get('/expenses/{expense}', 'show')->middleware('permission:finance_expenses.view')->name('expenses.show');
    Route::put('/expenses/{expense}', 'update')->middleware('permission:finance_expenses.edit')->name('expenses.update');
    Route::delete('/expenses/{expense}', 'destroy')->middleware('permission:finance_expenses.delete')->name('expenses.destroy');
});

// Operating expenses (مصاريف تشغيلية)
Route::prefix('operating-expenses')->name('operating-expenses.')
    ->controller(OperatingExpenseController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:operating_expenses.view')->name('index');
        Route::post('/', 'store')->middleware('permission:operating_expenses.create')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:operating_expenses.view')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:operating_expenses.edit')->name('update');
        Route::put('/{id}', 'update')->whereNumber('id')->middleware('permission:operating_expenses.edit')->name('update.put');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:operating_expenses.delete')->name('destroy');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('permission:operating_expenses.delete')->name('destroy.delete');
    });
