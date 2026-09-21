<?php

use App\Modules\Leads\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

// CR2: dashboard listing of potential customers (العملاء المحتملون).
// `permission:` (CheckEmployeePermission) is what actually restricts this to dashboard
// employees — `auth:sanctum` alone also accepts a public-website User token.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('admin.leads.index');
});
