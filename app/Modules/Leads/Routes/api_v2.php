<?php

use App\Modules\Leads\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

// CR2: capture a potential-customer lead from the payment screen (before paying).
Route::middleware(['auth:sanctum', 'ensure.customer'])->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->name('v2.leads.store');
});
