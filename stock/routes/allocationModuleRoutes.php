<?php 
use App\Http\Controllers\AllocationManagmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', '2fa'])->prefix('allocation-management')->controller(AllocationManagmentController::class)->group(function () {
    Route::get('/', 'index')->name('allocation-management.index');
    Route::get('/history', 'history')->name('allocation-management.history');
    Route::get('/create', 'create')->name('allocation-management.create');
    Route::post('/store', 'store')->name('allocation-management.store');
    Route::get('/view/{allocation}', 'show')->name('allocation-management.view');
    Route::get('/view-history-details/{allocation}', 'showHistory')->name('allocation-management.view-history-details');
    Route::get('/edit/{allocation}', 'edit')->name('allocation-management.edit');
    Route::post('/update/{allocation}', 'update')->name('allocation-management.update');
    Route::get('/pushToInventory/{allocation}', 'pushToInventory')->name('allocation-management.pushToInventory');
    Route::post('/updateVolume/{allocation}', 'updateVolume')->name('allocation-management.updateVolume');
    Route::delete('/delete/{allocation}', 'destroy')->name('allocation-management.delete');
    Route::get('/view-supplier/{id}', 'viewSupplier')->name('allocation-management.view-supplier');
    Route::post('/add-supplier-product/{id}', 'addSupplierProduct')->name('allocation-management.add_supplier_product');
    Route::post('/update_allocation/{id}', 'updateAllocation')->name('allocation-management.update_allocation');
    Route::post('/delete_old_entry', 'deleteOldEntry')->name('allocation-management.delete_old_entry');
    Route::post('/update_allocation_detail', 'updateAllocationDetail')->name('allocation-management.update_allocation_detail');
    Route::get('/po_generation/{id}', 'viewSupplierAllocation')->name('allocation-management.po_generation');
    Route::get('/carton_po_generation/{id}', 'viewSupplierAllocation2')->name('allocation-management.carton_po_generation');
    Route::get('/view_allocated_suppliers/{id}', 'viewAllocatedSuppliers')->name('allocation-management.view_allocated_suppliers');
    Route::get('/view_suppliers_detailed_history/{id}', 'viewSupplierDetailedHistory')->name('allocation-management.view_suppliers_detailed_history');
    Route::post('/generatePo/{id}', 'generatePo')->name('allocation-management.generatePo');
    Route::get('/sendReminderEmails/{id}', 'sendReminderEmails')->name('allocation-management.sendReminderEmails');
    Route::get('/generate_packing_list/{id}', 'generatePackingList')->name('allocation-management.generate_packing_list');
    Route::post('/generateCartonPo/{id}', 'generateCartonPo')->name('allocation-management.generateCartonPo');
    Route::get('/print_allocation_list/{id}', 'printAllocationList')->name('allocation-management.print_allocation_list');
    Route::get('/print_packaging_list/{id}', 'printPackagingList')->name('allocation-management.print_packaging_list');
    Route::get('/print-negative-item-list/{id}', 'printNegativeItemList')->name('allocation-management.print-negative-item-list');
    Route::get('/mark_completed_and_dispatched/{id}', 'markCompletedAndDispatched')->name('allocation-management.mark_completed_and_dispatched');

});
