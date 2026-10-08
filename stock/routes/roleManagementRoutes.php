<?php 
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\PermissionManagementController;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', '2fa'])->prefix('role-management')->controller(RoleManagementController::class)->group(function () {
    Route::get('/', 'index')->name('role-management.index');
    Route::get('/create', 'create')->name('role-management.create');
    Route::post('/store', 'store')->name('role-management.store');
    Route::get('/view/{role}', 'show')->name('role-management.view');
    Route::get('/edit/{role}', 'edit')->name('role-management.edit');
    Route::post('/update/{role}', 'update')->name('role-management.update');
    Route::delete('/delete/{role}', 'destroy')->name('role-management.delete');
});

Route::middleware(['auth', '2fa'])->prefix('permission-management')->controller(PermissionManagementController::class)->group(function () {
    Route::get('/', 'index')->name('permission-management.index');
    Route::get('/create', 'create')->name('permission-management.create');
    Route::post('/store', 'store')->name('permission-management.store');
    Route::get('/view/{permission}', 'show')->name('permission-management.view');
    Route::get('/edit/{permission}', 'edit')->name('permission-management.edit');
    Route::post('/update/{permission}', 'update')->name('permission-management.update');
    Route::delete('/delete/{permission}', 'destroy')->name('permission-management.delete');
});

