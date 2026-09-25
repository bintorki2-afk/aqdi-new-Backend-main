<?php

use App\Modules\Leads\Controllers\LeadController;
use App\Modules\Leads\Controllers\WebsiteOrderController;
use Illuminate\Support\Facades\Route;

// CR2: dashboard listing of potential customers (العملاء المحتملون).
// `permission:` (CheckEmployeePermission) is what actually restricts this to dashboard
// employees — `auth:sanctum` alone also accepts a public-website User token.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('admin.leads.index');

    // Dashboard listing of website orders (طلبات الموقع). Separate path from the
    // Contracts module's /api/admin/orders (which lists contract requests).
    Route::get('/website-orders', [WebsiteOrderController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('admin.website-orders.index');
});
