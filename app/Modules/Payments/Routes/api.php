<?php

use App\Http\Middleware\ApiLocalization;
use App\Http\Middleware\CheckApi;
use App\Modules\Payments\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment Status & Callbacks (without API middleware for external callbacks)
Route::withoutMiddleware([CheckApi::class, ApiLocalization::class])->group(function () {
    Route::post('/status/{uuid}/success', [PaymentController::class, 'updateCartByIPN'])->name('callback');
    Route::post('/status/{uuid}', [PaymentController::class, 'Callback'])->name('return');
    Route::get('/status/result/{uuid}', [PaymentController::class, 'result'])->middleware('throttle:payment-public')->name('status.result');
    Route::get('/status/success/{uuid}', [PaymentController::class, 'success'])->middleware('throttle:payment-public')->name('status.success');
    Route::get('/status/error/{uuid}', [PaymentController::class, 'error'])->middleware('throttle:payment-public')->name('status.error');
    Route::get('/payment/result/{uuid}', [PaymentController::class, 'paymentResult'])->middleware('throttle:payment-public')->name('payment.result');

    // Payment
    Route::get('/payment/{uuid}', [PaymentController::class, 'index'])
        ->middleware('throttle:payment-public')
        ->name('payment.show');
});
