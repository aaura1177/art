<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Http\Controllers\ArtisanEmissionController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\LogisticsPartnerController;
use App\Http\Controllers\EmployeedetailController;
use App\Http\Controllers\EmployeeExpenseController;
use App\Http\Controllers\TransportMaritimeEmissionController;
use App\Http\Controllers\LogisticsEmissionController;
use App\Http\Controllers\DistributionEmissionController;
use App\Http\Controllers\TravelExpenseController;
use App\Http\Controllers\SupplierCertificateController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\InventoryConsumableController;
use App\Http\Controllers\ManualConsumableInvoiceCountController;
use App\Http\Controllers\ManualCartonInvoiceCountController;
use App\Http\Controllers\ManualStockoutPendingController;
use App\Http\Controllers\HardwareMonthEndController;
use App\Http\Controllers\AdvancedReportsController;
use App\Http\Controllers\SupplierTermsController;

use App\Http\Controllers\ServiceProductController;
use App\Http\Controllers\ProductSupplierController;
use App\Http\Controllers\notificationsController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth','2fa'], function () {
    Route::get('/import-export', 'ExportImportController@exportimport');
    Route::post('/export', 'ExportImportController@export')->name('product.export');
    Route::post('/import', 'ExportImportController@import')->name('product.import');
    Route::post('/consumables/export', 'ExportImportController@consumablesexport')->name('product.consumablesexport');
    Route::post('/sample/export', 'ExportImportController@sampleexport')->name('product.sampleexport');
    Route::post('/furniture/export', 'ExportImportController@furnitureexport')->name('product.furnitureexport');
    Route::post('/purchase-ledger', 'ExportImportController@purchaseledger')->name('product.purchaseledger');

    // Route::resource('expenses', TravelExpenseController::class);
    // Route::resource('countries', CountryController::class);
    // Route::resource('logistics_partners', LogisticsPartnerController::class);
    // Route::resource('employeedetails', EmployeedetailController::class);
    // Route::resource('employee_expenses', EmployeeExpenseController::class);
    // Route::resource('emissions', TransportMaritimeEmissionController::class);/supplier-dashboard/accept-purchase-order
    // Route::resource('logistics_emission', LogisticsEmissionController::class);
    // Route::resource('distribution_emissions', DistributionEmissionController::class);
    // Route::post('/employee-expenses/import', [EmployeeExpenseController::class, 'import'])->name('employee_expenses.import');
    Route::get('/product_inventory/', [InventoryReportController::class, 'index'])->name('products.inventory');
    Route::get('/reports/advanced', [AdvancedReportsController::class, 'index'])->name('reports.advanced.index');
    Route::get('/reports/advanced/invoice-options', [AdvancedReportsController::class, 'invoiceOptions'])->name('reports.advanced.invoice_options');
    Route::get('/reports/advanced/export', [AdvancedReportsController::class, 'export'])->name('reports.advanced.export');
    Route::get('/batch/export', [InventoryReportController::class, 'batchExport'])->name('products.batchExport');
    Route::post('/batch/stock/report/export', [InventoryReportController::class, 'batchExportStockReport'])->name('products.batchExport.stock.reprot');
    Route::post('/batch/report/export', [InventoryReportController::class, 'batchExportReport'])->name('products.batchExport.report');
    Route::post('/Stock/report/export', [InventoryReportController::class, 'StockExportReport'])->name('products.stockExport.report');
    Route::post('/Stock/report/export/product', [InventoryReportController::class, 'StockExportReportProduct'])->name('products.stockExport.report.prodcut');
    Route::get('/product/detail_report/{id}', [InventoryReportController::class, 'detail_report'])->name('products.detail_report');


       Route::get('/stock/quantity',[InventoryReportController::class, 'stockQty'])->name('products.stockQty');
Route::get('supplierInvoice/no/swap', [InventoryReportController::class, 'SupplierInvoiceNOswap'])
    ->name('supplier.invoice.swap');
Route::get('supplierInvoice/stock', [InventoryReportController::class, 'SupplierInvoicestock'])
    ->name('supplier.invoice.stock');


    Route::get('/stock/buyername', [InventoryReportController::class, 'buyername'])
    ->name('supplier.invoice.e');
    
    Route::post('/batch/stock/report/export', [InventoryReportController::class, 'batchExportStockReport'])->name('products.batchExport.stock.reprot');

        Route::get('/consumable_inventory/', [InventoryConsumableController::class, 'index'])->name('consumable.inventory');
    Route::get('/consumable/detail_report/{id}', [InventoryConsumableController::class, 'detail_report'])->name('consumable.detail_report');
    Route::get('/carton/', [InventoryConsumableController::class, 'carton'])->name('carton.inventory');
    Route::get('/carton/detail_report/{id}', [InventoryConsumableController::class, 'carton_detail_report'])->name('carton.detail_report');

    Route::get('/consumable/manual-invoice-count', [ManualConsumableInvoiceCountController::class, 'index'])
        ->name('consumable.manual_invoice_count');
    Route::get('/consumable/manual-invoice-count/pending/{invoice}', [ManualConsumableInvoiceCountController::class, 'pendingDetail'])
        ->name('consumable.manual_pending_detail');

    Route::post('/consumable/manual-invoice-consumable-stockout', [ManualConsumableInvoiceCountController::class, 'stockoutConsumables'])
        ->name('consumable.manual_invoice_consumable_stockout');

    Route::get('/carton/manual-invoice-count', [ManualCartonInvoiceCountController::class, 'index'])
        ->name('carton.manual_invoice_count');
    Route::get('/carton/manual-invoice-count/alternate-products', [ManualCartonInvoiceCountController::class, 'searchAlternateProducts'])
        ->name('carton.manual_alternate_products');

    Route::post('/carton/manual-invoice-carton-stockout', [ManualCartonInvoiceCountController::class, 'stockoutCartons'])
        ->name('carton.manual_invoice_carton_stockout');

    Route::post('/stockout/manual-pending/fulfill-consumable', [ManualStockoutPendingController::class, 'fulfillConsumable'])
        ->name('stockout.manual_pending_fulfill_consumable');
    Route::post('/stockout/manual-pending/fulfill-carton', [ManualStockoutPendingController::class, 'fulfillCarton'])
        ->name('stockout.manual_pending_fulfill_carton');

    Route::get('/manual-stock/backmonth', 'BackmonthStockController@index')
        ->name('manual_stock.backmonth.index');
    Route::get('/manual-stock/backmonth/{invoiceId}', 'BackmonthStockController@show')
        ->name('manual_stock.backmonth.show');
    Route::post('/manual-stock/backmonth/{invoiceId}/cumulative-update', 'BackmonthStockController@cumulativeUpdate')
        ->name('manual_stock.backmonth.cumulative_update');
    Route::post('/manual-stock/backmonth/{invoiceId}/revert-cumulative-update', 'BackmonthStockController@revertCumulativeUpdate')
        ->name('manual_stock.backmonth.revert_cumulative_update');
    Route::post('/manual-stock/backmonth/{invoiceId}/individual-update', 'BackmonthStockController@individualUpdate')
        ->name('manual_stock.backmonth.individual_update');

    Route::get('/', 'HomeController@login');
    Route::get('/dashboard', 'HomeController@index')->name('dashboard');
    Route::get('/dashboard/data', 'HomeController@data');
    Route::get('/supplier-dashboard', 'supplierUserController@dashboard')->name('supplier.dashboard');
     Route::get('/multiple-po-instructions', 'supplierUserController@instructions')->name('Supplier Instruction');
        Route::get('/supplier-dashboard/terms', [SupplierTermsController::class, 'showSupplier'])->name('supplier.terms');
    Route::post('/supplier-dashboard/terms/accept', [SupplierTermsController::class, 'accept'])->name('supplier.terms.accept');
    Route::get('/supplier-dashboard/terms/pdf', [SupplierTermsController::class, 'downloadPdf'])->name('supplier.terms.pdf');
    Route::get('/supplier-dashboard/purchase-orders', 'supplierUserController@getPurchaseOrders')->name('Supplier Dashboard');
    Route::get('/supplier-dashboard/draft-purchase-orders', 'DraftPurchaseOrderController@supplierIndex');
    Route::get('/supplier-dashboard/draft-purchase-orders/modal/{id}', 'DraftPurchaseOrderController@supplierModal');
    Route::get('/supplier-dashboard/draft-consumable-purchase-orders', 'DraftConsumablePurchaseOrderController@supplierIndex');
    Route::get('/supplier-dashboard/draft-consumable-purchase-orders/modal/{id}', 'DraftConsumablePurchaseOrderController@supplierModal');

    Route::get('/supplier-dashboard/eligible-pos', 'supplierUserController@eligiblePOs')->name('supplier.eligible.pos');  
       Route::get('/supplier-dashboard/po/{id}/detail', 'supplierUserController@poDetail')->name('supplier.po.detail');
 
    Route::get('/supplier-dashboard/service', 'supplierUserController@getservice')->name('Supplier getservice');
    Route::get('/supplier-dashboard/raise-Serviceinvoice/{id}', 'supplierUserController@raiseServiceInvoice');
    Route::get('/supplier-dashboard/accept-service-order/{id}', 'supplierUserController@acceptServiceOrder');
    Route::post('/supplier-dashboard/raise-service-invoice/{id}', 'supplierUserController@createServiceInvoice');
    Route::get('/supplier-dashboard/data/serviceTable/{id}', 'supplierUserController@serivceTableData');
    Route::get('/supplier-dashboard/service-invoice-orders', 'supplierUserController@getserviceInvoiceOrders')->name('Supplier Dashboard');
    Route::get('/supplier-dashboard/service-invoiceModal/{id}', 'supplierUserController@serviceinvoiceModal');
    Route::get('/supplier-dashboard/service-edit-invoice/{id}', 'supplierUserController@serviceeditInvoice');
    Route::post('/supplier-dashboard/update-service-invoice/{id}', 'supplierUserController@updateServiceInvoice');
    Route::get('/supplier-dashboard/cancelServiceInvoice/{id}', 'supplierUserController@cancelServiceInvoice');
    Route::get('/supplier-dashboard/service-modal/{id}', 'supplierUserController@servicemodal');
    Route::get('/supplier-dashboard/accepted-service-orders', 'supplierUserController@getAcceptedServiceOrders')->name('Supplier Dashboard');

    Route::get('/notifications/get', [notificationsController::class, 'getNotifications'])->name('notifications.get');
    Route::resource('/certificates', SupplierCertificateController::class);
    Route::get('/supplier-dashboard/invoice-orders', 'supplierUserController@getInvoiceOrders')->name('Supplier Dashboard');
     Route::get('/supplier-dashboard/invoice-orders/multi', 'supplierUserController@getInvoiceOrdersmulti')->name('Supplier Dashboard multi');
    Route::get('/supplier-dashboard/accepted-purchase-orders', 'supplierUserController@getAcceptedPurchaseOrders')->name('Supplier Dashboard');
    Route::get('/supplier-dashboard/accepted-purchase-orders-consumable', 'supplierUserController@getAcceptedConsumablePurchaseOrders')->name('Supplier Dashboard Consumable Accepted');
    Route::get('/supplier-dashboard/accepted-purchase-orders-consumable-multi', 'SupplierMultiPoConsumableController@acceptedPurchaseOrders');
    Route::match(['get', 'post'], '/supplier-dashboard/raise-invoice/multiple-consumable', 'SupplierMultiPoConsumableController@raiseInvoice');
    Route::post('/supplier-dashboard/submit-invoice/multiple-consumable', 'SupplierMultiPoConsumableController@submitInvoice');
    Route::get('/supplier-dashboard/invoice-orders/multi-consumable', 'SupplierMultiPoConsumableController@invoiceOrders');
    Route::get('/supplier-dashboard/edit-invoice/multi-consumable/{id}', 'SupplierMultiPoConsumableController@editInvoice');
    Route::post('/supplier-dashboard/update-invoice/multi-consumable/{id}', 'SupplierMultiPoConsumableController@updateInvoice');
    Route::get('/supplier-dashboard/cancelInvoice/multi-consumable/{id}', 'SupplierMultiPoConsumableController@cancelInvoice');
    Route::get('/supplier-dashboard/eligible-pos-consumable', 'SupplierMultiPoConsumableController@eligiblePOs')->name('supplier.eligible.pos.consumable');
    Route::get('/supplier-dashboard/po-consumable/{id}/detail', 'SupplierMultiPoConsumableController@poDetail')->name('supplier.po.detail.consumable');
    Route::get('/supplier-dashboard/accepted-purchase-orders-carton', 'SupplierMultiPoCartonController@acceptedPurchaseOrdersSingle');
    Route::get('/supplier-dashboard/accepted-purchase-orders-carton-multi', 'SupplierMultiPoCartonController@acceptedPurchaseOrders');
    Route::match(['get', 'post'], '/supplier-dashboard/raise-invoice/multiple-carton', 'SupplierMultiPoCartonController@raiseInvoice');
    Route::post('/supplier-dashboard/submit-invoice/multiple-carton', 'SupplierMultiPoCartonController@submitInvoice');
    Route::get('/supplier-dashboard/invoice-orders/multi-carton', 'SupplierMultiPoCartonController@invoiceOrders');
    Route::get('/supplier-dashboard/edit-invoice/multi-carton/{id}', 'SupplierMultiPoCartonController@editInvoice');
    Route::post('/supplier-dashboard/update-invoice/multi-carton/{id}', 'SupplierMultiPoCartonController@updateInvoice');
    Route::get('/supplier-dashboard/cancelInvoice/multi-carton/{id}', 'SupplierMultiPoCartonController@cancelInvoice');
    Route::get('/supplier-dashboard/eligible-pos-carton', 'SupplierMultiPoCartonController@eligiblePOs')->name('supplier.eligible.pos.carton');
    Route::get('/supplier-dashboard/po-carton/{id}/detail', 'SupplierMultiPoCartonController@poDetail')->name('supplier.po.detail.carton');
    Route::get('/supplier-dashboard/returned-purchase-orders', 'supplierUserController@getReturnedPurchaseOrders')->name('Supplier Dashboard');
    Route::get('/supplier-dashboard/modal/{id}', 'supplierUserController@modal');
     Route::get('/supplier-dashboard/modal/carton/{id}', 'supplierUserController@modalCarton');

    Route::get('/supplier-dashboard-service/modal/{id}', 'supplierUserController@servicemodal');
    Route::get('/supplier-dashboard/invoiceModal/{id}', 'supplierUserController@invoiceModal');
    Route::get('/supplier-dashboard/accept-purchase-order/{id}', 'supplierUserController@acceptPurchaseOrder');
    Route::get('/supplier-dashboard/purchase-orders/{id}/versions', 'supplierUserController@poVersionHistory');
    Route::post('/supplier-dashboard/purchase-orders/{id}/versions/{versionId}/accept', 'supplierUserController@acceptPoVersion');
    Route::get('/supplier-dashboard/raise-invoice/{id}', 'supplierUserController@raiseInvoice')
        ->where('id', '^(10000000)?[0-9]+$');
     Route::get('/supplier-dashboard/invoiceModal/multi/{id}', 'supplierUserController@invoiceModalMulti');
    Route::match(['get', 'post'], '/supplier-dashboard/raise-invoice/multiple', 'supplierUserController@raiseInvoicemultiple');
    
    Route::post('/supplier-dashboard/submit-invoice/multiple', 'supplierUserController@submitInvoicemultiple')->name('multi-invoice.store');
    Route::get('/supplier-dashboard/edit-invoice/{id}', 'supplierUserController@editInvoice');
           Route::get('/supplier-dashboard/edit-invoice/multi/{id}', 'supplierUserController@editInvoicemulti');
    Route::get('/supplier-dashboard/edit-invoice/carton/{id}', 'supplierUserController@editInvoiceCarton');

    Route::get('/supplier-dashboard/cancelInvoice/{id}', 'supplierUserController@cancelInvoice');
    Route::get('/supplier-dashboard/cancelInvoice/multi/{id}', 'supplierUserController@cancelInvoicemulti');
    Route::post('/supplier-dashboard/raise-invoice/{id}', 'supplierUserController@createInvoice')
        ->where('id', '^(10000000)?[0-9]+$');
    Route::post('/supplier-dashboard/update-invoice/{id}', 'supplierUserController@updateInvoice');
           Route::post('/supplier-dashboard/update-invoice/multi/{id}', 'supplierUserController@updateInvoicemulti');
    Route::post('/supplier-dashboard/update_invoice_carton/{id}', 'supplierUserController@updateInvoiceCarton');

    Route::get('/supplier-dashboard/data', 'supplierUserController@data');
    Route::get('/supplier-dashboard/data/pbPoTable/{id}', 'supplierUserController@poTableData');

    Route::post('/supplier-dashboard/exportcsv', 'supplierUserController@exportcsv');
    Route::get('/supplier-dashboard/noti-read/{id}', 'supplierUserController@notiRead');
    Route::get('/supplier-dashboard/raiseChallan/{id}', 'supplierUserController@raiseChallan');

    Route::get('/erp-manager', 'productController@erpManager');
    Route::get('/product/erp_diff_manager/{type}', 'productController@erp_diff_manager');
    Route::get('/product/erp_reason/{type}', 'productController@erp_reason');
    Route::get('/product/erp_reason_manager/{type}', 'productController@erp_reason_manager');
    Route::get('/product/erp_diff_manager_export', 'productController@erp_diff_manager_export');
    Route::get('/product/erp_negetive_manager', 'productController@erp_negetive_manager');
    Route::post('/product/datewiseerpmanager', 'productController@datewiseerpmanager');
    Route::post('/update-erp-sku-manager', 'productController@updateErpSkuManager');
    Route::post('/product/importLessErpCSVManager', 'productController@importLessErpCSVManager');
    Route::post('/product/importAddErpCSVManager', 'productController@importAddErpCSVManager');
    Route::post('/product/importAdd2ErpCSVManager', 'productController@importAdd2ErpCSVManager');
    Route::get('/product/export_erp_manager', 'productController@export_erp_manager');
    Route::get('/view-erp-history/{id}', 'productController@view_erp_history');
    Route::get('/view-erp-history-manager/{id}', 'productController@view_erp_history_manager');
    Route::post('/product/reason-filter/', 'productController@reason_filter');

    Route::get('/erp-us-manager', 'productController@erpUsManager');
    Route::get('/product/erp_us_diff_manager/{type}', 'productController@erp_us_diff_manager');
    Route::get('/product/erp_us_reason/{type}', 'productController@erp_us_reason');
    Route::get('/product/erp_us_reason_manager/{type}', 'productController@erp_us_reason_manager');
    Route::get('/product/erp_us_diff_manager_export', 'productController@erp_us_diff_manager_export');
    Route::get('/product/erp_us_negetive_manager', 'productController@erp_us_negetive_manager');
    Route::post('/product/datewiseerpusmanager', 'productController@datewiseerpusmanager');
    Route::post('/update-erp-us-sku-manager', 'productController@updateErpUsSkuManager');
    Route::post('/product/importLessErpUsCSVManager', 'productController@importLessErpUsCSVManager');
    Route::post('/product/importAddErpUsCSVManager', 'productController@importAddErpUsCSVManager');
    Route::post('/product/importAdd2ErpUsCSVManager', 'productController@importAdd2ErpUsCSVManager');
    Route::get('/product/export_erp_us_manager', 'productController@export_erp_us_manager');
    Route::get('/view-erp-us-history/{id}', 'productController@view_erp_us_history');
    Route::get('/view-erp-us-history-manager/{id}', 'productController@view_erp_us_history_manager');
    Route::post('/product/reason-filter-us/', 'productController@reason_filter_us');

    Route::get('/erp-eu-manager', 'productController@erpEuManager');
    Route::get('/product/erp_eu_diff_manager/{type}', 'productController@erp_eu_diff_manager');
    Route::get('/product/erp_eu_reason/{type}', 'productController@erp_eu_reason');
    Route::get('/product/erp_eu_reason_manager/{type}', 'productController@erp_eu_reason_manager');
    Route::get('/product/erp_eu_diff_manager_export', 'productController@erp_eu_diff_manager_export');
    Route::get('/product/erp_eu_negetive_manager', 'productController@erp_eu_negetive_manager');
    Route::post('/product/datewiseerpeumanager', 'productController@datewiseerpeumanager');
    Route::post('/update-erp-eu-sku-manager', 'productController@updateErpEuSkuManager');
    Route::post('/product/importLessErpEuCSVManager', 'productController@importLessErpEuCSVManager');
    Route::post('/product/importAddErpEuCSVManager', 'productController@importAddErpEuCSVManager');
    Route::post('/product/importAdd2ErpEuCSVManager', 'productController@importAdd2ErpEuCSVManager');
    Route::get('/product/export_erp_eu_manager', 'productController@export_erp_eu_manager');
    Route::get('/view-erp-eu-history/{id}', 'productController@view_erp_eu_history');
    Route::get('/view-erp-eu-history-manager/{id}', 'productController@view_erp_eu_history_manager');
    Route::post('/product/reason-filter-eu/', 'productController@reason_filter_eu');

    Route::get('/form', function () {
        return view('form');
    });

    // Route::resource('transport_logs', TransportLogController::class);
    // Route::resource('production_emissions', ProductionEmissionController::class);
    // Route::get('consumptions', "ProductionEmissionController@consumption")->name("admin.production_emissions.consumption");
    // Route::post('store_production_emission', "ProductionEmissionController@store_production_emission")->name("admin.production_emission.store");
    // Route::get('transportlogs', 'TransportLogController@adminTransportLogs')->name('admin.transportlogs');
    // Route::post('/admin/transportlogs/save', 'transportlogcontroller@storeadmin')->name('admin.transportlogs.save');

   
   Route::get('ProductSuppliers', 'ProductSupplierController@index')->name('ProductSuppliers.index');
   Route::get('assigened_products', 'ProductSupplierController@supplier_products')->name('ProductSuppliers.assigned');
   Route::post('/supplier/revise-price', [ProductSupplierController::class, 'revisePrice'])->name('supplier.revise-price');
   Route::post('/supplier/approve-price', [ProductSupplierController::class, 'approvePrice'])->name('supplier.approveProduct');
   
// Display a list of all assignments


// Show the form for creating a new assignment
Route::get('/ProductSuppliers/create', [ProductSupplierController::class, 'create'])->name('ProductSuppliers.create');

// Store a newly created assignment in storage
Route::post('/ProductSuppliers', [ProductSupplierController::class, 'store'])->name('ProductSuppliers.store');

// Display a specific assignment
Route::get('/ProductSuppliers/{assignment}', [ProductSupplierController::class, 'show'])->name('ProductSuppliers.show');

// Show the form for editing a specific assignment
Route::get('/ProductSuppliers/{assignment}/edit', [ProductSupplierController::class, 'edit'])->name('ProductSuppliers.edit');

// Update a specific assignment in storage
Route::put('/ProductSuppliers/{assignment}', [ProductSupplierController::class, 'update'])->name('ProductSuppliers.update');

// Delete a specific assignment from storage
Route::delete('/ProductSuppliers/{assignment}', [ProductSupplierController::class, 'destroy'])->name('ProductSuppliers.destroy');

// Route::get('/artisan_emissions', [ArtisanEmissionController::class, 'index'])->name('artisan_emissions.index'); // Display the listing of emissions
// Route::post('/artisan_emissions', [ArtisanEmissionController::class, 'store'])->name('artisan_emissions.store'); // Store a new emission record
// Route::get('/artisan_emissions/{id}/edit', [ArtisanEmissionController::class, 'edit'])->name('artisan_emissions.edit'); // Show the form for editing a record
// Route::put('/artisan_emissions/{id}', [ArtisanEmissionController::class, 'update'])->name('artisan_emissions.update'); // Update a specific emission record
// Route::delete('/artisan_emissions/{id}', [ArtisanEmissionController::class, 'destroy'])->name('artisan_emissions.destroy'); // Delete a specific emission record

});

Route::group(['middleware' => ['permission:category-read','2fa']], function () {

    //For Category
    Route::get('/category', 'categoryController@index');

    Route::get('/product_ledger', 'productLedgerController@index');

    //For Sub-Category
    Route::get('/category/subCategory', 'categoryController@subCategoryIndex');
});

Route::group(['middleware' => ['permission:category-edit','2fa']], function () {
    //For Category
    Route::get('/category/create', 'categoryController@create');
    Route::post('/category/create', 'categoryController@store');
    Route::get('/category/view/{id}', 'categoryController@view');
    Route::post('/category/delete/{id}', 'categoryController@delete');
    Route::post('/category/view/{id}', 'categoryController@update');

    //product ledger
    Route::get('/product_ledger/create', 'productLedgerController@create');
    Route::post('/product_ledger/create', 'productLedgerController@store');
    Route::get('/product_ledger/view/{id}', 'productLedgerController@view');
    Route::post('/product_ledger/delete/{id}', 'productLedgerController@delete');
    Route::post('/product_ledger/view/{id}', 'productLedgerController@update');

    //For Sub-Category
    Route::get('/category/subCategory/create', 'categoryController@subCategoryCreate');
    Route::post('/category/subCategory/create', 'categoryController@subCategoryStore');
    Route::get('/category/subCategory/view/{id}', 'categoryController@subCategoryView');
    Route::post('/category/subCategory/delete/{id}', 'categoryController@subCategoryDelete');
    Route::post('/category/subCategory/view/{id}', 'categoryController@subCategoryUpdate');
});

Route::group(['middleware' => ['permission:product-read','2fa']], function () {
    Route::get('/product', 'productController@index');
    Route::get('/product/batch/create', 'productController@batchcreate');
    Route::get('/product/hardwareProduct', 'productController@hardwareProduct');
    Route::get('/FinshingExtraPrice', 'productController@FinshingExtraPrice');
    Route::get('/FinshingExtraPrice/create', 'productController@FinshingExtraPricecreate');
    Route::post('/FinshingExtraPrice/store', 'productController@FinshingExtraPricestore');
    Route::get('/FinshingExtraPrice/edit/{id}', 'productController@FinshingExtraPriceedit');
    Route::post('/FinshingExtraPrice/update', 'productController@FinshingExtraPriceupdate');
    Route::post('/FinshingExtraPrice/delete/{id}', 'productController@FinshingExtraPricedelete');

    Route::get('/service/product/view', 'ServiceProductController@index');
        Route::get('/service/create/product', 'ServiceProductController@create');
        Route::post('/service/product/create', 'ServiceProductController@store');
    
    Route::get('/finishing_rates', 'productController@finishing_rates');
    Route::get('/finishing_rates/add', 'productController@addfinishing_rates');
    Route::get('/finishing_rates/view/{id}', 'productController@viewfinishing_rates');
    Route::post('/finishing_rates/create', 'productController@storefinishing_rates');
    Route::put('/finishing_rates/update/{id}','productController@updatefinishing_rates');
    Route::delete('/finishing_rates/delete/{id}','productController@deletefinishing_rates');
    
    Route::get('/consumables', 'consumableController@index');
    Route::get('/temporaryProduct', 'tempProductController@index');
    Route::post('/product/printList', 'productController@printList');
    Route::post('/product/exportCodeWiseExcel', 'productController@exportCodeWiseExcel');
    Route::post('/product/exportIR', 'productController@exportIR');
    Route::post('/product/inventory_report_pdf', 'productController@inventory_report_pdf');
    Route::post('/product/exportValuationExcel', 'productController@exportValuationExcel');
    Route::get('/product/inventory_report', 'productController@inventory_report');
    Route::post('/product/downloadProductAnnualReport', 'productController@downloadProductAnnualReport');
    Route::post('/product/downloadDateWiseReport', 'productController@downloadDateWiseReport');
    Route::get('/product/valuation', 'productController@valuation');
    Route::get('/consumable/valuation', 'ConsumableValuationController@valuation');
    Route::post('/consumable/valuationPdfView', 'ConsumableValuationController@valuationPdfView');
    Route::post('/consumable/exportValuationExcel', 'ConsumableValuationController@exportValuationExcel');
    Route::get('/product/product_age', 'productController@product_age');
    Route::post('/product/valuationPdfView', 'productController@valuationPdfView');
    Route::post('/product/importGrouping', 'productController@importGrouping');
    Route::post('/product/createGrouping', 'productController@createGrouping');
    Route::get('/product/families', 'productController@families');
    Route::get('/product/batch', 'productController@downloadbatch');
    Route::get('/product/batch/qty', 'productController@downloadbatchqty');
    Route::get('/product/legs', 'productController@legs');
    Route::post('/product/leg_create', 'productController@leg_create');
    Route::post('/product/leg_update', 'productController@leg_update');
    Route::post('/product/leg_delete/{id}', 'productController@leg_delete');
    Route::post('/product/importLegsCSV', 'productController@importLegsCSV');
    Route::post('/product/importCSVcarton', 'productController@importCSVcarton');
    Route::get('/product/carton-import-template', 'productController@downloadCartonImportTemplate');
    Route::get('/product/product-weight-lbs-import-template', 'productController@downloadProductWeightLbsImportTemplate');
    Route::post('/product/batchimportCSV', 'productController@batchimportCSV');
    Route::post('/product/delete_family/{child_id}/{parent_id}', 'productController@delete_family');

    Route::get('/product/erp', 'productController@erp');
    Route::get('/product/erp_diff/{type}', 'productController@erp_diff');
    Route::get('/product/erp_negetive', 'productController@erp_negetive');
    Route::post('/update-erp-sku', 'productController@updateErpSku');
    Route::post('/product/importLessErpCSV', 'productController@importLessErpCSV');
    Route::post('/product/importAddErpCSV', 'productController@importAddErpCSV');
    Route::get('/product/export_erp', 'productController@export_erp');
    Route::post('/product/datewiseerp', 'productController@datewiseerp');

   
    Route::get('/product/erp_us_diff/{type}', 'productController@erp_us_diff');
    Route::get('/product/erp_us_negetive', 'productController@erp_us_negetive');
    Route::post('/update-erp-us-sku', 'productController@updateErpUsSku');
    Route::post('/product/importLessErpUsCSV', 'productController@importLessErpUsCSV');
    Route::post('/product/importAddErpUsCSV', 'productController@importAddErpUsCSV');
    Route::get('/product/export_erp_us', 'productController@export_erp_us');
    Route::post('/product/datewiseerpus', 'productController@datewiseerpus');

    
    Route::get('/product/erp_eu_diff/{type}', 'productController@erp_eu_diff');
    Route::get('/product/erp_eu_negetive', 'productController@erp_eu_negetive');
    Route::post('/update-erp-eu-sku', 'productController@updateErpEuSku');
    Route::post('/product/importLessErpEuCSV', 'productController@importLessErpEuCSV');
    Route::post('/product/importAddErpEuCSV', 'productController@importAddErpEuCSV');
    Route::get('/product/export_erp_eu', 'productController@export_erp_eu');
    Route::post('/product/datewiseerpeu', 'productController@datewiseerpeu');

    //For consumables
    Route::get('/consumables/create', 'consumableController@create');
    Route::post('/consumables/create', 'consumableController@store');
  
      Route::get('/unitType', 'consumableController@unitType');
    Route::get('/unitType/create', 'consumableController@unitTypeCreate');
    Route::post('/unitType/create', 'consumableController@storeunitType');
    Route::get('/unitType/view/{id}', 'consumableController@unitTypeview');
    Route::put('/unitType/update/{id}', 'consumableController@updateunitType');
    Route::post('/unitType/delete/{id}', 'consumableController@deleteunitType');


    Route::post('/consumables/importConsumable', 'consumableController@importConsumable');
    Route::get('/consumables/import-report/download/{fileName}', 'consumableController@downloadImportReport');
    Route::post('/consumables/importUpdateUnitType', 'consumableController@importUpdateUnitType');
    Route::post('/consumables/importUpdateSKU', 'consumableController@importUpdateSKU');
    Route::post('/consumables/delete/{id}', 'consumableController@delete');
    Route::get('/consumables/view/{id}', 'consumableController@view');
    Route::post('/consumables/view/{id}', 'consumableController@update');
    Route::get('/consumables/exportcsv', 'consumableController@consumables_exportcsv');

    //For FullFillment
    Route::get('/fulfillment/logs/', 'FulFillmentController@fulFillmentLogs');
    Route::get('/fulfillment/list', 'FulFillmentController@fulFillmentList');
    Route::get('/export-fulfillment', 'FulFillmentController@exportFulFillment');
    Route::get('/export/fulfillmentLogs', 'FulFillmentController@exportFulFillmentLogs');
    Route::get('/export/ErpOverallStock', 'FulFillmentController@ErpOverallStock');
    Route::get('/export/ErpOverallStock', 'FulFillmentController@ErpOverallStock');
    Route::post('/fulfillment/fulfillment-order-manually', 'FulFillmentController@addManualFulfillment');
    Route::get('/update-finishing-prices', 'productController@updateFinishingPrices');
    Route::get('/update-contractor-bill-finishing', 'productController@updateContractorBillFinishing');
    Route::get('/export-finishing-price-recalc', 'productController@exportFinishingPriceRecalcAndApply');
});





Route::group(['middleware' => ['permission:product-edit','2fa']], function () {
    //For Products
    Route::get('/product/create', 'productController@create');
    Route::post('/product/create', 'productController@store');
    Route::post('/product/delete/{id}', 'productController@delete');
    Route::get('/product/view/{id}', 'productController@view');
    Route::post('/product/view/{id}', 'productController@update');
    // Route::get('/product/exportpdf', 'productController@exportpdf');

    Route::get('/service/product/view/{id}', 'ServiceProductController@view');
    Route::put('/service/product/update/{id}', 'ServiceProductController@update');
    Route::post('/service/product/delete/{id}', 'ServiceProductController@delete');
    Route::get('/service/categories', 'ServiceProductController@serviceCategories');
    Route::get('/service/categoriesindex', 'ServiceProductController@serviceCategoriesindex');
    Route::post('/service/categories', 'ServiceProductController@stroeServiceCategories');
    Route::get('/service/category/view/{id}', 'ServiceProductController@serviceCategoriesView');
    Route::put('/service/categories/{id}', 'ServiceProductController@serviceCategoriesUpdate');
    Route::post('/service/categories/delete/{id}', 'ServiceProductController@serviceCategoriesDelete');

    Route::get('/product/exportcsv', 'productController@exportcsv');
    // Route::post('/product/exportcsv', 'productController@exportcsv');
       Route::get('/product/exportcsv/Consumable', 'productController@exportcsvConsumable');
    Route::get('/product/smallhardware', 'productController@exportSmallHardware');

    Route::post('/product/importCSV', 'productController@importCSV');
    Route::get('/product/consumable-import-template', 'productController@downloadConsumableImportTemplate');
    Route::post('/product/ConsumableimportCSV', 'productController@ConsumableimportCSV');
    Route::post('/product/importCSVfinishing', 'productController@importCSVfinishing');
    Route::post('/product/ProductWeightLbsImport/importCSV', 'productController@ProductWeightLbsImport');
    Route::post('/product/updateLegs', 'productController@updateLegs');

    Route::post('/product/downloadInReport', 'productController@downloadInReport');
    Route::post('/product/downloadOutReport', 'productController@downloadOutReport');
    //For Temporary Products
    Route::get('/temporaryProduct/create', 'tempProductController@create');
    Route::post('/temporaryProduct/create', 'tempProductController@store');
    Route::get('/temporaryProduct/view/{id}', 'tempProductController@view');
    Route::post('/temporaryProduct/view/{id}', 'tempProductController@update');
    Route::get('/temporaryProduct/delete/{id}', 'tempProductController@delete');
});

Route::group(['middleware' => ['permission:quality-read','2fa']], function () {
    Route::get('/quality/create', 'qualityController@create');
    Route::post('/quality/create', 'qualityController@store');
    Route::get('/quality/complete/{id}', 'qualityController@complete');
    Route::post('/quality/delete/{id}', 'qualityController@delete');
    Route::get('/quality', 'qualityController@index');
    Route::get('/quality/download', 'qualityController@download');
    Route::get('/quality/view/{id}', 'qualityController@view');
    Route::post('/quality/view/{id}', 'qualityController@update');
});

Route::group(['middleware' => ['permission:buyer-read','2fa']], function () {

    Route::get('/buyer', 'buyerController@index');
    Route::get('/buyer_uk', 'buyerController@index_uk');
    Route::get('/buyer_us', 'buyerController@index_us');
    Route::get('/buyer_eu', 'buyerController@index_eu');
    Route::get('/buyer_canada', 'buyerController@index_canada');
    Route::get('/buyer_california', 'buyerController@index_california');
    Route::get('/temporaryBuyer', 'tempBuyerController@index');
});

Route::group(['middleware' => ['permission:buyer-edit','2fa']], function () {

    Route::get('/buyer/create', 'buyerController@create');
    Route::post('/buyer/create', 'buyerController@store');
    Route::get('/buyer/view/{id}', 'buyerController@view');
    Route::post('/buyer/delete/{id}', 'buyerController@delete');
    Route::post('/buyer/view/{id}', 'buyerController@update');
    
    Route::get('/temporaryBuyer/create', 'tempBuyerController@create');
    Route::post('/temporaryBuyer/create', 'tempBuyerController@store');
    Route::get('temporaryBuyer/view/{id}', 'tempBuyerController@view');
    Route::post('temporaryBuyer/view/{id}', 'tempBuyerController@update');
    Route::get('temporaryBuyer/delete/{id}', 'tempBuyerController@delete');

// Route::get('/buyer/bulk-buyer-create', 'BulkBuyerController@create_bulk_buyer');
Route::get('/buyer/create-bulk-pricing', 'BulkBuyerController@create_bulk_buyer');
Route::post('/buyer/bulk-buyer-update', 'BulkBuyerController@update_bulk_buyer');
Route::get('/buyer/create-bulk-all-cost-input', 'BulkBuyerController@create_bulk_all_cost_input');
Route::post('/buyer/bulk-all-cost-input-update', 'BulkBuyerController@update_bulk_all_cost_input');
Route::get('/buyer/get-product-by-buyer', 'BulkBuyerController@getProductByBuyer');
Route::get('/buyer/get-source-product-pricing', 'BulkBuyerController@getSourceProductPricing');
Route::get('/buyer/get-source-buyer', 'BulkBuyerController@getSourceBuyer');
Route::get('/buyer/check-bulk-destination-duplicates', 'BulkBuyerController@checkBulkDestinationDuplicates');
});
Route::group(['middleware' => ['permission:supplier-read','2fa']], function () {
     Route::get('/supplier-terms/view', [SupplierTermsController::class, 'showSupplier'])->name('admin.supplier_terms.view');
     Route::get('/supplier-terms/acceptance-status', [SupplierTermsController::class, 'adminSupplierAcceptanceStatus'])
        ->name('admin.supplier_terms.acceptance_status');
    Route::get('/supplier', 'supplierController@index');
    Route::get('/supplier/pricing', 'supplierController@pricingSupp');
    Route::get('/supplier/pricing/history', 'supplierController@pricingHistory');
    Route::get('/supplier/pricing/history/{productId}/{supplierId}', 'supplierController@pricingHistoryDetail');
    Route::get('/supplier/pricing/export/excel', 'supplierController@pricingExportExcel');
    Route::get('/supplier/pricing/export/csv', 'supplierController@pricingExportCsv');
    Route::get('/supplier/pricing/export/pdf', 'supplierController@pricingExportPdf');
    Route::get('/supplier/pricing/import-template', 'supplierController@pricingImportTemplate');
    Route::get('/supplier/data', 'supplierController@data');
    Route::get('/smhsupplier', 'smhsupplierController@index');
    Route::get('/smhsupplier/data', 'smhsupplierController@data');
});

Route::group(['middleware' => ['permission:supplier-edit','2fa']], function () {
        Route::get('/supplier-terms/edit', [SupplierTermsController::class, 'edit'])->name('admin.supplier_terms.edit');
    Route::post('/supplier-terms', [SupplierTermsController::class, 'update'])->name('admin.supplier_terms.update');
    Route::get('/supplier/create', 'supplierController@create');
    Route::post('/supplier/create', 'supplierController@store');
    Route::get('/supplier/view/{id}', 'supplierController@view');
    Route::post('/supplier/delete/{id}', 'supplierController@delete');
    Route::post('/supplier/view/{id}', 'supplierController@update');
    Route::post('/supplier/importCSV', 'supplierController@importCSV');
    Route::post('/supplier/pricing/import', 'supplierController@pricingImport');
    Route::get('/smhsupplier/create', 'smhsupplierController@create');
    Route::post('/smhsupplier/create', 'smhsupplierController@store');
    Route::get('/smhsupplier/view/{id}', 'smhsupplierController@view');
    Route::post('/smhsupplier/delete/{id}', 'smhsupplierController@delete');
    Route::post('/smhsupplier/view/{id}', 'smhsupplierController@update');
    Route::post('/smhsupplier/importCSV', 'smhsupplierController@importCSV');
});

Route::group(['middleware' => ['permission:contractor-read','2fa']], function () {
    Route::get('/contractor', 'contractorController@index');
    Route::get('/contractor/data', 'contractorController@data');
});

Route::group(['middleware' => ['permission:contractor-edit','2fa']], function () {
    Route::get('/contractor/create', 'contractorController@create');
    Route::post('/contractor/create', 'contractorController@store');
    Route::get('/contractor/view/{id}', 'contractorController@view');
    Route::post('/contractor/delete/{id}', 'contractorController@delete');
    Route::post('/contractor/view/{id}', 'contractorController@update');
    Route::post('/contractor/downloadBill', 'contractorController@downloadBill');
});

Route::group(['middleware' => ['permission:performance-edit','2fa']], function () {
    Route::get('/performanceCards', 'performanceCardsController@index');
    Route::get('/performanceCards/create', 'performanceCardsController@create');
    Route::post('/performanceCards/create', 'performanceCardsController@store');
    Route::get('/performanceCards/view/{id}', 'performanceCardsController@view');
    Route::post('/performanceCards/view/{id}', 'performanceCardsController@update');
    Route::post('/performanceCards/delete/{id}', 'performanceCardsController@delete');
    Route::get('/performanceCards/detail/{id}', 'performanceCardsController@detail');
    Route::get('/performanceCards/modal/{id}', 'performanceCardsController@modal');
});

Route::group(['middleware' => ['permission:allocation-read','2fa']], function () {
    Route::get('/allocation', 'allocationController@index');
});

Route::group(['middleware' => ['permission:allocation-edit','2fa']], function () {
    Route::get('/allocation/create', 'allocationController@create');
    Route::post('/allocation/create', 'allocationController@store');
    Route::get('/allocation/view/{id}', 'allocationController@view');
    Route::get('/allocation/delete/{id}', 'allocationController@delete');
    Route::post('/allocation/view/{id}', 'allocationController@update');
});

Route::group(['middleware' => ['permission:po-read','2fa']], function () {
    Route::get('/purchaseOrder', 'purchaseOrderController@index');

    Route::get('/purchaseOrder/service', 'purchaseOrderController@indexservice')->name('purchaseOrder.index');
    Route::get('/purchaseOrder/service/modal/{id}', 'purchaseOrderController@servicemodal');

    Route::get('/purchaseOrder/modalConsumablePo/month/{id}', 'purchaseOrderController@modalMonthEnd');

    Route::get('/purchaseOrder/modal/{id}', 'purchaseOrderController@modal');
    Route::get('/purchaseOrder/version-history/{id}', 'purchaseOrderController@poVersionHistory');
    Route::post('/purchaseOrder/exportcsv', 'purchaseOrderController@exportcsv');
    Route::get('/purchaseOrder/consumablePos', 'purchaseOrderController@consumablePos');
    Route::get('/purchaseOrder/cartonPos', 'purchaseOrderController@cartonPos');
    Route::get('/purchaseOrder/modalConsumablePo/{id}', 'purchaseOrderController@modalConsumablePo');
    Route::get('/purchaseOrder/modalCartonPo/{id}', 'purchaseOrderController@modalCartonPo');
    Route::get('/purchaseOrder/modalRecommendedPurchaseOrder/{id}', 'purchaseOrderController@modalRecommendedPurchaseOrder');
    Route::get('/draftPurchaseOrder', 'DraftPurchaseOrderController@index');
    Route::get('/draftPurchaseOrder/modal/{id}', 'DraftPurchaseOrderController@modal');
    Route::get('/draftConsumablePurchaseOrder', 'DraftConsumablePurchaseOrderController@index');
    Route::get('/draftConsumablePurchaseOrder/modal/{id}', 'DraftConsumablePurchaseOrderController@modal');
    Route::post('/purchaseOrder/poTallyExport', 'purchaseOrderController@poTallyExport');
    Route::get('/samplePurchaseOrder', 'purchaseOrderController@samplePurchaseOrder');
    Route::get('/purchaseOrder/modalSample/{id}', 'purchaseOrderController@modalSample');
    Route::post('/purchaseOrder/exportcsvsample', 'purchaseOrderController@exportcsvsample');
    Route::post('/purchaseOrder/poTallyExportSample', 'purchaseOrderController@poTallyExportSample');
});

Route::group(['middleware' => ['permission:po-edit','2fa']], function () {
    Route::get('/purchaseOrder/create', 'purchaseOrderController@create');

    Route::get('/purchaseOrder/create/service', 'purchaseOrderController@createservice');
    Route::post('/purchaseOrder/create/service', 'purchaseOrderController@storeService');

    Route::get('/service/product', 'purchaseOrderController@serviceproduct');

  

    Route::get('/purchaseOrder/service/view/{id}', 'purchaseOrderController@viewservice');
    Route::post('/purchaseOrder/service/view/{id}', 'purchaseOrderController@updateServicepo');
    Route::post('/purchaseOrder/service/updateStatus/{id}', 'purchaseOrderController@update_statusService');
    Route::post('/purchaseOrder/service/cancel/{id}', 'purchaseOrderController@cancelService');

    Route::get('/purchaseOrder/view/{id}', 'purchaseOrderController@view');
    Route::post('/purchaseOrder/view/{id}', 'purchaseOrderController@updatepo');
    Route::post('/purchaseOrder/create', 'purchaseOrderController@store');
    Route::get('/purchaseOrder/data', 'purchaseOrderController@data');
    Route::get('/purchaseOrder/spdata', 'purchaseOrderController@spdata');
    Route::get('/purchaseOrder/cdata', 'purchaseOrderController@cdata');
    Route::get('/purchaseOrder/supplierPoLimitStatus', 'purchaseOrderController@supplierPoLimitStatus');
    Route::post('/purchaseOrder/{id}/send-to-supplier', 'purchaseOrderController@sendFurniturePoToSupplier');
    Route::post('/purchaseOrder/consumable/{id}/send-to-supplier', 'purchaseOrderController@sendConsumablePoToSupplier');
    Route::post('/purchaseOrder/service/{id}/send-to-supplier', 'purchaseOrderController@sendServicePoToSupplier');
    Route::post('/purchaseOrder/sample/{id}/send-to-supplier', 'purchaseOrderController@sendSamplePoToSupplier');
    Route::get('/draftPurchaseOrder/create', 'DraftPurchaseOrderController@create');
    Route::post('/draftPurchaseOrder/create', 'DraftPurchaseOrderController@store');
    Route::get('/draftPurchaseOrder/edit/{id}', 'DraftPurchaseOrderController@edit');
    Route::post('/draftPurchaseOrder/edit/{id}', 'DraftPurchaseOrderController@update');
    Route::get('/draftPurchaseOrder/supplierMonthlyDraftAmounts', 'DraftPurchaseOrderController@supplierMonthlyDraftAmounts');
    Route::post('/draftPurchaseOrder/{id}/send-to-supplier', 'DraftPurchaseOrderController@sendToSupplier');
    Route::get('/draftPurchaseOrder/{id}/convert', 'DraftPurchaseOrderController@convert');
    Route::delete('/draftPurchaseOrder/delete/{id}', 'DraftPurchaseOrderController@destroy');
    Route::get('/draftConsumablePurchaseOrder/create', 'DraftConsumablePurchaseOrderController@create');
    Route::post('/draftConsumablePurchaseOrder/create', 'DraftConsumablePurchaseOrderController@store');
    Route::get('/draftConsumablePurchaseOrder/edit/{id}', 'DraftConsumablePurchaseOrderController@edit');
    Route::post('/draftConsumablePurchaseOrder/edit/{id}', 'DraftConsumablePurchaseOrderController@update');
    Route::post('/draftConsumablePurchaseOrder/{id}/send-to-supplier', 'DraftConsumablePurchaseOrderController@sendToSupplier');
    Route::get('/draftConsumablePurchaseOrder/{id}/convert', 'DraftConsumablePurchaseOrderController@convert');
    Route::delete('/draftConsumablePurchaseOrder/delete/{id}', 'DraftConsumablePurchaseOrderController@destroy');
     Route::get('/purchaseOrder/cunitType', 'purchaseOrderController@cunitType');
    Route::get('/purchaseOrder/pdata', 'purchaseOrderController@pdata');
    Route::post('/purchaseOrder/calculate_box', 'purchaseOrderController@calculate_box');
    Route::get('/purchaseOrder/contractor_data', 'purchaseOrderController@contractor_data');
    //Route::post('/purchaseOrder/delete/{id}', 'purchaseOrderController@delete');
    Route::post('/purchaseOrder/cancel/{id}', 'purchaseOrderController@cancel');
    Route::delete('/purchaseOrder/delete/{id}', 'purchaseOrderController@destroy')->name('purchaseOrder.delete');
    Route::post('/purchaseOrder/ConsumablePo/cancel/{id}', 'purchaseOrderController@cancelConsumablePo');
    Route::delete('/purchaseOrder/ConsumablePo/delete/{id}', 'purchaseOrderController@deleteConsumablePo')->name('purchaseOrder.ConsumablePo.delete');
    Route::post('/purchaseOrder/updateStatus/{id}', 'purchaseOrderController@update_status');
    Route::get('/purchaseOrder/createSamplePo', 'purchaseOrderController@createSamplePo');
    Route::get('/purchaseOrder/sampleData', 'purchaseOrderController@sampleData');
    Route::post('/purchaseOrder/createSamplePo', 'purchaseOrderController@storeSample');
    Route::get('/purchaseOrder/viewSample/{id}', 'purchaseOrderController@viewSample');
    Route::post('/purchaseOrder/viewSample/{id}', 'purchaseOrderController@updatepos');
    Route::post('/purchaseOrder/deleteSample/{id}', 'purchaseOrderController@deleteSample');

    Route::post('/purchaseOrder/deleteConsumablePo/{id}', 'purchaseOrderController@deleteConsumablePo');
    Route::post('/purchaseOrder/updateConsumablePo/{id}', 'purchaseOrderController@updateConsumablePo');
    Route::get('/purchaseOrder/createConsumablePo', 'purchaseOrderController@createConsumablePo');
    Route::post('/purchaseOrder/createConsumablePo', 'purchaseOrderController@storeConsumablePo');
    Route::get('/purchaseOrder/viewConsumablePo/{id}', 'purchaseOrderController@viewConsumablePo');
    Route::post('/purchaseOrder/viewConsumablePo/{id}', 'purchaseOrderController@updateConsumablePo');

        Route::get('/purchaseOrder/viewConsumablePo/monthend/{id}', 'purchaseOrderController@viewConsumablePomonthend');
    Route::post('/purchaseOrder/viewConsumablePo/monthend/{id}', 'purchaseOrderController@viewConsumablePomonthendUpdate');

    Route::post('/purchaseOrder/deleteCartonPo/{id}', 'purchaseOrderController@deleteCartonPo');
    Route::post('/purchaseOrder/updateCartonPo/{id}', 'purchaseOrderController@updateCartonPo');
    Route::get('/purchaseOrder/createCartonPo', 'purchaseOrderController@createCartonPo');
    Route::post('/purchaseOrder/createCartonPo', 'purchaseOrderController@storeCartonPo');
    Route::get('/purchaseOrder/viewCartonPo/{id}', 'purchaseOrderController@viewCartonPo');
    Route::post('/purchaseOrder/viewCartonPo/{id}', 'purchaseOrderController@updateCartonPo');

    Route::get('/purchaseOrder/recommendedPurchaseOrder', 'purchaseOrderController@recommendedPurchaseOrder');
    Route::post('/purchaseOrder/deleteRecommendedPo/{id}', 'purchaseOrderController@deleteRecommendedPo');
    Route::post('/purchaseOrder/createRecommendedPo/{id}', 'purchaseOrderController@storeRecommendedPo');
    Route::get('/purchaseOrder/viewRecommendedPo/{id}', 'purchaseOrderController@viewRecommendedPo');
    Route::post('/purchaseOrder/viewRecommendedPo/{id}', 'purchaseOrderController@updateRecommendedPo');
    // Route::get('/purchaseOrder/exportcsv', 'purchaseOrderController@exportcsv');

});


Route::group(['middleware' => ['permission:invoice-read','2fa']], function () {
    Route::get('/invoice', 'invoiceController@index')->name('invoice.index');
    Route::post('/invoice/cancel/{id}', 'invoiceController@cancel')->name('invoice.cancel');

    Route::post('/invoice/exportcsv', 'invoiceController@exportcsv');
    Route::get('/invoice/exportEachCSV/{id}', 'invoiceController@exportEachCSV');
    Route::get('/invoice/exportEachCSVuk/{id}', 'invoiceController@exportEachCSVuk');
    Route::get('/invoice/swapping', 'invoiceController@swapping');
    Route::get('/invoice/createswapping', 'invoiceController@createswapping');
    Route::post('/invoice/createswapping', 'invoiceController@storeswapping');
    Route::get('/carton-swapping', 'ConsumableValuationController@cartonSwapping');
    Route::get('/carton-swapping/create', 'ConsumableValuationController@createCartonSwapping');
    Route::post('/carton-swapping/create', 'ConsumableValuationController@storeCartonSwapping');
    Route::get('/invoice/data', 'invoiceController@data');
    Route::post('/invoice/updateShippingBill', 'invoiceController@updateShippingBill');
    Route::post('/invoice/exportsalesBillForm', 'invoiceController@exportsalesBillForm');
    Route::post('/invoice/updateInvoiceExport', 'invoiceController@updateInvoiceExport');
    Route::post('/invoice/updateTallyStatus/{id}', 'invoiceController@update_tally_status');
    Route::post('/invoice/exportSalesSheet', 'invoiceController@exportSalesSheet');
    Route::post('/invoice/einvoice', 'invoiceController@einvoice');
    Route::post('/invoice/email', 'invoiceController@email');
    Route::post('/invoice/create_uk', 'invoiceController@create_uk');
    Route::post('/invoice/store_uk/{id}', 'invoiceController@store_uk');
    Route::post('/invoice/create_us', 'invoiceController@create_us');
    Route::post('/invoice/store_us/{id}', 'invoiceController@store_us');
    Route::post('/invoice/create_eu', 'invoiceController@create_eu');
    Route::post('/invoice/store_eu/{id}', 'invoiceController@store_eu');

    Route::post('/invoice/create_canada', 'invoiceController@create_canada');
    Route::post('/invoice/store_canada/{id}', 'invoiceController@store_canada');

    Route::post('/invoice/create_california', 'invoiceController@create_california');
    Route::post('/invoice/store_california/{id}', 'invoiceController@store_california');

    Route::get('/supplierInvoice', 'supplierInvoiceController@index');
   
    Route::get('/supplierInvoice/multi', 'supplierInvoiceController@indexmulti');
    Route::get('/supplierInvoice/multi-consumable', 'AdminSupplierMultiPoController@indexConsumable');
    Route::get('/supplierInvoice/approve/multi-consumable/{id}', 'AdminSupplierMultiPoController@approveConsumable');
    Route::post('/supplierInvoice/approve/multi-consumable/{id}', 'AdminSupplierMultiPoController@storeApproveConsumable');
    Route::get('/supplierInvoice/multi-carton', 'AdminSupplierMultiPoController@indexCarton');
    Route::get('/supplierInvoice/approve/multi-carton/{id}', 'AdminSupplierMultiPoController@approveCarton');
    Route::post('/supplierInvoice/approve/multi-carton/{id}', 'AdminSupplierMultiPoController@storeApproveCarton');
   
       Route::get('/supplierInvoice/carton', 'supplierInvoiceController@indexcarton');

    Route::get('/supplierInvoice/data/subTable', 'supplierInvoiceController@subTable');

    Route::get('/supplier-service-Invoice', 'supplierInvoiceController@serviceIndex');
    Route::get('/supplierInvoice/service-modal/{id}', 'supplierInvoiceController@serviceModal');
    Route::get('/supplier-service-Invoice/approve/{id}', 'supplierInvoiceController@serviceApprove');
    Route::post('/supplier-service-Invoice/approve/{id}', 'supplierInvoiceController@store_service_approve');
    Route::get('/supplierInvoice/modal/carton/{id}', 'supplierInvoiceController@modalcarton');

    Route::get('/supplierInvoice/modal/{id}', 'supplierInvoiceController@modal');
      Route::get('/supplierInvoice/modal/multi/{id}', 'supplierInvoiceController@modalmulti');
    Route::post('/supplierInvoice/exportcsv', 'supplierInvoiceController@exportcsv');
    Route::get('/supplierInvoice/download/{id}', 'supplierInvoiceController@download');
    Route::get('/supplierInvoice/create/{id}/{type}', 'supplierInvoiceController@create');
    Route::post('/supplierInvoice/updateSupplierReturn', 'supplierInvoiceController@updateSupplierReturn');
    Route::post('/supplierInvoice/saveInvoiceReturn', 'supplierInvoiceController@saveInvoiceReturn');
    Route::get('/supplierInvoice/saveInvoiceReturn/{id}', 'supplierInvoiceController@saveInvoiceReturn');
    Route::post('/supplierInvoice/saveInvoiceReturn/{id}', 'supplierInvoiceController@updateReturn');
    Route::get('/supplierInvoice/approve/{id}', 'supplierInvoiceController@approve');
    Route::post('/supplierInvoice/approve/{id}', 'supplierInvoiceController@store_approve');
     Route::get('/supplierInvoice/approve/multi/{id}', 'supplierInvoiceController@approvemulti');
      Route::post('/supplierInvoice/approve/multi/{id}', 'supplierInvoiceController@store_approvemulti');

  Route::get('/supplierInvoice/approve/carton/{id}', 'supplierInvoiceController@approvecarton');
    Route::post('/supplierInvoice/approve/carton/{id}', 'supplierInvoiceController@store_approvecarton');


    Route::get('/supplierInvoice/carton/{id}/reverse', 'Carton\\CartonSupplierInvoiceReverseController@confirm');
    Route::post('/supplierInvoice/carton/{id}/reverse', 'Carton\\CartonSupplierInvoiceReverseController@store');

    Route::get('/invoices/invoice-uk', 'invoiceController@invoice_uk');
    Route::get('/getbatchesbyid/{id}', 'invoiceController@getbatchesbyid');

     Route::post('/supplier-invoice/batch-details','invoiceController@batchDetails')->name('supplier.invoice.batch.details');
     Route::post('/swapping/batch-details','invoiceController@swappingBatchDetails')->name('swapping.batch.details');
     Route::post('/supplier-invoice/get-supplier-invoices','invoiceController@getSupplierInvoices')
    ->name('supplier.invoice.get-supplier-invoices');

    Route::get('/invoice/delete-uk/{id}', 'invoiceController@delete_uk');

    Route::get('/invoice/view-uk/{id}', 'invoiceController@view_uk');
    Route::post('/invoice/view-uk/{id}', 'invoiceController@updateinvoiceUk');

    Route::get('/invoices/invoice-us', 'invoiceController@invoice_us');
    Route::get('/invoice/delete-us/{id}', 'invoiceController@delete_us');

    Route::get('/invoice/view-us/{id}', 'invoiceController@view_us');
    Route::post('/invoice/view-us/{id}', 'invoiceController@updateinvoiceUs');

    Route::get('/invoices/invoice-eu', 'invoiceController@invoice_eu');
    Route::get('/invoice/delete-eu/{id}', 'invoiceController@delete_eu');

    Route::get('/invoice/view-eu/{id}', 'invoiceController@view_eu');
    Route::post('/invoice/view-eu/{id}', 'invoiceController@updateinvoiceEu');

    Route::get('/invoices/invoice-canada', 'invoiceController@invoice_canada');
    Route::get('/invoice/delete-canada/{id}', 'invoiceController@delete_canada');
    
    Route::get('/invoice/view-canada/{id}', 'invoiceController@view_canada');
    Route::post('/invoice/view-canada/{id}', 'invoiceController@updateinvoicecanada');

    Route::get('/invoices/invoice-california', 'invoiceController@invoice_california');
    Route::get('/invoice/delete-california/{id}', 'invoiceController@delete_california');
    Route::get('/invoice/view-california/{id}', 'invoiceController@view_california');
    Route::post('/invoice/view-california/{id}', 'invoiceController@updateinvoicecalifornia');

    Route::get('/setting/setCountry/{country}', 'settingController@setCountry');

    Route::get('/invoice/credit_notes', 'invoiceController@credit_notes');
    Route::get('/invoice/credit_note_add', 'invoiceController@credit_note_add');
    Route::post('/invoice/credit_note_store', 'invoiceController@credit_note_store');
        Route::post('/invoice/credit_note/ref_snapshot/status', 'invoiceController@credit_note_ref_snapshot_status')->name('credit.note.ref_snapshot.status');
    Route::post('/invoice/credit_note/ref_snapshot/save', 'invoiceController@credit_note_ref_snapshot_save')->name('credit.note.ref_snapshot.save');
    Route::get('/invoice/credit_note_edit/{id}', 'invoiceController@credit_note_edit');
    Route::post('/invoice/credit_note_update/{id}', 'invoiceController@credit_note_update');
    Route::get('/invoice/credit_note_delete/{id}', 'invoiceController@credit_note_delete');
    Route::get('/invoice/credit_note_modal/{id}', 'invoiceController@credit_note_modal');
});

Route::group(['middleware' => ['permission:invoice-edit','2fa']], function () {
    Route::get('/invoice/create', 'invoiceController@create');
    Route::post('/invoice/create', 'invoiceController@store');
    Route::get('/invoice/view/{id}', 'invoiceController@view');
    Route::post('/invoice/delete/{id}', 'invoiceController@delete');
    Route::post('/invoice/view/{id}', 'invoiceController@updateinvoice');
    Route::get('/invoice/createinvoicesheet', 'invoiceController@createinvsheet');
    Route::post('/invoice/createinvoicesheet', 'invoiceController@createinvoicesheet');
    Route::post('/invoice/hardwarebill', 'invoiceController@hardwarebill');
    Route::post('/invoice/dustcoverbill', 'invoiceController@dustcoverbill');
    Route::post('/invoice/pouchbill', 'invoiceController@pouchbill');
    Route::post('/invoice/contractorbill', 'invoiceController@contractorbill');
    Route::get('/invoice/contractor-bill-finishing-dry-run', 'invoiceController@downloadContractorBillFinishingDryRun')->name('invoice.contractor_bill_finishing_dry_run');
    Route::get('/invoice/contractor-bill-finishing-apply', 'invoiceController@applyContractorBillFinishingDryRun')->name('invoice.contractor_bill_finishing_apply');
    Route::post('/invoice/createContractorBill', 'invoiceController@createContractorBill');
    Route::post('/invoice/createContractorBillbulk', 'invoiceController@createContractorBillbulk');
    Route::post('/invoice/upholsterybill', 'invoiceController@upholsterybill');
    Route::post('/invoice/createUpholsteryBill', 'invoiceController@createUpholsteryBill');
    Route::post('/invoice/downloadUpholstreyBill', 'invoiceController@downloadUpholstreyBill');
    Route::post('/invoice/cornerbill', 'invoiceController@cornerbill');
    Route::post('/invoice/cornerbillEdit', 'invoiceController@cornerbillEdit');
    Route::post('/invoice/updateCornerBill', 'invoiceController@updateCornerBill');
    Route::post('/invoice/hardwarebillfinal', 'invoiceController@hardwarebillfinal');
    Route::post('/invoice/dustcoverbillfinal', 'invoiceController@dustcoverbillfinal');
    Route::post('/invoice/smallhardwarebill', 'invoiceController@smallhardwarebill');
    Route::post('/invoice/smallhardwarebillfinal', 'invoiceController@smallhardwarebillfinal');
    Route::get('/monthendpo','invoiceController@viewmonthendpo');
Route::get('/invoice/monthEndPO', 'invoiceController@monthEndPO');

Route::post('/invoice/monthEndPO/create', 'invoiceController@monthEndPOcreate')->name('monthendpo.create');
Route::get('/invoice/hardwareMonthEndPO', 'invoiceController@hardwareMonthEndPO');
Route::post('/invoice/hardwareMonthEndPO/create', 'invoiceController@hardwareMonthEndPOcreate')->name('hardware.monthendpo.create');
Route::post('/invoice/hardwareMonthendsupplier', 'invoiceController@hardwareMonthendsupplier')->name('invoice.hardware.monthendsupplier');
      Route::post('/invoice/monthendsupplier', 'invoiceController@monthendsupplier')->name('invoice.monthendsupplier');;

    Route::get('/challan', 'invoiceController@challan');

    Route::get('/challan/modal/{id}', 'invoiceController@challanModal');
    Route::post('/challan/export-excel-csv', 'ChallanExcelExportController@export')->name('challan.export.excel.csv');

    Route::get('/monthendpo', 'invoiceController@viewmonthendpo');
    Route::get('/monthendpo/consumable', 'invoiceController@viewConsumbalemonthendpo');
    Route::delete('/purchaseOrder/ConsumablePo/monthend/{id}', 'purchaseOrderController@deleteConsumableMonthEndPo')->name('purchaseOrder.ConsumablePo.monthend.delete');


    Route::post('/invoice/pouchbillfinal', 'invoiceController@pouchbillfinal');
    Route::get('/invoice/sale-register', 'invoiceController@saleRegister');
    Route::post('/invoice/store_sale_register', 'invoiceController@store_sale_register');

    Route::post('/invoice/saleRegister', 'invoiceController@saleRegister_csv');

    // Route::get('/invoice/discharge', 'invoiceController@discharge');
    // Route::get('/invoice/discharge/create', 'invoiceController@dischargeCreate');
    // Route::post('/invoice/discharge/store', 'invoiceController@dischargeStore');
    // Route::delete('/invoice/discharge/delete/{id}', 'invoiceController@dischargeDelete');

});

Route::group(['middleware' => ['permission:settings-edit','2fa']], function () {
    Route::get('/setting', 'settingController@index');
    Route::get('/settings_uk', 'settingController@index_uk');
    Route::get('/settings_us', 'settingController@index_us');
    Route::get('/settings_eu', 'settingController@index_eu');
    Route::get('/settings_canada', 'settingController@index_canada');
    Route::get('/settings_california', 'settingController@index_california');
    Route::get('/setting/viewuser', 'settingController@viewuser');
    Route::get('/setting/delete/{id}', 'settingController@delete');
    Route::get('/setting/createuser', 'settingController@create');
    Route::post('/setting/createuser', 'settingController@storeuser');
    Route::post('/setting/updateEmail', 'settingController@updateEmail');
    Route::post('/setting/updateLogo', 'settingController@updateLogo');
     Route::post('/setting/updateSign', 'settingController@updateSign');
    Route::post('/setting/updateDetails', 'settingController@updateDetails');
    Route::post('/setting/updateDetailsUk', 'settingController@updateDetailsUk');
    Route::post('/setting/updateDetailsUs', 'settingController@updateDetailsUs');
    Route::post('/setting/updateDetailsEu', 'settingController@updateDetailsEu');
    Route::post('/setting/updateDetailsCanada', 'settingController@updateDetailsCanada');
    Route::post('/setting/updateDetailsCalifornia', 'settingController@updateDetailsCalifornia');
    Route::post('/setting/updatePassword', 'settingController@updatePassword');
    Route::post('/setting/updateCertificateDetails', 'settingController@updateCertificateDetails');
    Route::post('/setting/updateCertificateDetailsUk', 'settingController@updateCertificateDetailsUk');
    Route::post('/setting/updateCertificateDetailsUs', 'settingController@updateCertificateDetailsUs');
    Route::post('/setting/updateCertificateDetailsEu', 'settingController@updateCertificateDetailsEu');
    Route::post('/setting/updateCertificateDetailsCanada', 'settingController@updateCertificateDetailsCanada');
    Route::post('/setting/updateCertificateDetailsCalifornia', 'settingController@updateCertificateDetailsCalifornia');
    Route::post('/setting/updateUserPassword/{id}', 'settingController@updateUserPassword');
     Route::post('/setting/role/{id}', 'settingController@roleupdate');
    Route::post('/setting/updateUserPasswordAdmin/{id}', 'settingController@updatePasswordByAdmin');
     Route::post('/setting/updateLoginSecurity/{id}', 'settingController@updateLoginSecurity');
    Route::post('/setting/updatePricingSettings', 'settingController@updatePricingSettings');
    Route::post('/setting/updateUsaRates', 'settingController@updateUsaRates');
    Route::post('/setting/updateAustraliaRates', 'settingController@updateAustraliaRates');
    Route::post('/setting/updateEuRates', 'settingController@updateEuRates');
    Route::post('/setting/updateCanadaRates', 'settingController@updateCanadaRates');
    Route::post('/setting/updateCaliforniaRates', 'settingController@updateCaliforniaRates');
    Route::post('/setting/updateBackmonthSettings', 'settingController@updateBackmonthSettings');
    Route::get('/setting/getDbBackup', 'settingController@getDbBackup');

    Route::get('/notifications/index', 'notificationsController@index');
    Route::get('/notifications/create', 'notificationsController@create');
    Route::post('/notifications/create', 'notificationsController@store');
    Route::post('/notifications/delete/{id}', 'notificationsController@delete');

    //////////////changes here
    Route::post('/setting/update-conversion-rate', 'settingController@updateConversionRate');
    Route::post('/setting/update-country-conversion-rate', 'UpdatePriceController@updateCountryConversionRate');
});
Route::group(['middleware' => ['permission:report','2fa']], function () {
    // FOR Report
    Route::get('/report', 'invoiceController@viewreport');
    Route::get('/report/shutout', 'invoiceController@viewshutout');
    // End

});

Route::group(['middleware' => ['permission:courier','2fa']], function () {
    // FOR Courier
    Route::get('/courier', 'courierController@index');
    Route::get('/courier/create', 'courierController@create');
    Route::post('/courier/create', 'courierController@store');
    Route::get('/courier/history', 'courierController@history');
    Route::get('/courier/history/{id}', 'courierController@historyCourier');
    Route::get('/courier/history/{id}/snapshot/{snapshotId}', 'courierController@historySnapshot');
    Route::get('/courier/view/{id}', 'courierController@view');
    Route::post('/courier/view/{id}', 'courierController@update');
    Route::get('/courier/delete/{id}', 'courierController@delete');
    Route::post('/courier/bulk-price/{id}', 'courierController@updateBulkCourierPrice');
    // End
});

Route::group(['middleware' => ['permission:hardwares','2fa']], function () {
    // FOR hardwares
    Route::get('/hardwares', 'hardwaresController@index');
    Route::get('/hardwares/monthend/onboarding', [HardwareMonthEndController::class, 'index'])->name('hardwares.monthend.onboarding.index');
    Route::get('/hardwares/monthend/onboarding/{hardware}/consumable/create', [HardwareMonthEndController::class, 'createConsumableForm'])->name('hardwares.monthend.onboarding.create_consumable_form');
    Route::post('/hardwares/monthend/onboarding/{hardware}/consumable/create', [HardwareMonthEndController::class, 'storeConsumable'])->name('hardwares.monthend.onboarding.store_consumable');
    Route::get('/hardwares/create', 'hardwaresController@create');
    Route::post('/hardwares/create', 'hardwaresController@store');
    Route::get('/hardwares/view/{id}', 'hardwaresController@view');
    Route::post('/hardwares/view/{id}', 'hardwaresController@update');
    Route::post('/hardwares/delete/{id}', 'hardwaresController@delete');
    // End
    // FOR smallhardwares
    Route::get('/smallhardwares', 'smallhardwaresController@index');
     Route::get('/smallhardwares/download', 'smallhardwaresController@downloadExcel')->name('smallhardwares.download');

    Route::get('/smallhardwares/create', 'smallhardwaresController@create');
    Route::post('/smallhardwares/create', 'smallhardwaresController@store');
    Route::get('/smallhardwares/view/{id}', 'smallhardwaresController@view');
    Route::post('/smallhardwares/view/{id}', 'smallhardwaresController@update');
    Route::post('/smallhardwares/delete/{id}', 'smallhardwaresController@delete');
    Route::post('/smallhardwares/importCSV', 'smallhardwaresController@importCSV');
    // End
});

Route::group(['middleware' => ['permission:packaging','2fa']], function () {
    // FOR packaging
    Route::get('/packaging', 'packagingController@index');
    Route::get('/packaging/create', 'packagingController@create');
    Route::post('/packaging/create', 'packagingController@store');
    Route::get('/packaging/view/{id}', 'packagingController@view');
    Route::post('/packaging/view/{id}', 'packagingController@update');
    Route::post('/packaging/delete/{id}', 'packagingController@delete');
    Route::post('/packaging/importCSV', 'packagingController@importCSV');
    Route::post('/packaging/importQuantityCSV', 'packagingController@importQuantityCSV')->name('packaging.importQuantityCSV');
    Route::get('/packaging/exportExcel', 'packagingController@exportExcel');
    Route::get('/packaging/quantity-template', 'packagingController@downloadQuantityTemplate')->name('packaging.quantityTemplate');
    Route::post('/packaging/updatePackaging', 'packagingController@updatePackaging');
    Route::get('/packaging/updateAllPackaging', 'packagingController@updateAllPackaging');

    Route::post('/packaging/updateqty','packagingController@updateqty')->name('packaging.updateBoxQty');
    // End
});

Route::group(['middleware' => ['permission:smallhardware','2fa']], function () {
    // FOR smallhardware
    Route::get('/smallhardware/{id}', 'smallhardwareController@index');
    Route::get('/smallhardware/create', 'smallhardwareController@create');
    Route::post('/smallhardware/create', 'smallhardwareController@store');
    Route::get('/smallhardware/view/{id}', 'smallhardwareController@view');
    Route::post('/smallhardware/view/{id}', 'smallhardwareController@update');
    Route::post('/smallhardware/delete/{id}', 'smallhardwareController@delete');
    Route::post('/smallhardware/importCSV', 'smallhardwareController@importCSV');
    Route::post('/smallhardware/updateSmallhardware', 'smallhardwareController@updateSmallhardware');
    Route::get('/smallhardware/updateAllSmallhardware', 'smallhardwareController@updateAllSmallhardware');
    Route::post('/smallhardware/updatePouchPrice', 'smallhardwareController@updatePouchPrice');
    // End
});

Route::group(['middleware' => ['permission:cornerpackaging','2fa']], function () {
    // FOR cornerpackaging
    Route::get('/cornerpackaging', 'cornerpackagingController@index');
    Route::get('/cornerpackaging/create', 'cornerpackagingController@create');
    Route::post('/cornerpackaging/create', 'cornerpackagingController@store');
    Route::get('/cornerpackaging/view/{id}', 'cornerpackagingController@view');
    Route::post('/cornerpackaging/view/{id}', 'cornerpackagingController@update');
    Route::post('/cornerpackaging/delete/{id}', 'cornerpackagingController@delete');
    Route::post('/cornerpackaging/importCSV', 'cornerpackagingController@importCSV');
    Route::get('/cornerpackaging/empdata', 'cornerpackagingController@empdata');
    Route::post('/cornerpackaging/store', 'cornerpackagingController@store');
    Route::post('/cornerpackaging/download', 'cornerpackagingController@download');
    // End
});

// Route::group(['middleware' => ['permission:others','2fa']], function () {
//     //FOR CHART file
//     Route::get('/charts', 'ChartController@index');
//     //End
// });

Route::group(['middleware' => ['permission:pricing-read','2fa']], function () {
    Route::get('/pricing', 'pricingController@index');
    Route::get('/pricing/exportEachCSV/{id}', 'pricingController@exportEachCSV');
    Route::get('/pricing/related_product_calculator', 'pricingController@related_product_calculator');
});

Route::group(['middleware' => ['permission:pricing-edit','2fa']], function () {
    Route::get('/pricing/create', 'pricingController@create');
    Route::get('/pricing/view/{id}', 'pricingController@view');
    Route::post('/pricing/view/{id}', 'pricingController@update');
    Route::get('/pricing/Excel/sheet', 'pricingController@excelSheet');
    Route::get('/pricing/Excel/sheetFormat', 'pricingController@excelSheetFormat');
    Route::post('/pricing/Excel/sheet/store', 'pricingController@excelSheetstore');
    Route::post('/pricing/delete/{id}', 'pricingController@delete');
    Route::post('/pricing/create', 'pricingController@store');
    Route::get('/pricing/data', 'pricingController@data');
    Route::get('/pricing/duplicate/{id}', 'pricingController@duplicate');
    Route::post('/pricing/duplicate', 'pricingController@duplicateStore');
    Route::post('/pricing/importCSV', 'pricingController@importCSV');
    Route::get('/apCalculator/apcalculate', 'pricingController@apcalculate');
    Route::get('apCalculator/apData/{id}/{startDate}/{endDate}', 'pricingController@apData');
    Route::post('/pricing/exportcsv', 'pricingController@exportcsv');
    Route::post('/pricing/exportBuyingCost', 'pricingController@exportBuyingCost');
    Route::post('/pricing/getnewdelcost', 'pricingController@getnewdelcost');
    //////////////////changes here
    Route::get('/pricing/check-courier-country', 'pricingController@check_courier_country');
    Route::get('/pricing/check-product-pricing', 'pricingController@checkProductPricing');
    Route::get('/pricing/get-buyer-form-on-edit', 'pricingController@getBuyerFormOnEdit');
    Route::get('/pricing/get-fob-value', 'pricingController@getFobValue');
    Route::get('/pricing/get-custom-rate', 'pricingController@getCustomValue');
});

Route::group(['middleware' => ['permission:pb-read','2fa']], function () {
    Route::get('/purchaseBill', 'purchaseBillController@index')->name('purchaseBill.index');
    Route::get('/purchaseBill/condition', 'purchaseBillController@purchase_bills');
      Route::get('/purchaseBill/condition/consumables', 'purchaseBillController@purchase_billsconsumables');
    Route::get('/purchaseBill/condition/carton', 'purchaseBillController@purchase_billscarton');
      
    Route::get('/purchaseBill/condition/Multi', 'purchaseBillController@purchase_billsMulti');

    Route::get('/purchaseBill/service/condition', 'purchaseBillController@purchase_billService');
    Route::get('/purchaseBill/service/modal/{id}', 'purchaseBillController@modalservice');
    Route::get('/purchaseBill/service/verify/{id}', 'purchaseBillController@verifyService');
    Route::get('/purchaseBill/service', 'purchaseBillController@service');
    Route::get('/purchaseBill/service/data', 'purchaseBillController@dataService');
    Route::get('/purchaseBill/data/service-po/{id}', 'purchaseBillController@servicePoLines');
    Route::get('/purchaseBill/data/Service/{id}', 'purchaseBillController@serviceTableData');
    Route::post('/purchaseBill/service/exportcsv', 'purchaseBillController@Serviceexportcsv');
    Route::post('/purchaseBill/service/billstracking', 'purchaseBillController@Servicebillstracking');


    Route::post('/purchaseBill/billstracking', 'purchaseBillController@billstracking');
    Route::post('/purchaseBill/condition/consumables/billstracking', 'ConsumableValuationController@purchaseBillConsumableBillstracking');
    Route::post('/purchaseBill/condition/carton/billstracking', 'ConsumableValuationController@purchaseBillCartonBillstracking');
    Route::get('/purchaseBill/data', 'purchaseBillController@data');
    Route::get('/purchaseBill/viewdata', 'purchaseBillController@viewdata');
    Route::get('/purchaseBill/data/pbPoTable/{id}', 'purchaseBillController@poTableData');
    Route::get('/purchaseBill/modal/{id}', 'purchaseBillController@modal');
   
      Route::get('/purchaseBill/modal/consumables/{id}', 'purchaseBillController@modalconsumables')->name('purchaseBill.consumables.modal');
    Route::get('/purchaseBill/modal/carton/{id}', 'purchaseBillController@modalcarton')->name('purchaseBill.carton.modal');

     Route::get('/purchaseBill/verify/consumable/{id}', 'purchaseBillController@verifyconsumable');
    Route::get('/purchaseBill/verify/carton/{id}', 'purchaseBillController@verifycarton');
  
    Route::get('/purchaseBill/modal/multi/{id}', 'purchaseBillController@modalMulti');
    Route::post('/purchaseBill/exportcsv', 'purchaseBillController@exportcsv');
    Route::get('/purchaseBill/isTallyExport', 'purchaseBillController@isTallyExport');
    Route::get('/purchaseBill/verify/{id}', 'purchaseBillController@verify');
    Route::get('/purchaseBill/revert_verify/{id}', 'purchaseBillController@revert_verify');
    Route::get('/purchaseBill/verified', 'purchaseBillController@verified');
    Route::get('/purchaseBill/downloaded', 'purchaseBillController@downloaded');
    Route::get('/purchaseBill/updatePaymentStatus/{id}/{stat}', 'purchaseBillController@updatePaymentStatus');
    Route::get('/purchaseBill/samples', 'purchaseBillController@samples');
    Route::get('/purchaseBill/data/pbPosTable/{id}', 'purchaseBillController@posTableData');
    Route::get('/purchaseBill/dataSample', 'purchaseBillController@dataSample');
    Route::get('/purchaseBill/modalSample/{id}', 'purchaseBillController@modalSample');
    Route::post('/purchaseBill/exportcsvsample', 'purchaseBillController@exportcsvsample');
    Route::post('/purchaseBill/updateTallyStatus/{id}', 'purchaseBillController@update_tally_status');
    Route::post('/purchaseBill-multi/updateTallyStatus/{id}', 'purchaseBillController@update_tally_status_multi');
});

Route::group(['middleware' => ['permission:pb-edit','2fa']], function () {
    Route::get('/purchaseBill/create', 'purchaseBillController@create');
    Route::post('/purchaseBill/create', 'purchaseBillController@store');

    Route::get('/purchaseBill/service/create', 'purchaseBillController@createService');
    Route::post('/purchaseBill/service/create', 'purchaseBillController@storeservice');
    
    //Route::get('/purchaseBill/delete/{id}', 'purchaseBillController@delete');
    //Route::get('/purchaseBill/view/{id}', 'purchaseBillController@view');
    //Route::post('/purchaseBill/view/{id}', 'purchaseBillController@update');
    Route::get('/purchaseBill/createSample', 'purchaseBillController@createSample');
    Route::post('/purchaseBill/createSample', 'purchaseBillController@storeSample');

    Route::get('/purchaseBill/admin/consumable/create', 'AdminPurchaseBillController@createConsumable');
    Route::get('/purchaseBill/admin/carton/create', 'AdminPurchaseBillController@createCarton');
    Route::get('/purchaseBill/admin/consumable/edit', 'AdminPurchaseBillController@editConsumable');
    Route::get('/purchaseBill/admin/data/consumable-po/{id}', 'AdminPurchaseBillController@consumablePoLines');
    Route::get('/purchaseBill/admin/data/carton-po/{id}', 'AdminPurchaseBillController@cartonPoLines');
    Route::get('/purchaseBill/admin/data/consumable-edit-po/{id}', 'AdminPurchaseBillController@consumableEditPoData');
    Route::post('/purchaseBill/admin/consumable', 'AdminPurchaseBillController@storeConsumable');
    Route::post('/purchaseBill/admin/consumable/edit', 'AdminPurchaseBillController@updateConsumable');
    Route::post('/purchaseBill/admin/carton', 'AdminPurchaseBillController@storeCarton');
});

Route::group(['middleware' => ['permission:stockout-read','2fa']], function () {
    Route::get('/stockout', 'invoiceController@stockindex');
    Route::get('/stockout/modal/{id}', 'invoiceController@stockmodal');
    Route::get('/stockout/edit/{id}','invoiceController@stockedit');
});

Route::group(['middleware' => ['permission:stockout-edit','2fa']], function () {
    Route::get('/stockout/create', 'invoiceController@stockcreate');
    Route::post('/stockout/create', 'invoiceController@stockstore');
    Route::get('/stockreverse/create', 'invoiceController@stockreversestore');
});

Route::group(['middleware' => ['permission:rejectrepair-read','2fa']], function () {
    Route::get('/rejectRepair', 'rejectRepairController@index');
});

Route::group(['middleware' => ['permission:rejectrepair-edit','2fa']], function () {
    Route::get('/rejectRepair/create', 'rejectRepairController@create');
    Route::get('/rejectRepair/data', 'rejectRepairController@data');

    Route::post('/rejectRepair/create', 'rejectRepairController@store');
    Route::post('/rejectRepair/complete/{id}', 'rejectRepairController@complete');
    Route::post('/rejectRepair/delete/{id}', 'rejectRepairController@delete');
    Route::get('/rejectRepair/createDebitNote/{id}', 'rejectRepairController@createDebitNote');
    Route::get('/rejectRepair/viewDebitNote/{id}', 'rejectRepairController@viewDebitNote');
    Route::get('/rejectRepair/rejectRepairFixNew', 'rejectRepairController@rejectRepairFixNew');
});

Route::group(['middleware' => ['permission:packing','2fa']], function () {
    Route::get('/invoice/packing_list', 'invoiceController@packing_list');
    Route::get('/invoice/packing_create', 'invoiceController@packing_create');
    Route::post('/invoice/packing_create', 'invoiceController@packing_store');
    Route::get('/invoice/packing_view/{id}', 'invoiceController@packing_view')->name('invoice.packing-view');
    Route::post('/invoice/packing_view/{id}', 'invoiceController@updatepacking');
    Route::post('/invoice/packing_delete/{id}', 'invoiceController@packing_delete');
    Route::get('/invoice/packing_detail/{id}', 'invoiceController@packing_detail');
    Route::get('/purchaseOrder/data', 'purchaseOrderController@data');
    Route::get('/autocomplete-product-name','purchaseOrderController@autocomplete')->name('purchaseOrder.autocomplete.product-name');
    Route::post('/invoice/lock_packing_list', 'invoiceController@lock_packing_list')->name('invoice.lock_packing_list');
    Route::post('/invoice/unlock_packing_list', 'invoiceController@unlock_packing_list')->name('invoice.unlock_packing_list');
});

Route::group(['middleware' => ['permission:sample-edit','2fa']], function () {
    //For Products
    Route::get('/samples', 'samplesController@index');
    Route::get('/samples/create', 'samplesController@create');
    Route::post('/samples/create', 'samplesController@store');
    Route::post('/samples/delete/{id}', 'samplesController@delete');
    Route::get('/samples/view/{id}', 'samplesController@view');
    Route::post('/samples/view/{id}', 'samplesController@update');
    Route::get('/samples/exportcsv', 'samplesController@exportcsv');
    Route::post('/samples/importCSV', 'samplesController@importCSV');
});

Route::group(['middleware' => ['permission:shipping-lines','2fa']], function () {
    // FOR shippingLines
    Route::get('/shipping', 'shippingController@index');
    Route::get('/shipping/create', 'shippingController@create');
    Route::post('/shipping/create', 'shippingController@store');
    Route::get('/shipping/view/{id}', 'shippingController@view');
    Route::post('/shipping/view/{id}', 'shippingController@update');
    Route::post('/shipping/delete/{id}', 'shippingController@delete');
    Route::post('/shipping/getShippingData', 'shippingController@getShippingData');

    Route::get('/shippingLines', 'shippingLinesController@index');
    Route::get('/shippingLines/create', 'shippingLinesController@create');
    Route::post('/shippingLines/create', 'shippingLinesController@store');
    Route::get('/shippingLines/view/{id}', 'shippingLinesController@view');
    Route::post('/shippingLines/view/{id}', 'shippingLinesController@update');
    Route::post('/shippingLines/delete/{id}', 'shippingLinesController@delete');
    // End
});


Route::get('/packingSheet', 'packingSheetController@index');
Route::get('/packingSheet/create', 'packingSheetController@create');
Route::post('/packingSheet/create', 'packingSheetController@store');
Route::get('/packingSheet/data', 'packingSheetController@data');
Route::get('/packingSheet/viewdata', 'packingSheetController@viewdata');
Route::get('/packingSheet/delete/{id}', 'packingSheetController@delete');
Route::get('/packingSheet/data', 'packingSheetController@data');
Route::get('/packingSheet/data/psInvoiceTable/{id}', 'packingSheetController@psInvoiceTable');
Route::get('/packingSheet/data/psInvoiceTableForCN/{id}', 'packingSheetController@psInvoiceTableForCN');
Route::get('/packingSheet/view/{id}', 'packingSheetController@view');
Route::post('/packingSheet/view/{id}', 'packingSheetController@update');
Route::get('/product/create_history', 'productController@create_history');
Route::get('/product/create_history_manager', 'productController@create_history_manager');
Route::get('/import_array', 'productController@import_array');

// Route::get('/exportFile/pdfview', array('as' => 'exportFile/pdfview', 'uses' => 'PdfController@pdfview'));

Route::get('/makepdfpurchase', 'productController@pdfview');

//For Export File
// Route::get('items', 'ItemController@index');
// Route::get('items/export', 'ItemController@export');
//End

// Route::get('exportFile/index', 'PagesController@index');
// Route::post('exportFile/index', 'PagesController@index');

Route::get('viewpdf', function () {
    return view('viewpdf');
});

//Route::post('viewpdf', 'UserController@viewpdf');

// Route::get('bar', function () {
//     return view('bar');
// });

// Route::get('bar', 'ChartController@bar');

Route::get('/login', function () {
    return view('auth/login');
});

Route::get('/register', function () {
    return view('auth/register');
});

Route::get('/forget', function () {
    return view('forgetPass');
});

Route::get('/supplierInvoice/modalEmail/{id}', 'supplierInvoiceController@modal');

Route::get('/invoice/modal/{id}', 'invoiceController@modal');
Route::get('/invoice/modal_uk/{id}', 'invoiceController@modal_uk');
Route::get('/invoice/modal_us/{id}', 'invoiceController@modal_us');
Route::get('/invoice/modal_eu/{id}', 'invoiceController@modal_eu');
Route::get('/invoice/modal_canada/{id}', 'invoiceController@modal_canada');
Route::get('/invoice/modal_california/{id}', 'invoiceController@modal_california');

Route::get('/invoice/printinv/{id}', 'invoiceController@printinv');
Route::get('/invoice/modalpackingsheet/{id}', 'invoiceController@modalpackingsheet');

Auth::routes();

//2fa
Route::group(['prefix' => '2fa'], function () {
    Route::get('/', 'LoginSecurityController@show2faForm');
    Route::post('/generateSecret', 'LoginSecurityController@generate2faSecret')->name('generate2faSecret');
    Route::post('/enable2fa', 'LoginSecurityController@enable2fa')->name('enable2fa');
    Route::post('/disable2fa', 'LoginSecurityController@disable2fa')->name('disable2fa');
     Route::post('/logout2fa', 'LoginSecurityController@logout2fa')->name('logout2fa');

    // 2fa middleware
    Route::post('/2faVerify', function () {
        return redirect('/dashboard');
    })->name('2faVerify')->middleware('2fa');
});

// test middleware
Route::get('/2fa_authenticate', function () {
    return redirect(url('/dashboard'));
    // })->middleware(['auth', '2fa']);
});
Route::get('stagezero','SustainController@stagezero')->middleware('auth');

// Route::get('stagezero','SustainController@stagezero')->middleware('auth');

Route::get('/home', 'HomeController@index')->name('dashboard');
Route::get('/assigningbatchestoold', 'invoiceController@assigningbatchestoold');

Route::get('/products/search', 'productController@search')->name('products.search');

Route::get('/run-migrations', function () {  
    try {
        Artisan::call('migrate', ['--force' => true]);
        return 'Migrations ran successfully.';
    } catch (\Exception $e) {
        return 'Migration failed: ' . $e->getMessage();
    }
});


include "sustainabilityRoutes.php";
include "supplierSustainabilityRoutes.php";
include "roleManagementRoutes.php";
include "allocationModuleRoutes.php";
include "wholesalePoRoutes.php";