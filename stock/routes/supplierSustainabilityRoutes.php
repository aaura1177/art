<?php 
use App\Http\Controllers\SupplierSustainabilityStage1Controller;
use App\Http\Controllers\SupplierSustainabilityStage2Controller;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('transport/logs')->controller(SupplierSustainabilityStage1Controller::class)->group(function () {
    Route::get('/', 'index')->name('transport.logs.index');
    Route::get('/create', 'create')->name('transport.logs.create');
    Route::post('/store', 'store')->name('transport.logs.store');
    Route::get('/view/{transportlogs}', 'show')->name('transport.logs.view');
    Route::get('/edit/{transportlogs}', 'edit')->name('transport.logs.edit');
    Route::post('/update/{transportlogs}', 'update')->name('transport.logs.update');
    Route::delete('/delete/{transportlogs}', 'destroy')->name('transport.logs.delete');
});


Route::middleware(['auth'])->prefix('production/logs')->controller(SupplierSustainabilityStage2Controller::class)->group(function () {
    Route::get('/', 'fuelIndex')->name('production.logs.index');
    Route::get('/fuel-consumption', 'fuelIndex')->name('production.logs.fuel-consumption');
    Route::get('/power-consumption', 'powerIndex')->name('production.logs.power-consumption');

    Route::get('/create', 'create')->name('production.logs.create');
    Route::post('/store', 'store')->name('production.logs.store');
    Route::get('/view/{productionlogs}', 'show')->name('production.logs.view');
    Route::get('/edit/{productionlogs}', 'edit')->name('production.logs.edit');
    Route::post('/update/{productionlogs}', 'update')->name('production.logs.update');
    Route::delete('/delete/{productionlogs}', 'destroy')->name('production.logs.delete');
});
