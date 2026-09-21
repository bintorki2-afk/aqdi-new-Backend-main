<?php

use App\Modules\Settings\Controllers\Api\V2\AppStatusController as V2AppStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/app-status', [V2AppStatusController::class, 'show']);
Route::get('/website-status', [V2AppStatusController::class, 'website']);
