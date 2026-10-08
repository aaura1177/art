<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\invoice;
use App\buyer;
use App\product;
use App\category;
use App\subCategory;
use App\stockLog;
use App\Batch;
use App\BatchProduct;
use App\supplier;
use App\Exports\StockReportAll;
use App\Exports\BatchLogExport;
use App\Exports\StockReportProduct;
use App\Exports\BatchStockExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BatchReport;
use Carbon\Carbon;

class InventoryReportController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by category if selected
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by subcategory if selected
        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }

        $products = $query->orderBy('updated_at', 'desc')->get();
        $cat = Category::all();
        $subcat = SubCategory::all();
        $batches = Batch::all();

        return view('admin.inventory_report.index', compact('products', 'cat', 'subcat', 'batches'));
    }

    public function detail_report(Request $request, $id)
    {
        $products = product::where('id', $id)->first();

        // Base query for stock logs
        $logsQuery = stockLog::where('type', '<', '3')->where('product_id', $id);

        // Apply filters (date range, supplier, etc.)
        if ($request->has('from') && $request->from != '') {
            $logsQuery->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to') && $request->to != '') {
            $logsQuery->whereDate('created_at', '<=', $request->to);
        }

        if (empty($request->type)) {
            $logsQuery->where('type', '<', '3');
        } else {
            $logsQuery->where('type', $request->type);
        }

        // Get filtered logs
        $logs = $logsQuery
    ->orderBy('created_at', 'desc')
    ->orderBy('id', 'desc')   // tie-breaker when created_at is identical
    ->get();

        // Calculate the sum of received stock (type 1) and out stock (type 2)
        $receivedStock = $logsQuery->where('type', 1)->sum('quantity');
        $outStock = $logsQuery->where('type', 2)->sum('quantity');

        // Remaining stock calculation (can be the last remaining stock from the logs or custom logic)
        $lastLog = $logs->first();
        $remainingStock = $lastLog ? $lastLog->remaining_stock : 0;




        // Get the filtered logs

        // Get suppliers to populate supplier dropdown


        return view('admin.inventory_report.detail_report', [
            'logs' => $logs,
            'products' => $products,
            'receivedStock' => $receivedStock,
            'outStock' => $outStock,
            'remainingStock' => $remainingStock

        ]);
    }


    public function batchExport()
    {
        $fileName = 'batch_log_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new BatchLogExport, $fileName);
    }

    public function batchExportReport(Request $request)
    {
        $batchNo = $request->batch_no;

        if ($batchNo === 'all') {
            $fileName = 'batch_report_all.xlsx';
        } else {
            $batch = Batch::with('supplier')->where('batch_no', $batchNo)->first();
            $supplierName = $batch->supplier->c_name ?? 'Unknown';
            $fileName = $batchNo . ' (' . $supplierName . ').xlsx';
        }

        return Excel::download(new BatchReport($batchNo), $fileName);
    }



public function StockExportReport(Request $request)
{
    $filters = [
        'from_date'   => $request->from_date,
        'to_date'     => $request->to_date,
        'invoice_no'  => $request->invoice_no,
        'batch_no'    => $request->batch_no,
    ];

    $fileName = 'stock_report';

    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        $fileName .= '_' . str_replace('-', '', $filters['from_date']) . '_to_' . str_replace('-', '', $filters['to_date']);
    }

    if (!empty($filters['invoice_no'])) {
        $safeInvoice = preg_replace('/[^A-Za-z0-9\-]/', '_', $filters['invoice_no']);
        $fileName .= '_invoice_' . $safeInvoice;
    }

    if (!empty($filters['batch_no'])) {
        $safeBatch = preg_replace('/[^A-Za-z0-9\-]/', '_', $filters['batch_no']);
        $fileName .= '_batch_' . $safeBatch;
    }

    $fileName .= '.xlsx';

    return Excel::download(new StockReportAll($filters), $fileName);
}




public function StockExportReportProduct(Request $request)
{
    $filters = [
        'from_date'  => $request->from_date,
        'to_date'    => $request->to_date,
        'invoice_no' => $request->invoice_no,
        'batch_no'   => $request->batch_no,
        'product_id' => $request->product_id,
    ];

    $fileName = 'stock_report';

    if (!empty($request->product_id)) {
        $product = product::find($request->product_id);
        if ($product) {
            $fileName .= '_' . $product->code;
        }
    }

    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        $fileName .= '_' . $filters['from_date'] . '_to_' . $filters['to_date'];
    }

    if (!empty($filters['invoice_no'])) {
        $fileName .= '_invoice_' . $filters['invoice_no'];
    }

    if (!empty($filters['batch_no'])) {
        $fileName .= '_batch_' . $filters['batch_no'];
    }

    $fileName .= '.xlsx';

    return Excel::download(new StockReportProduct($filters), $fileName);
}

public function batchExportStockReport(Request $request)
{
    $batchNo = $request->batch_no ?? 'all';
    $fromDate = $request->from_date;
    $toDate = $request->to_date;

    $minDate = Carbon::parse('2025-11-01');

    if (Carbon::parse($fromDate)->lt($minDate)) {
        $fromDate = $minDate->format('Y-m-d');
    }

    if (Carbon::parse($toDate)->lt($minDate)) {
        $toDate = $minDate->format('Y-m-d');
    }

    $batchLabel = $batchNo === 'all' ? 'AllBatches' : $batchNo;

    $from = Carbon::parse($fromDate)->format('dMY');
    $to = Carbon::parse($toDate)->format('dMY');

    $filename = "Batch_Stock_Report_{$batchLabel}_{$from}_to_{$to}.xlsx";

    return Excel::download(
        new BatchStockExport($batchNo, $fromDate, $toDate),
        $filename
    );
}

public function stockQty()
{
    // Step 1: Define date range — from Nov 1 to today
    $from = Carbon::create(2025, 11, 1)->startOfDay();
    $to = Carbon::now()->endOfDay();

    // Step 2: Get stock logs within date range where batch_no starts with 'DEVI'
    $stockLogs = stockLog::whereBetween('created_at', [$from, $to])
    ->where(function($query) {
        $query->whereNull('supplier_name')
              ->orWhere('supplier_name', '');
    })
                ->get();

    // Step 3: Loop and update supplier name
    foreach ($stockLogs as $log) {
        // find the batch record linked to this log
        $batch = Batch::where('batch_no', $log->batch_no)->first();

        if ($batch) {
            $supplier = supplier::find($batch->supplier_id);
            if ($supplier) {
                $log->supplier_name = $supplier->c_name;
                $log->save();
            }
        }
    }

    return "Updated " . $stockLogs->count() . " records successfully.";
}


public function SupplierInvoiceNOswap()
{
    $from = Carbon::create(2025, 11, 1)->startOfDay();
    $to = Carbon::now()->endOfDay();

    $stockLogsout = StockLog::where('ref_no', 'LIKE', '%SWAP%')
          ->where('type',2)
        ->whereBetween('created_at', [$from, $to])
        ->where(function($query) {
        $query->whereNull('supplier_inv_no')
              ->orWhere('supplier_inv_no', '');
    })
        ->get();

    foreach ($stockLogsout as $log) {
        $cleanRef = str_replace('SWAP OUT - ', '', $log->ref_no);

        $cleanRef = trim($cleanRef);

        $log->supplier_inv_no = $cleanRef;
        $log->save();
    }
 $stockLogsin = StockLog::where('ref_no', 'LIKE', '%SWAP%')
          ->where('type',1)
        ->whereBetween('created_at', [$from, $to])
        ->where(function($query) {
        $query->whereNull('supplier_inv_no')
              ->orWhere('supplier_inv_no', '');
    })
        ->get();
 foreach ($stockLogsin as $logIN) {


        $logIN->supplier_inv_no = $logIN->voucher_no;
        $logIN->save();
    }

    $stockLogStock = StockLog::where('ref_no', 'LIKE', '%Stock%')
          ->where('type',2)
        ->whereBetween('created_at', [$from, $to])
        ->where(function($query) {
        $query->whereNull('supplier_inv_no')
              ->orWhere('supplier_inv_no', '');
    })
        ->get();

        foreach ($stockLogStock as $logStock) {


        $logStock->supplier_inv_no = $logStock->voucher_no;
        $logStock->save();
    }

 return "Updated  records successfully.";
}


public function SupplierInvoicestock(){
     $from = Carbon::create(2025, 11, 1)->startOfDay();
    $to = Carbon::now()->endOfDay();

    $stockLogsout = StockLog::whereBetween('created_at', [$from, $to])
        ->where(function($query) {
        $query->whereNull('supplier_inv_no')
              ->orWhere('supplier_inv_no', '');
    })
        ->get();

    foreach ($stockLogsout as $log) {


        $log->supplier_inv_no = $log->voucher_no;;
        $log->save();
    }
     return "Updated  records successfully.";
}

public function buyername(){
      $from = Carbon::create(2025, 11, 1)->startOfDay();
    $to = Carbon::now()->endOfDay();


    $stockLogStock = StockLog::where('ref_no', 'LIKE', '%Stock%')
          ->where('type',2)
        ->whereBetween('created_at', [$from, $to])
        ->where(function($query) {
        $query->whereNull('buyer_name')
              ->orWhere('buyer_name', '');
    })
        ->get();

        foreach ($stockLogStock as $logStock) {

          $invoice =invoice::where('invoiceno',$logStock->voucher_no)->first();
         $buyerName = buyer::find($invoice->buyer_id);
        $logStock->buyer_name = $buyerName->c_name;
        $logStock->save();
    }
     return "Updated  records successfully.";
}

}