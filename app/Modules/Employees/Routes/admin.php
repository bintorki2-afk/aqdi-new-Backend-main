<?php

use App\Modules\Employees\Controllers\Admin\EmployeeController;
use App\Modules\Employees\Controllers\Admin\EmployeeKpiController;
use App\Modules\Employees\Controllers\Admin\PermissionController;
use App\Modules\Employees\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')->name('employees.')->controller(EmployeeController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'index')->middleware('permission:employees.view')->name('index');
        Route::get('/employee-salary', 'employeeSalary')->middleware('permission:employee_salaries.view')->name('employee-salary');
        Route::get('/employee-notes', 'employeeNotes')->middleware('permission:employees.view')->name('employee-notes');
        Route::post('/{id}/salary', 'storeSalary')->whereNumber('id')->middleware('permission:employee_salaries.create')->name('salary.store');
        Route::post('/{id}/note', 'storeNote')->whereNumber('id')->middleware('permission:employees.create')->name('note.store');
        Route::post('/', 'store')->middleware('permission:employees.create')->name('');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:employees.view')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:employees.edit')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:employees.delete')->name('destroy');
        Route::post('/{id}/toggle-status', 'toggleStatus')->whereNumber('id')->middleware('permission:employees.edit')->name('toggle-status');
        Route::post('/{id}/block', 'block')->whereNumber('id')->middleware('permission:employees.edit')->name('block');
        Route::post('/{id}/unblock', 'unblock')->whereNumber('id')->middleware('permission:employees.edit')->name('unblock');
    });
});

Route::prefix('employees')->name('employees.')->middleware('auth:sanctum')->controller(EmployeeKpiController::class)->group(function () {
    Route::get('/kpis', 'index')->middleware('permission:employee_kpis.view')->name('kpis.index');
    Route::get('/me/kpis', 'me')->name('kpis.me');
    Route::get('/{id}/kpis/details', 'details')->whereNumber('id')->middleware('permission:employee_kpis.view')->name('kpis.details');
    Route::get('/{id}/kpis', 'show')->whereNumber('id')->middleware('permission:employee_kpis.view')->name('kpis.show');
});

Route::prefix('roles')->name('roles.')->controller(RoleController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/create', 'create')->middleware('permission:roles.view')->name('create');
    Route::get('/', 'index')->middleware('permission:roles.view')->name('index');
    Route::post('/', 'store')->middleware('permission:roles.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:roles.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:roles.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:roles.delete')->name('destroy');
    Route::post('/{id}/assign-permissions', 'assignPermissions')->middleware('permission:roles.edit')->name('assign-permissions');
});

Route::prefix('permissions')->name('permissions.')->controller(PermissionController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/by-section', 'bySection')->middleware('permission:permissions.view')->name('by-section');
    Route::get('/create', 'create')->middleware('permission:permissions.view')->name('create');
    Route::get('/', 'index')->middleware('permission:permissions.view')->name('index');
    Route::post('/', 'store')->middleware('permission:permissions.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:permissions.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:permissions.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:permissions.delete')->name('destroy');
});
