<?php

use App\Modules\Seo\Controllers\Admin\GoogleSearchConsoleController;
use App\Modules\Seo\Controllers\Admin\GoogleSeoController;
use App\Modules\Seo\Controllers\Admin\SeoCrawlController;
use Illuminate\Support\Facades\Route;

// Technical SEO crawl of aqdi.sa (dashboard + issues table)
Route::prefix('seo-crawl')->name('seo-crawl.')
    ->controller(SeoCrawlController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'show')->middleware('permission:seo_crawl.view')->name('show');
        Route::post('/', 'run')->middleware('permission:seo_crawl.create')->name('store');
        Route::post('/run', 'run')->middleware('permission:seo_crawl.create')->name('run');
        Route::post('/stop', 'stop')->middleware('permission:seo_crawl.create')->name('stop');
        Route::get('/issues', 'issues')->middleware('permission:seo_crawl.view')->name('issues');
        Route::get('/issues/{issue}', 'issue')->middleware('permission:seo_crawl.view')->name('issues.show');
    });

Route::get('/seo-google/callback', [GoogleSeoController::class, 'callback'])->name('seo-google.callback');

Route::prefix('seo-google')->name('seo-google.')
    ->controller(GoogleSeoController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/status', 'status')->middleware('permission:seo_crawl.view')->name('status');
        Route::post('/connect', 'connect')->middleware('permission:seo_crawl.create')->name('connect');
        Route::post('/disconnect', 'disconnect')->middleware('permission:seo_crawl.create')->name('disconnect');
    });

Route::prefix('seo-google/search-console')->name('seo-google.search-console.')
    ->controller(GoogleSearchConsoleController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'overview')->middleware('permission:seo_crawl.view')->name('overview');
        Route::get('/sites', 'sites')->middleware('permission:seo_crawl.view')->name('sites');
        Route::post('/sites', 'selectSite')->middleware('permission:seo_crawl.create')->name('sites.select');
        Route::get('/queries', 'queries')->middleware('permission:seo_crawl.view')->name('queries');
        Route::get('/pages', 'pages')->middleware('permission:seo_crawl.view')->name('pages');
        Route::get('/countries', 'countries')->middleware('permission:seo_crawl.view')->name('countries');
        Route::get('/devices', 'devices')->middleware('permission:seo_crawl.view')->name('devices');
        Route::get('/dates', 'dates')->middleware('permission:seo_crawl.view')->name('dates');
        Route::get('/sitemaps', 'sitemaps')->middleware('permission:seo_crawl.view')->name('sitemaps');
    });
