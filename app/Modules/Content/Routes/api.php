<?php

use App\Modules\Content\Controllers\Api\BlogSubDomainController;
use App\Modules\Content\Controllers\Api\GeneralController;
use App\Modules\Content\Controllers\Api\InstructionImageController;
use App\Modules\Content\Controllers\Api\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::controller(GeneralController::class)->group(function () {
    Route::get('/terms-and-conditions', 'termsAndConditions');
    Route::get('/privacy', 'privacy');
    Route::get('/common-questions', 'commonQuestions');
    Route::get('/settings', 'settings');
    Route::get('/cover', 'cover');
});

Route::prefix('instruction-images')->controller(InstructionImageController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{key}', 'show')->where('key', '[a-z0-9\-]+');
});

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.newsletter.store');

Route::prefix('blogs')->name('public.blogs.')->controller(BlogSubDomainController::class)->group(function () {
    Route::get('/', 'blogs')->name('index');
    Route::get('/meta', 'meta')->name('meta');
    Route::post('/', 'store')->middleware('auth:sanctum')->name('store');
    Route::post('/{id}/toggle-active', 'toggleActive')->whereNumber('id')->middleware('auth:sanctum')->name('toggle-active');
    Route::put('/{id}', 'update')->whereNumber('id')->middleware('auth:sanctum')->name('update');
    Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('auth:sanctum')->name('destroy');
    Route::get('/{slug}', 'singleBlog')->name('show');
});

Route::post('/seo/login', [BlogSubDomainController::class, 'login'])
    ->middleware('throttle:login')
    ->name('public.seo.login');
