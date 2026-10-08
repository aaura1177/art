<?php 
use App\Http\Controllers\PortController;
use App\Http\Controllers\SustainabilityVariableController;
use App\Http\Controllers\SustainabilityStage0Controller;
use App\Http\Controllers\SustainabilityStage1Controller;
use App\Http\Controllers\SustainabilityStage2Controller;
use App\Http\Controllers\SustainabilityStage3Controller;
use App\Http\Controllers\SustainabilityStage3MiscellaneousController;
use App\Http\Controllers\SustainabilityStage3EmployeeController;
use App\Http\Controllers\SustainabilityStage4Controller;
use App\Http\Controllers\SustainabilityStage5Controller;
use App\Http\Controllers\SustainabilityStage6Controller;
use App\Http\Controllers\SustainabilitySummaryController;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', '2fa'])->prefix('sustainability/port')->controller(PortController::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.port.index');
    Route::get('/create', 'create')->name('sustainability.port.create');
    Route::post('/store', 'store')->name('sustainability.port.store');
    Route::get('/view/{Port}', 'show')->name('sustainability.port.view');
    Route::get('/edit/{Port}', 'edit')->name('sustainability.port.edit');
    Route::post('/update/{Port}', 'update')->name('sustainability.port.update');
    Route::delete('/delete/{Port}', 'destroy')->name('sustainability.port.delete');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/variable')->controller(SustainabilityVariableController::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.variable.index');
    Route::get('/create', 'create')->name('sustainability.variable.create');
    Route::post('/store', 'store')->name('sustainability.variable.store');
    Route::get('/view/{sustainabilityVariable}', 'show')->name('sustainability.variable.view');
    Route::get('/edit/{sustainabilityVariable}', 'edit')->name('sustainability.variable.edit');
    Route::post('/update/{sustainabilityVariable}', 'update')->name('sustainability.variable.update');
    Route::delete('/delete/{sustainabilityVariable}', 'destroy')->name('sustainability.variable.delete');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage0')->controller(SustainabilityStage0Controller::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage0.index');
    Route::get('/create', 'create')->name('sustainability.stage0.create');
    Route::post('/store', 'store')->name('sustainability.stage0.store');
    Route::get('/view/{sustainabilityStage0}', 'show')->name('sustainability.stage0.view');
    Route::get('/edit/{sustainabilityStage0}', 'edit')->name('sustainability.stage0.edit');
    Route::post('/update/{sustainabilityStage0}', 'update')->name('sustainability.stage0.update');
    Route::delete('/delete/{sustainabilityStage0}', 'destroy')->name('sustainability.stage0.delete');
    Route::post('/export', 'export')->name('sustainability.stage0.export');

    Route::post('/getContainerDetails', 'containerDetails')->name('sustainability.stage0.getContainerDetails');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage1')->controller(SustainabilityStage1Controller::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage1.index');
    Route::get('/create', 'create')->name('sustainability.stage1.create');
    Route::post('/store', 'store')->name('sustainability.stage1.store');
    Route::get('/view/{sustainabilityStage1}', 'show')->name('sustainability.stage1.view');
    Route::get('/edit/{sustainabilityStage1}', 'edit')->name('sustainability.stage1.edit');
    Route::post('/update/{sustainabilityStage1}', 'update')->name('sustainability.stage1.update');
    Route::delete('/delete/{sustainabilityStage1}', 'destroy')->name('sustainability.stage1.delete');
    Route::post('/export', 'export')->name('sustainability.stage1.export');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage2')->controller(SustainabilityStage2Controller::class)->group(function () {
    Route::get('/', 'fuelIndex')->name('sustainability.stage2.index');
    Route::get('/fuel-consumption', 'fuelIndex')->name('sustainability.stage2.fuel-consumption');
    Route::get('/power-consumption', 'powerIndex')->name('sustainability.stage2.power-consumption');

    Route::get('/create', 'create')->name('sustainability.stage2.create');
    Route::post('/store', 'store')->name('sustainability.stage2.store');
    Route::get('/view/{sustainabilityStage2}', 'show')->name('sustainability.stage2.view');
    Route::get('/edit/{sustainabilityStage2}', 'edit')->name('sustainability.stage2.edit');
    Route::post('/update/{sustainabilityStage2}', 'update')->name('sustainability.stage2.update');
    Route::delete('/delete/{sustainabilityStage2}', 'destroy')->name('sustainability.stage2.delete');
    Route::post('/exportFuelConsumption', 'exportFuelConsumption')->name('sustainability.stage2.fuel-consumption.export');
    Route::post('/exportPowerConsumption', 'exportPowerConsumption')->name('sustainability.stage2.power-consumption.export');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage3')->controller(SustainabilityStage3Controller::class)->group(function () {
    Route::get('/', 'powerIndex')->name('sustainability.stage3.power-consumption');
    Route::get('/power-consumption', 'powerIndex')->name('sustainability.stage3.power-consumption');

    Route::get('/create', 'create')->name('sustainability.stage3.create');
    Route::post('/store', 'store')->name('sustainability.stage3.store');
    Route::get('/view/{sustainabilityStage3}', 'show')->name('sustainability.stage3.view');
    Route::get('/edit/{sustainabilityStage3}', 'edit')->name('sustainability.stage3.edit');
    Route::post('/update/{sustainabilityStage3}', 'update')->name('sustainability.stage3.update');
    Route::delete('/delete/{sustainabilityStage3}', 'destroy')->name('sustainability.stage3.delete');
    
    Route::post('/exportPowerConsumption', 'exportPowerConsumption')->name('sustainability.stage3.power-consumption.export');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage3/miscellaneous')->controller(SustainabilityStage3MiscellaneousController::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage3.miscellaneous.index');
    Route::get('/autocomplete-username','autocomplete')->name('sustainability.stage3.miscellaneous.autocomplete.username');
    Route::get('/autocomplete-origin','autocompleteOrigin')->name('sustainability.stage3.miscellaneous.autocomplete.origin');
    Route::get('/autocomplete-destination','autocompleteDestination')->name('sustainability.stage3.miscellaneous.autocomplete.destination');

    Route::get('/create', 'create')->name('sustainability.stage3.miscellaneous.create');
    Route::post('/store', 'store')->name('sustainability.stage3.miscellaneous.store');
    Route::get('/view/{miscellaneousVariable}', 'show')->name('sustainability.stage3.miscellaneous.view');
    Route::get('/edit/{miscellaneousVariable}', 'edit')->name('sustainability.stage3.miscellaneous.edit');
    Route::post('/update/{miscellaneousVariable}', 'update')->name('sustainability.stage3.miscellaneous.update');
    Route::delete('/delete/{miscellaneousVariable}', 'destroy')->name('sustainability.stage3.miscellaneous.delete');

    Route::post('/export', 'export')->name('sustainability.stage3.miscellaneous.export');
});


Route::middleware(['auth', '2fa'])->prefix('sustainability/stage3/employee')->controller(SustainabilityStage3EmployeeController::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage3.employee.index');
    Route::get('/create', 'create')->name('sustainability.stage3.employee.create');
    Route::post('/store', 'store')->name('sustainability.stage3.employee.store');
    Route::get('/view/{employeeVariable}', 'show')->name('sustainability.stage3.employee.view');
    Route::get('/edit/{employeeVariable}', 'edit')->name('sustainability.stage3.employee.edit');
    Route::post('/update/{employeeVariable}', 'update')->name('sustainability.stage3.employee.update');
    Route::delete('/delete/{employeeVariable}', 'destroy')->name('sustainability.stage3.employee.delete');
    Route::get('/autocomplete-username','autocomplete')->name('sustainability.stage3.employee.autocomplete.username');
    Route::post('/export', 'export')->name('sustainability.stage3.employee.export');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage4')->controller(SustainabilityStage4Controller::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage4.index');
    Route::get('/edit/{sustainabilityStage4}', 'edit')->name('sustainability.stage4.edit');
    Route::post('/update/{sustainabilityStage4}', 'update')->name('sustainability.stage4.update');
    Route::delete('/delete/{sustainabilityStage4}', 'destroy')->name('sustainability.stage4.delete');
    Route::post('/export', 'export')->name('sustainability.stage4.export');
    
    Route::post('/getContainerDetails', 'containerDetails')->name('sustainability.stage4.getContainerDetails');
});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage5')->controller(SustainabilityStage5Controller::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage5.index');
    Route::get('/edit/{sustainabilityStage5}', 'edit')->name('sustainability.stage5.edit');
    Route::post('/update/{sustainabilityStage5}', 'update')->name('sustainability.stage5.update');
    Route::delete('/delete/{sustainabilityStage5}', 'destroy')->name('sustainability.stage5.delete');
    Route::post('/export', 'export')->name('sustainability.stage5.export');
    Route::post('/getContainerDetails', 'containerDetails')->name('sustainability.stage5.getContainerDetails');

});

Route::middleware(['auth', '2fa'])->prefix('sustainability/stage6')->controller(SustainabilityStage6Controller::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.stage6.index');
    Route::get('/create', 'create')->name('sustainability.stage6.create');
    Route::post('/store', 'store')->name('sustainability.stage6.store');
    Route::get('/view/{sustainabilityStage6}', 'show')->name('sustainability.stage6.view');
    Route::get('/edit/{sustainabilityStage6}', 'edit')->name('sustainability.stage6.edit');
    Route::post('/update/{sustainabilityStage6}', 'update')->name('sustainability.stage6.update');
    Route::delete('/delete/{sustainabilityStage6}', 'destroy')->name('sustainability.stage6.delete');
    Route::post('/export', 'export')->name('sustainability.stage6.export');
    Route::post('/export-parcel-deliverd', 'exportParcelDeliverd')->name('sustainability.stage6.export-parcel-deliverd');

});

Route::middleware(['auth', '2fa'])->prefix('sustainability/summary')->controller(SustainabilitySummaryController::class)->group(function () {
    Route::get('/', 'index')->name('sustainability.summary.index');
    Route::post('/export', 'export')->name('sustainability.summary.export');
});




