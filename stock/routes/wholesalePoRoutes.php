<?php

use App\Http\Controllers\WholesalePoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', '2fa'])->prefix('wholesale-po')->controller(WholesalePoController::class)->group(function () {
    Route::get('/', 'index')->name('wholesale-po.index');
    Route::get('/create', 'create')->name('wholesale-po.create');
    Route::post('/store', 'store')->name('wholesale-po.store');
    Route::get('/template', 'downloadTemplate')->name('wholesale-po.template');
    Route::get('/view/{id}', 'show')->name('wholesale-po.show');
    Route::get('/edit/{id}', 'edit')->name('wholesale-po.edit');
    Route::post('/update/{id}', 'update')->name('wholesale-po.update');
    Route::get('/logs/{id}', 'logs')->name('wholesale-po.logs');
    Route::get('/download-excel/{id}', 'downloadUploadedFile')->name('wholesale-po.download-excel');
    Route::get('/allocate/{id}', 'allocateForm')->name('wholesale-po.allocate');
    Route::post('/allocate/{id}', 'saveAllocations')->name('wholesale-po.allocate.save');
    Route::get('/po-generation/{id}', 'poGenerationForm')->name('wholesale-po.po-generation');
    Route::post('/generate-po/{id}', 'generatePo')->name('wholesale-po.generate-po');
    Route::get('/negative-list/{id}', 'negativeList')->name('wholesale-po.negative-list');
    Route::post('/remind/{id}', 'sendReminder')->name('wholesale-po.remind');
    Route::post('/close/{id}', 'markClosed')->name('wholesale-po.close');
});
