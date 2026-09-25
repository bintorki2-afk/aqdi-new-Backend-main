<?php

use App\Modules\Leads\Controllers\LeadController;
use App\Modules\Leads\Controllers\WebsiteOrderController;
use Illuminate\Support\Facades\Route;

// CR2: capture a potential-customer lead from the payment screen (before paying).
Route::middleware(['auth:sanctum', 'ensure.customer'])->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->name('v2.leads.store');
});

// Public website order intake (المسار الجديد بدون تسجيل دخول). No auth — a
// logged-out visitor submits the order. Throttled against abuse; an optional
// shared secret (ORDER_INTAKE_TOKEN) is enforced inside the controller when set.
Route::post('/orders', [WebsiteOrderController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('v2.website-orders.store');
