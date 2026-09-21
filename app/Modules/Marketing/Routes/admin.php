<?php

use App\Modules\Marketing\Controllers\Admin\MarketingArticlesController;
use App\Modules\Marketing\Controllers\Admin\MarketingServicePageController;
use App\Modules\Marketing\Controllers\Admin\MarketingTabReportsController;
use App\Modules\Marketing\Controllers\Admin\MarketingTrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('marketing-tracking')->name('marketing-tracking.')
    ->controller(MarketingTrackingController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'overview')->middleware('permission:analytics.view')->name('overview');
        Route::get('/keywords', 'keywords')->middleware('permission:analytics.view')->name('keywords');
        Route::get('/channels', 'channels')->middleware('permission:analytics.view')->name('channels');
    });

Route::prefix('marketing')->name('marketing.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('service-pages')->name('service-pages.')
            ->controller(MarketingServicePageController::class)
            ->group(function () {
                Route::get('/', 'index')->middleware('permission:analytics.view')->name('index');
                Route::post('/', 'store')->middleware('permission:analytics.create')->name('store');
                Route::put('/{id}', 'update')->whereNumber('id')->middleware('permission:analytics.edit')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('permission:analytics.delete')->name('destroy');
            });

        Route::get('/articles', [MarketingArticlesController::class, 'index'])
            ->middleware('permission:blogs.view')
            ->name('articles.index');

        Route::prefix('reports')->name('reports.')
            ->controller(MarketingTabReportsController::class)
            ->group(function () {
                Route::get('/', 'overview')->middleware('permission:analytics.view')->name('overview');
                Route::get('/channels', 'channels')->middleware('permission:analytics.view')->name('channels');
                Route::post('/export', 'export')->middleware('permission:analytics.view')->name('export');
            });
    });
