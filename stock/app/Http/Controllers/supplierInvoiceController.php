<?php

namespace App\Http\Controllers;

use App\Services\ServiceInvoiceQuantityService;

use App\SubServiceTable;
use App\SubServiceProductTable;
use App\SubServicePbTable;
use App\UnitType;

use App\Batch;
use App\BatchProduct;
use Illuminate\Support\Facades\Storage;
use App\consumable;
use App\Exports\PosExport;
use App\Exports\purchaseOrderTallySampleExport;
use App\Exports\siExport;
use App\packaging;
use App\packagingPrice;
use App\pbTable;
use App\ServicePbTable;
use App\servicePurchaseBill;
use App\popTable;
use App\poTable;
use App\pocTable;
use App\product;
use App\productLocations;
use App\purchaseBill;
use App\purchaseBillConsumable;
use App\pbTableConsumable;
use App\stockLogConsumable;
use App\StockLogCarton;
use App\PbTableCorton;
use App\PurchaseBillCarton;
use App\purchaseOrder;
use App\purchaseOrderConsumable;
use App\Service;
use App\ServiceProduct;
use App\serviceProductTable;
use App\UniqueReferenceNumber;
use App\UniqueReferenceNumberProduct;
use App\serviceTable;
use App\setting;
use App\stockLog;
use App\supplier;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use App\supplierInvoiceReturn;
use App\supplierInvoiceReturnProduct;
use App\soTable;
use App\Notification;
use App\supplierServiceInvoice;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RefQtyBackfillReportExport;
use App\Support\ConsumableMonthEndInvoiceSupport;
use App\Support\SupplierMultiPoSupport;
use Illuminate\Support\Str;

class supplierInvoiceController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }
    public function download($id)
    {
        $supplierInvoice = supplierInvoice::find($id);
        $directory = public_path('../../uploads/supplier-invoice/' . $supplierInvoice->eway_bill_pdf);
        //dd($directory);

        $headers = [
            'Content-Type' => 'application/pdf',
        ];

        return response()->download($directory, $supplierInvoice->eway_bill_pdf, $headers);
    }

    // public function exportcsv()
    // {
    //     return Excel::download(new purchaseOrder(), 'purchaseOrder.csv');

    // }

    public function exportcsv(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new siExport($fsd, $fed))->download('supplierInvoice.xlsx');
    }
    public function store_approve(Request $request, $id)
    {
        // return $request->all();
        $supplierInvoice = supplierInvoice::find($id);

      
        
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoice->purchase_order_id)->first();
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
        }


        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $carbonDate = Carbon::parse($request['supp_inv_date']);
            $Batchmonth = $carbonDate->format('m') . $carbonDate->format('y');
            $Batchmonth1 = $carbonDate->format('m') . '/' . $carbonDate->format('y');

              $exists = purchaseBill::where('getinserailno', $request['getinserailno'])->exists();

        if ($exists) {
            return redirect('/supplierInvoice')->with('danger', 'Get In Serial No already exists.');
        }
        
                    $this->ensureSupplierInvoiceReferenceNumber($supplierInvoice);

if ($request['tdsTotal']) {
    $number = $request['tdsTotal'];
    $intPart = floor($number);
    $decimal = $number - $intPart;

    if ($decimal < 0.5) {
        $rounded = $intPart;
    } else {
        $rounded = $intPart + 1;
    }
}

            $q = purchaseBill::create([
                'purchaseOrder_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'ewaybill' => strtoupper($request['ewaybill']),
                'supp_inv_no' => strtoupper($request['supp_inv_no']),
                'supp_inv_date' => $request['supp_inv_date'],
                'quantity' => $request['pbQty'],
                'subtotal' => $request['pbSubTotal'],
                'gst' => $request['pbGST'],
                'freight' => $request['freight'],
                'total' => $request['pbTotal'],
                'tdsTotal' => $rounded,
                'totaldiscount' => $request['totaldiscount'],
                'roundoff' => $request['roundoff'],
                'getinserailno' => $request['getinserailno'],
                'invoice_status' => 1,
                'supplier_invoice_id' => $supplierInvoice->id
            ]);
            $batchNumbers = [];
            $supplier = supplier::find($purchaseOrder->supplier_id);

            $prefix = $supplier->short_name . $Batchmonth;
            $totalquantitybatch = 0;

            $batch = Batch::where('date', $Batchmonth1)
                ->whereRaw("LEFT(batch_no, LENGTH(batch_no) - 4) = ?", [$prefix])
                ->first();
            if (!$batch) {
                $batch =  Batch::create([
                    'batch_no' => $supplier->short_name . $Batchmonth . rand(1000, 9999),
                    'quantity' => 0,
                    'supplier_id' =>  $supplier->id,
                    'date' => $Batchmonth1,
                ]);
            }

            if (isset($batch)) {
                $supplierInvoice->batch_no = $batch->batch_no;
            }
            foreach ($request['pb'] as $pb) {
                if ($pb['receiveqty'] > 0) {
                    $poProduct = poTable::where('poid', $purchaseOrder->id)->where('product_id', $pb['product'])->first();
                    $soProduct = soTable::where('supplier_invoice_id', $supplierInvoice->id)->where('purchase_order_id', $purchaseOrder->id)->where('product_id', $pb['product'])->first();



                    $amount2  = $pb['amount'] - $pb['rem_discount'];
                    $p = pbTable::create([
                        'product_id' => $pb['product'],
                        'ean' => $pb['EAN'],
                        'purchaseOrder_id' => $q->purchaseOrder_id,
                        'purchaseBill_id' => $q->id,
                        'orderqty' => $poProduct->remqty + $soProduct->quantity,
                        'receiveqty' => $pb['receiveqty'],
                        'remainingqty' => $poProduct->remqty + $soProduct->quantity - $pb['receiveqty'],
                        'rate' => $pb['rate'],
                        'amount' => $amount2,
                        'discount_type' => $pb['discount_type'],
                        'discount' => $pb['rem_discount'],
                        'location' => $pb['location'],
                    ]);

                    $poProduct->remqty = $p->remainingqty;

                    $poProduct->remaining_discount += $soProduct->discount - $pb['rem_discount'];

                    $poProduct->save();
                    // Initialize statusremqty variable
                    $statusremqty = 0;

                    // Get all poTable entries for this purchase order and sum the remaining quantities
                    $poTableCheck = poTable::where('poid', $purchaseOrder->id)->get();
                    foreach ($poTableCheck as $poTableChecks) {
                        $statusremqty += $poTableChecks->remqty;
                    }

                    // Update purchase order status based on remaining quantity
                    if ($statusremqty == 0) {
                        $purchaseOrder->status = 1; // Mark as complete
                        $purchaseOrder->remqty = $statusremqty;
                        $purchaseOrder->save();
                    } else {
                        $purchaseOrder->status = 0; // Mark as incomplete
                        $purchaseOrder->remqty = $statusremqty;
                        $purchaseOrder->save();
                    }

                    if (trim($pb['location']) != "") {
                        $plocation = productLocations::where('product_id', $pb['product'])->where('location', $pb['location'])->first();
                        if ($plocation) {
                            $plocation->quantity = $plocation->quantity + $pb['receiveqty'];
                            $plocation->save();
                        } else {
                            $pl = productLocations::create([
                                'product_id' => $pb['product'],
                                'location' => $pb['location'],
                                'quantity' => $pb['receiveqty'],
                            ]);
                        }
                    }
                    $product = product::where('id', $pb['product'])->first();
                    $open_balance = $product->quantity;
                    $product->quantity = $product->quantity + $pb['receiveqty'];
                    $product->location = $pb['location'];
                    $product->save();


                    if ($purchaseOrder->address_option != 100) {

                        if ($batch) {
                            $batchproduct = BatchProduct::where('product_id', $pb['product'])->where('batch_id', $batch->id)->first();
                            if ($batchproduct) {
                                $batchproduct->quantity += $pb['receiveqty'];
                                $batchproduct->save();
                            } else {

                                $batchproduct = BatchProduct::create([
                                    'batch_id' => $batch->id,
                                    'product_id' => $pb['product'],
                                    'quantity' => $pb['receiveqty'],
                                    'date' => now()->toDateString(),
                                    'supp_in_no' => strtoupper($request['supp_inv_no']),
                                ]);
                            }
                            $batchNumbers[] = $batch->batch_no;
                            if ($batchproduct) {

                                $this->ensureSupplierInvoiceReferenceNumber($supplierInvoice);
                                if ($supplierInvoice->isDirty()) {
                                    $supplierInvoice->save();
                                }
                                $formattedReferenceNumber = $supplierInvoice->supplier_invoice_number . '(' . (int) $pb['receiveqty'] . '/0)';
                                $s = stockLog::create([
                                    'product_id' => $pb['product'],
                                    'voucher_no' => strtoupper($request['supp_inv_no']),
                                    'supplier_inv_no' => strtoupper($request['supp_inv_no']),
                                    'ref_no' => $purchaseOrder->ref_supplier,
                                    'quantity' => $pb['receiveqty'],
                                    'opening_balance' => $open_balance,
                                    'remaining_stock' => $product->quantity,
                                    'type' => 1,
                                    'supplier_name' => $supplier->c_name,
                                    'entity_id' => $q->id,
                                    'batch_no'  =>  $batch->batch_no,
                                    'batch_balance' =>  $batchproduct->quantity,
                                    'reference_number' => $formattedReferenceNumber,
                                    'reference_quantity' => (int) $pb['receiveqty']
                                ]);
                            }

                            $this->syncApprovedRefToBothTables(
                                $supplierInvoice,
                                (string) $batch->batch_no,
                                (int) $pb['product'],
                                (int) $pb['receiveqty']
                            );
                            $this->syncApprovedRefToLegacySupplierReferenceTables(
                                $supplierInvoice,
                                (string) $batch->batch_no,
                                (int) $pb['product'],
                                (int) $pb['receiveqty']
                            );

                            $totalquantitybatch += $pb['receiveqty'];
                        }
                    }
                }
            }
            $batch->quantity += $totalquantitybatch;
            $batch->save();
        } else {
            if ($purchaseOrder->type == 1) {

                $q = purchaseBillConsumable::create([
                    'purchaseOrder_id' => $purchaseOrder->id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'ewaybill' => strtoupper($request['ewaybill']),
                    'supp_inv_no' => strtoupper($request['supp_inv_no']),
                    'supp_inv_date' => $request['supp_inv_date'],
                    'quantity' => $request['pbQty'],
                    'subtotal' => $request['pbSubTotal'],
                    'gst' => $request['pbGST'],
                    'freight' => $request['freight'],
                    'total' => $request['pbTotal'],
                    'tdsTotal' => $request['tdsTotal'],
                    'invoice_status' => 1,
                    'supplier_invoice_id' => $supplierInvoice->id
                ]);
                foreach ($request['pb'] as $pb) {
                    if ($pb['receiveqty'] > 0) {
                        $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($purchaseOrder, $pb);
                        $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                            $purchaseOrder,
                            (int) $pb['product'],
                            $pocLineId
                        );
                        $soProduct = ConsumableMonthEndInvoiceSupport::resolveSoLine(
                            (int) $supplierInvoice->id,
                            (int) $purchaseOrder->id,
                            (int) $pb['product'],
                            $pocLineId,
                            $purchaseOrder
                        );
                        if (! $poProduct || ! $soProduct) {
                            return redirect('/supplierInvoice')->with(
                                'danger',
                                'Could not match approve line to purchase order / supplier invoice (check PO line id).'
                            );
                        }

                        $lineAmount = (float) ($pb['amount'] ?? 0);
                        if ($lineAmount <= 0 && (float) ($pb['receiveqty'] ?? 0) > 0) {
                            $lineAmount = (float) ($pb['receiveqty'] ?? 0) * (float) ($pb['rate'] ?? 0);
                        }
                        $p = pbTableConsumable::create([
                            'product_id' => $pb['product'],

                            'purchaseOrder_id' => $q->purchaseOrder_id,
                            'purchaseBill_id' => $q->id,
                            'orderqty' => $poProduct->remqty + $soProduct->quantity,
                            'receiveqty' => $pb['receiveqty'],
                            'remainingqty' => $poProduct->remqty + $soProduct->quantity - $pb['receiveqty'],
                            'rate' => $pb['rate'],
                            'unit' => $pb['unit'],
                            'amount' => $lineAmount,
                            'location' => $pb['location'],
                        ]);

                        $poProduct->remqty = $p->remainingqty;
                        $poProduct->save();
                        // Initialize statusremqty variable
                        $statusremqty = 0;

                        // Get all poTable entries for this purchase order and sum the remaining quantities
                        $poTableCheck = pocTable::where('poid', $purchaseOrder->id)->get();
                        foreach ($poTableCheck as $poTableChecks) {
                            $statusremqty += $poTableChecks->remqty;
                        }

                        // Update purchase order status based on remaining quantity
                        if ($statusremqty == 0) {
                            $purchaseOrder->status = 1; // Mark as complete
                            $purchaseOrder->remqty = $statusremqty;
                            $purchaseOrder->save();
                        } else {
                            $purchaseOrder->status = 0; // Mark as incomplete
                            $purchaseOrder->remqty = $statusremqty;
                            $purchaseOrder->save();
                        }


                        // Month-end PO (address_option 100): stock was adjusted when PO was generated; do not bump consumable qty again on approve.
                        if ((int) $purchaseOrder->address_option !== 100) {
                            $product = consumable::where('id', $pb['product'])->first();
                            $open_balance = $product->quantity;
                            $product->quantity = $product->quantity + $pb['receiveqty'];
                            $product->save();

                            $supplier = supplier::find($purchaseOrder->supplier_id);
                            $s = stockLogConsumable::create([
                                'consumable_id' => $pb['product'],
                                'voucher_no' => strtoupper($request['supp_inv_no']),
                                'supplier_inv_no' => strtoupper($request['supp_inv_no']),
                                'ref_no' => $purchaseOrder->ref_supplier,
                                'quantity' => $pb['receiveqty'],
                                'opening_balance' => $open_balance,
                                'remaining_stock' => $product->quantity,
                                'type' => 1,
                                'supplier_name' => $supplier->c_name,
                                'entity_id' => $q->id,
                                'batch_balance' => $pb['receiveqty'],
                            ]);
                        }
                    }
                }
            } else {


                foreach ($request['pb'] as $pb) {
                    $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($purchaseOrder, $pb);
                    $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                        $purchaseOrder,
                        (int) $pb['product'],
                        $pocLineId
                    );
                    $soProduct = ConsumableMonthEndInvoiceSupport::resolveSoLine(
                        (int) $supplierInvoice->id,
                        (int) $purchaseOrder->id,
                        (int) $pb['product'],
                        $pocLineId,
                        $purchaseOrder
                    );
                    if (! $poProduct || ! $soProduct) {
                        return redirect('/supplierInvoice')->with(
                            'danger',
                            'Could not match approve line to purchase order / supplier invoice (check PO line id).'
                        );
                    }
                    $supplier = supplier::find($purchaseOrder->supplier_id);
                    $poProduct->remqty = $poProduct->remqty + $soProduct->quantity - $pb['receiveqty'];
                    $poProduct->save();
                    $statusremqty = 0;

                    $poTableCheck = pocTable::where('poid', $purchaseOrder->id)->get();
                    foreach ($poTableCheck as $poTableChecks) {
                        $statusremqty += $poTableChecks->remqty;
                    }

                    // Update purchase order status based on remaining quantity
                    if ($statusremqty == 0) {
                        $purchaseOrder->status = 1;
                        $purchaseOrder->remqty = $statusremqty;
                        $purchaseOrder->save();
                    } else {
                        $purchaseOrder->status = 0;
                        $purchaseOrder->remqty = $statusremqty;
                        $purchaseOrder->save();
                    }
                }
            }
        }
        $supplierInvoice->is_approved = 1;
        // $supplierInvoice->batch_no = $supplier->short_name . $Batchmonth . rand(1000, 9999);
        $supplierInvoice->update();
        return redirect('/supplierInvoice')->with('success', 'Approved successfully.');
    }
    public function approve($id)
    {
 $unitType = UnitType::all();
        $supplierInvoice = supplierInvoice::where('id', $id)->first();
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoice->purchase_order_id)->first();
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $supplier = supplier::find($purchaseOrder->supplier_id);
        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $typeUnit =  1;
                $supplierInvoiceProduct[$key]['potable'] = poTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                if (isset($supplierInvoiceProduct[$key]['potable']->gstslab)) {
                    $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($supplierInvoiceProduct[$key]['potable']->gstslab / 100);
                } else {
                    $supplierInvoiceProduct[$key]['gstamount'] = 0;
                }
            } else {
                $typeUnit =  2;
                $supplierInvoiceProduct[$key]['potable'] = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                    $purchaseOrder,
                    $sup
                );
                $potable = $supplierInvoiceProduct[$key]['potable'] ?? null;
                $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                    $purchaseOrder,
                    $sup,
                    $potable
                );
            }
        }
        $admins = User::whereIn('role', ['Admin'])->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Supplier Invoice no. - ' . $supplierInvoice->supplier_invoice_number . ' has been approved by Puran.',
                'is_read' => 0
            ]);
        }
        if ($typeUnit == 2) {
            // Consumable approve view does not use furniture purchaseBill; omit unused $bills.
            return view('supplierInvoice/create_via_suppinvoiceconsumable', [
                'supplierInvoiceProduct' => $supplierInvoiceProduct,
                'purchaseOrder' => $purchaseOrder,
                'supplier' => $supplier,
                'supplierInvoice' => $supplierInvoice,
                'typeUnit' => $typeUnit,
            ]);
        }

        $bills = purchaseBill::where('purchaseOrder_id', $purchaseOrder->id)->first();

        return view('supplierInvoice/create_via_suppinvoice', [
            'bills' => $bills,
            'supplierInvoiceProduct' => $supplierInvoiceProduct,
            'purchaseOrder' => $purchaseOrder,
            'supplier' => $supplier,
            'supplierInvoice' => $supplierInvoice,
            'typeUnit' => $typeUnit,
        ]);
    }
    public function subTable($id)
    {
        $product = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();
        return response()->json(['product' => $product]);
    }
    public function view($id)
    {

        $purchaseOrder = purchaseOrder::find($id);
        if ($purchaseOrder->status != 1) {
            $product = product::all();
            $supplier = supplier::where('type', '!=', 'Consumable')->get();
            $poTable = poTable::where('poid', $id)->get();
            if ($purchaseOrder) {
                return view('purchaseOrder/view', ['purchaseOrder' => $purchaseOrder, 'product' => $product, 'supplier' => $supplier, 'poTable' => $poTable]);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }
    }

    public function index(Request $request)
    {

        $search = $request->input('search');

        $supplierInvoices = supplierInvoice::with(['supplier', 'purchaseOrder', 'user.supplier', 'supplierData'])
            ->whereNull('mulitple_po')->where('purchase_order_type', '!=', 'Carton')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_invoice_number', 'like', "%{$search}%")
                        ->orWhere('internal_invoice_number', 'like', "%{$search}%")
                        ->orWhere('eway_bill_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q2) use ($search) {
                            $q2->where('c_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                            $q2->where('pono', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('supplierInvoice/index', ['supplierInvoices' => $supplierInvoices]);
    }

       public function indexcarton()
    {

        $supplierInvoice = supplierInvoice::with(['user.supplier', 'supplierData', 'supplier'])
            ->where('purchase_order_type', 'Carton')
            ->orderBy('created_at', 'desc')
            ->get();
        $product = product::get();

        return view('supplierInvoice/indexcarton', ['supplierInvoice' => $supplierInvoice, 'product' => $product]);
    }


    public function data()
    {
        $product = product::all();
        return response()->json(['product' => $product]);
    }

    public function delete(Request $request, $id)
    {
        $purchaseOrder = purchaseOrder::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if ($poRelationCount > 0) {
            return redirect('/purchaseOrder')->with('danger', 'Purchase Order cannot be deleted. Purchase Order exist in other relations.');
        } else {
            $poTable = poTable::where('poid', $id)->get();

            if ($poTable) {
                foreach ($poTable as $key => $value) {
                    $poTable[$key]->delete();
                }
            }

            if ($purchaseOrder) {
                if ($purchaseOrder->delete()) {
                    return redirect('/purchaseOrder')->with('success', 'Purchase Order deleted successfully.');
                } else {
                    return redirect('/purchaseOrder')->with('danger', 'Purchase Order was not found.');
                }
            }
        }
    }

    public function create($id, $type)
    {
        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->first();
        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();

        return view('supplierInvoice/create', ['id' => $id, 'supplierInvoiceProduct' => $supplierInvoiceProduct, 'supplierInvoice' => $supplierInvoice]);
    }

    public function updateSupplierReturn(Request $request)
    {

        supplierInvoiceReturn::create([
            'supplier_invoice_id' => $request['id'],
            'type' => 'full',
        ]);
        return redirect('/supplierInvoice')->with('success', 'Returns submitted successfully.');
        //  return view('supplierInvoice/index', ['supplierInvoice'=>$supplierInvoice,'product'=>$product]);
    }

    public function modal($id)
    {
        $supplierInvoice = supplierInvoice::where('id', $id)->first();
        //dd($supplierInvoice);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoice->purchase_order_id)->first();
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = poTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                if (isset($potable->gstslab)) {
                    $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($potable->gstslab / 100);
                } else {
                    $supplierInvoiceProduct[$key]['gstamount'] = 0;
                }
            } elseif ((int) $purchaseOrder->type === 2) {
                $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                $gstslab = (float) (optional($potable)->gstslab ?? 0);
                $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstslab / 100.0, 2);
            } else {
                $supplierInvoiceProduct[$key]['potable'] = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                    $purchaseOrder,
                    $sup
                );
                $potable = $supplierInvoiceProduct[$key]['potable'] ?? null;
                $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                    $purchaseOrder,
                    $sup,
                    $potable
                );
            }
        }
        //dd($supplierInvoiceProduct);
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $footerTgst = $purchaseOrder instanceof purchaseOrderConsumable
            && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($purchaseOrder)
            ? (float) $supplierInvoice->tgst
            : null;

        return view('supplierInvoice/modal', [
            'supplierInvoice' => $supplierInvoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'poTable' => $supplierInvoiceProduct,
            'purchaseOrder' => $purchaseOrder,
            'fileMap1' => $fileMap1,
            'footerTgst' => $footerTgst,
        ]);
    }


        public function modalcarton($id)
    {
        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->first();
        //dd($supplierInvoice->purchase_order_id);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoice->purchase_order_id)->first();
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = poTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                if (isset($potable->gstslab)) {
                    $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($potable->gstslab / 100);
                } else {
                    $supplierInvoiceProduct[$key]['gstamount'] = 0;
                }
            } elseif ((int) $purchaseOrder->type === 2) {
                $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                $gstslab = (float) (optional($potable)->gstslab ?? 0);
                $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstslab / 100.0, 2);
            } else {
                $supplierInvoiceProduct[$key]['potable'] = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                    $purchaseOrder,
                    $sup
                );
                $potable = $supplierInvoiceProduct[$key]['potable'] ?? null;
                $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                    $purchaseOrder,
                    $sup,
                    $potable
                );
            }
        }

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $footerTgst = $purchaseOrder instanceof purchaseOrderConsumable
            && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($purchaseOrder)
            ? (float) $supplierInvoice->tgst
            : null;

        return view('supplierInvoice/modal', [
            'supplierInvoice' => $supplierInvoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'poTable' => $supplierInvoiceProduct,
            'purchaseOrder' => $purchaseOrder,
            'fileMap1' => $fileMap1,
            'footerTgst' => $footerTgst,
        ]);
    }


    public function modalEmail($id)
    {
        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->first();
        //dd($supplierInvoice->purchase_order_id);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::find($supplierInvoice->purchase_order_id)->where('id', $supplierInvoice->purchase_order_id)->first();
        } else {
            $purchaseOrder = purchaseOrderConsumable::find($supplierInvoice->purchase_order_id)->where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $companyDetails = setting::first();
        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = poTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                if (isset($potable->gstslab)) {
                    $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($potable->gstslab / 100);
                } else {
                    $supplierInvoiceProduct[$key]['gstamount'] = 0;
                }
            } elseif ((int) $purchaseOrder->type === 2) {
                $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                $gstslab = (float) (optional($potable)->gstslab ?? 0);
                $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstslab / 100.0, 2);
            } else {
                $supplierInvoiceProduct[$key]['potable'] = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                    $purchaseOrder,
                    $sup
                );
                $potable = $supplierInvoiceProduct[$key]['potable'] ?? null;
                $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                    $purchaseOrder,
                    $sup,
                    $potable
                );
            }
        }

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $footerTgst = $purchaseOrder instanceof purchaseOrderConsumable
            && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($purchaseOrder)
            ? (float) $supplierInvoice->tgst
            : null;

        return view('supplierInvoice/modal', [
            'supplierInvoice' => $supplierInvoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'poTable' => $supplierInvoiceProduct,
            'purchaseOrder' => $purchaseOrder,
            'footerTgst' => $footerTgst,
        ]);
    }

    public function viewCartonPo($id)
    {

        $purchaseOrder = purchaseOrderConsumable::find($id);
        if ($purchaseOrder->status != 1) {
            $product = consumable::all();
            $supplier = supplier::where('type', '!=', 'Furniture')->get();
            $poTable = popTable::where('poid', $id)->get();
            $companyDetails = setting::first();
            $pricingDetails = packagingPrice::get();
            if ($purchaseOrder) {
                return view('purchaseOrder/viewCartonPo', ['purchaseOrder' => $purchaseOrder, 'product' => $product, 'supplier' => $supplier, 'poTable' => $poTable, 'companyDetails' => $companyDetails, 'pricingDetails' => $pricingDetails]);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }
    }

    public function createCartonPo()
    {
        $supplier = supplier::where('type', '!=', 'Furniture')->get();
        $po = purchaseOrderConsumable::orderBy('id', 'desc')->first();
        $companyDetails = setting::first();
        $pricingDetails = packagingPrice::get();
        return view('purchaseOrder/createCartonPo', ['supplier' => $supplier, 'po' => $po, 'companyDetails' => $companyDetails, 'pricingDetails' => $pricingDetails]);
    }

    public function storeCartonPo(Request $request)
    {
        //echo '<pre>';print_r($_REQUEST);die;
        $q = purchaseOrderConsumable::create([
            'pono' => strtoupper($request['pono']),
            'supplier_id' => $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'month' => implode(',', $request['month']),
            'buyer_orderno' => strtoupper($request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'remqty' => $request['tquantity'],
            'type' => 2,
        ]);

        foreach ($request['po'] as $po) {
            popTable::create([
                'product_id' => $po['product'],
                'poid' => $q->id,
                'quantity' => $po['quantity'],
                'unit' => 'Sq. Inch',
                'sq_inches' => 0,
                'amount' => $po['amount'],
                'gstslab' => $po['gstslab'],
                'gstamount' => $po['gstamount'],
                'box1_height' => $po['box1_height'],
                'box1_width' => $po['box1_width'],
                'box1_depth' => $po['box1_depth'],
                'box2_height' => $po['box2_height'],
                'box2_width' => $po['box2_width'],
                'box2_depth' => $po['box2_depth'],
                'box1_sqinch' => $po['box1_sqinch'],
                'box2_sqinch' => $po['box2_sqinch'],
                'box1_ply' => $po['box1_ply'],
                'box2_ply' => $po['box2_ply'],
                'box1_rate' => $po['box1_rate'],
                'box2_rate' => $po['box2_rate'],
                'box1_amount' => $po['box1_amount'],
                'box2_amount' => $po['box2_amount'],
                'line_drawing' => $po['line_drawing'],
            ]);
        }

        $companyDetails = setting::first();
        $companyDetails->ctnpo_no = $companyDetails->ctnpo_no + 1;
        $companyDetails->save();

        return redirect('/purchaseOrder/consumablePos')->with('success', 'Order was added successfully.');
    }

    public function cartonPos()
    {
        $purchaseOrder = purchaseOrderConsumable::get();
        return view('purchaseOrder/cartonPos', ['purchaseOrder' => $purchaseOrder]);
    }

    public function updateCartonPo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $poProduct = popTable::where('poid', $id)->get();

        if ($purchaseOrder && $purchaseOrder->status != 1) {
            $purchaseOrder->pono = $request['pono'];
            $purchaseOrder->supplier_id = $request['supplier_id'];
            $purchaseOrder->podate = $request['podate'];
            $purchaseOrder->del_date = $request['del_date'];
            $purchaseOrder->month = implode(',', $request['month']);
            $purchaseOrder->buyer_orderno = strtoupper($request['buyer_orderno']);
            $purchaseOrder->payterms = $request['payterms'];
            $purchaseOrder->remarks = $request['remarks'];
            $purchaseOrder->subTotal = $request['subtotalamount'];
            $purchaseOrder->tgst = $request['tgst'];
            $purchaseOrder->tquantity = $request['tquantity'];
            $purchaseOrder->remqty = $request['tquantity'];
            $purchaseOrder->tamount = $request['tamount'];

            if (isset($poProduct)) {
                foreach ($poProduct as $poProduct) {
                    $poProduct->delete();
                }
            }

            foreach ($request['po'] as $po) {
                popTable::create([
                    'product_id' => $po['product'],
                    'poid' => $id,
                    'quantity' => $po['quantity'],
                    'unit' => 'Sq. Inch',
                    'sq_inches' => 0,
                    'amount' => $po['amount'],
                    'gstslab' => $po['gstslab'],
                    'gstamount' => $po['gstamount'],
                    'box1_height' => $po['box1_height'],
                    'box1_width' => $po['box1_width'],
                    'box1_depth' => $po['box1_depth'],
                    'box2_height' => $po['box2_height'],
                    'box2_width' => $po['box2_width'],
                    'box2_depth' => $po['box2_depth'],
                    'box1_sqinch' => $po['box1_sqinch'],
                    'box2_sqinch' => $po['box2_sqinch'],
                    'box1_ply' => $po['box1_ply'],
                    'box2_ply' => $po['box2_ply'],
                    'box1_rate' => $po['box1_rate'],
                    'box2_rate' => $po['box2_rate'],
                    'box1_amount' => $po['box1_amount'],
                    'box2_amount' => $po['box2_amount'],
                    'line_drawing' => $po['line_drawing'],
                ]);
            }

            if ($purchaseOrder->save()) {
                return redirect('purchaseOrder/consumablePos')->with('success', 'PurchaseOrder was updated successfully.');
            } else {
                return redirect('purchaseOrder/consumablePos')->with('danger', 'Error occurred while saving PurchaseOrder.');
            }
        } else {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'PurchaseOrder can not be edited.');
        }
    }

    public function deleteCartonPo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if ($poRelationCount > 0) {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'Purchase Order cannot be deleted. Purchase Order exist in other relations.');
        } else {
            $poTable = popTable::where('poid', $id)->get();

            if ($poTable) {
                foreach ($poTable as $key => $value) {
                    $poTable[$key]->delete();
                }
            }

            if ($purchaseOrder) {
                if ($purchaseOrder->delete()) {
                    return redirect('/purchaseOrder/consumablePos')->with('success', 'Purchase Order deleted successfully.');
                } else {
                    return redirect('/purchaseOrder/consumablePos')->with('danger', 'Purchase Order was not found.');
                }
            }
        }
    }

    public function pdata()
    {
        $packaging = packaging::all();
        foreach ($packaging as $k => $package) {
            $packaging[$k]['product'] = $package->product;
        }
        return response()->json(['packaging' => $packaging]);
    }

    public function modalCartonPo($id)
    {
        $purchaseOrder = purchaseOrderConsumable::find($id)->where('id', $id)->first();
        $companyDetails = setting::first();
        $poTable = popTable::where('poid', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modalCartonPo', ['purchaseOrder' => $purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable]);
    }

    public function calculate_box(Request $request)
    {
        //print_r($_REQUEST);die;
        if ($request['box1_type'] == "") {
            $request['box1_type'] = "Standard Box";
        }
        if ($request['box2_type'] == "") {
            $request['box2_type'] = "Standard Box";
        }
        $box2_sqinch = 0;
        if ($request['box1_type'] == "Standard Box") {
            $box1_sqinch = $request['box1_width'] + $request['box1_depth'];
            $box1_sqinch = ceil($box1_sqinch);
            if (($box1_sqinch % 2) != 0) {
                $box1_sqinch = $box1_sqinch + 1;
            }
            $box1_sqinch = $box1_sqinch * ($request['box1_height'] + $request['box1_width'] + 2);
            $box1_sqinch = ($box1_sqinch * 2) / 100;
            $box1_sqinch = round($box1_sqinch, 2);
        }
        if ($request['box1_type'] == "Over Flap") {
            $box1_sqinch = $request['box1_width'] + $request['box1_width'] + $request['box1_depth'];
            $box1_sqinch = ceil($box1_sqinch);
            if (($box1_sqinch % 2) != 0) {
                $box1_sqinch = $box1_sqinch + 1;
            }
            $box1_sqinch = $box1_sqinch * ($request['box1_height'] + $request['box1_width'] + 2);
            $box1_sqinch = ($box1_sqinch * 2) / 100;
            $box1_sqinch = round($box1_sqinch, 2);
        }
        if ($request['box1_type'] == "Lateral Box") {
            $box1_sqinch = $request['box1_height'] + $request['box1_width'];
            $box1_sqinch = ceil($box1_sqinch);
            if (($box1_sqinch % 2) != 0) {
                $box1_sqinch = $box1_sqinch + 1;
            }
            $box1_sqinch = $box1_sqinch * ($request['box1_width'] + $request['box1_depth'] + 1);
            $box1_sqinch = ($box1_sqinch * 2) / 100;
            $box1_sqinch = round($box1_sqinch, 2);
        }

        if ($request['no_of_boxes'] == 2) {
            if ($request['box2_type'] == "Standard Box") {
                $box2_sqinch = $request['box2_width'] + $request['box2_depth'];
                $box2_sqinch = ceil($box2_sqinch);
                if (($box2_sqinch % 2) != 0) {
                    $box2_sqinch = $box2_sqinch + 1;
                }
                $box2_sqinch = $box2_sqinch * ($request['box2_height'] + $request['box2_width'] + 2);
                $box2_sqinch = ($box2_sqinch * 2) / 100;
                $box2_sqinch = round($box2_sqinch, 2);
            }
            if ($request['box2_type'] == "Over Flap") {
                $box2_sqinch = $request['box2_width'] + $request['box2_width'] + $request['box2_depth'];
                $box2_sqinch = ceil($box2_sqinch);
                if (($box2_sqinch % 2) != 0) {
                    $box2_sqinch = $box2_sqinch + 1;
                }
                $box2_sqinch = $box2_sqinch * ($request['box2_height'] + $request['box2_width'] + 2);
                $box2_sqinch = ($box2_sqinch * 2) / 100;
                $box2_sqinch = round($box2_sqinch, 2);
            }
            if ($request['box2_type'] == "Lateral Box") {
                $box2_sqinch = $request['box2_height'] + $request['box2_width'];
                $box2_sqinch = ceil($box2_sqinch);
                if (($box2_sqinch % 2) != 0) {
                    $box2_sqinch = $box2_sqinch + 1;
                }
                $box2_sqinch = $box2_sqinch * ($request['box2_width'] + $request['box2_depth'] + 1);
                $box2_sqinch = ($box2_sqinch * 2) / 100;
                $box2_sqinch = round($box2_sqinch, 2);
            }
        }

        $response['box1_sqinch'] = $box1_sqinch;
        $response['box2_sqinch'] = $box2_sqinch;
        return response()->json($response);
    }

    public function samplePurchaseOrder()
    {
        $samplePurchaseOrder = samplePurchaseOrder::get();
        return view('purchaseOrder/samplePurchaseOrder', ['samplePurchaseOrder' => $samplePurchaseOrder]);
    }

    public function createSamplePo()
    {
        $supplier = supplier::where('type', '!=', 'Consumable')->get();
        $po = purchaseOrder::where('supplier_id', '!=', '17')->orderBy('id', 'desc')->first();
        $companyDetails = setting::first();
        return view('purchaseOrder/createSamplePo', ['supplier' => $supplier, 'po' => $po, 'companyDetails' => $companyDetails]);
    }

    public function sampleData()
    {
        $sample = sample::all();
        return response()->json(['sample' => $sample]);
    }

    public function storeSample(Request $request)
    {

        $q = samplePurchaseOrder::create([
            'pono' => strtoupper($request['pono']),
            'supplier_id' => $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'ref_supplier' => strtoupper($request['ref_supplier']),
            'buyer_orderno' => strtoupper($request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'remqty' => $request['tquantity'],
        ]);
        // dd($request['po']);
        foreach ($request['po'] as $po) {
            posTable::create([
                'sample_id' => $po['product'],
                'product_id' => $po['product'],
                'poid' => $q->id,
                'quantity' => $po['quantity'],
                'unit' => $po['unit'],
                'remqty' => $po['quantity'],
                'rate' => $po['rate'],
                'amount' => $po['amount'],
                'gstslab' => $po['gstslab'],
                'gstamount' => $po['gstamount'],
                'priority' => $po['priority'],
                'delivery_point' => $po['delivery_point'],
                'legs' => $po['legs'],
                'ean' => '',
            ]);
        }

        return redirect('/samplePurchaseOrder')->with('success', 'Order was added successfully.');
    }

    public function viewSample($id)
    {

        $purchaseOrder = samplePurchaseOrder::find($id);
        if ($purchaseOrder->status != 1) {
            $product = product::all();
            $sample = sample::all();
            $supplier = supplier::where('type', '!=', 'Consumable')->get();
            $posTable = posTable::where('poid', $id)->get();
            if ($purchaseOrder) {
                return view('purchaseOrder/viewSample', ['purchaseOrder' => $purchaseOrder, 'sample' => $sample, 'supplier' => $supplier, 'posTable' => $posTable]);
            } else {
                return redirect('/samplePurchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/samplePurchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }
    }

    public function updatepos(Request $request, $id)
    {
        $purchaseOrder = samplePurchaseOrder::where('id', $id)->first();
        $posProduct = posTable::where('poid', $id)->get();
        //dd($posProduct);
        if ($purchaseOrder && $purchaseOrder->status != 1) {
            $purchaseOrder->pono = $request['pono'];
            $purchaseOrder->supplier_id = $request['supplier_id'];
            $purchaseOrder->podate = $request['podate'];
            $purchaseOrder->del_date = $request['del_date'];
            $purchaseOrder->ref_supplier = strtoupper($request['ref_supplier']);
            $purchaseOrder->buyer_orderno = strtoupper($request['buyer_orderno']);
            $purchaseOrder->payterms = $request['payterms'];
            $purchaseOrder->remarks = $request['remarks'];
            $purchaseOrder->subTotal = $request['subtotalamount'];
            $purchaseOrder->tgst = $request['tgst'];
            $purchaseOrder->tquantity = $request['tquantity'];
            $purchaseOrder->remqty = $request['tquantity'];
            $purchaseOrder->tamount = $request['tamount'];

            if (isset($posProduct)) {
                foreach ($posProduct as $posProduct) {
                    $posProduct->delete();
                }
            }

            foreach ($request['po'] as $po) {
                if ($po['consumed'] == '') {
                    posTable::create([
                        'product_id' => $po['product'],
                        'sample_id' => $po['product'],
                        'ean' => '',
                        'poid' => $id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => $po['quantity'],
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'priority' => $po['priority'],
                        'delivery_point' => $po['delivery_point'],
                        'legs' => $po['legs'],
                    ]);
                } else {
                    posTable::create([
                        'product_id' => $po['product'],
                        'sample_id' => $po['product'],
                        'ean' => '',
                        'poid' => $id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => $po['quantity'] - $po['consumed'],
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'priority' => $po['priority'],
                        'delivery_point' => $po['delivery_point'],
                        'legs' => $po['legs'],
                    ]);
                }
            }

            if ($purchaseOrder->save()) {
                return redirect('samplePurchaseOrder')->with('success', 'SamplePurchaseOrder was updated successfully.');
            } else {
                return redirect('samplePurchaseOrder')->with('danger', 'Error occurred while saving SamplePurchaseOrder.');
            }
        } else {
            return redirect('/samplePurchaseOrder')->with('danger', 'SamplePurchaseOrder can not be edited.');
        }
    }

    public function modalSample($id)
    {

        $purchaseOrder = samplePurchaseOrder::find($id)->where('id', $id)->first();
        $companyDetails = setting::first();
        $poTable = posTable::where('poid', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modalSample', ['purchaseOrder' => $purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable]);
    }

    public function deleteSample(Request $request, $id)
    {
        $purchaseOrder = samplePurchaseOrder::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if ($poRelationCount > 0) {
            return redirect('/purchaseOrder')->with('danger', 'Sample Purchase Order cannot be deleted. Purchase Order exist in other relations.');
        } else {
            $poTable = posTable::where('poid', $id)->get();

            if ($poTable) {
                foreach ($poTable as $key => $value) {
                    $poTable[$key]->delete();
                }
            }

            if ($purchaseOrder) {
                if ($purchaseOrder->delete()) {
                    return redirect('/samplePurchaseOrder')->with('success', 'Sample Purchase Order deleted successfully.');
                } else {
                    return redirect('/samplePurchaseOrder')->with('danger', 'Sample Purchase Order was not found.');
                }
            }
        }
    }

    public function exportcsvsample(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];

        return (new PosExport($fsd, $fed))->download('samplePurchaseOrder.xlsx');
    }

    public function poTallyExportSample(Request $request)
    {
        $po_ids = $request['po_ids'];
        return (new purchaseOrderTallySampleExport($po_ids))->download('poTallySampleExport.xlsx');
    }

    public function saveInvoiceReturn($id)
    {

        $supplierInvoice = supplierInvoice::find($id);

        return view('supplierInvoice/saveInvoiceReturn', ['supplierInvoice' => $supplierInvoice]);
    }

    public function updateReturn(Request $request, $id)
    {

        $supplyInvReturn = supplierInvoiceReturn::create([
            'supplier_invoice_id' => $id,
            'type' => 'partial',
        ]);

        $sir_id = $supplyInvReturn->id;

        $sirProduct = supplierInvoiceReturnProduct::where('supplier_invoice_return_id', $sir_id)->get();

        if (isset($sirProduct)) {
            foreach ($sirProduct as $sirProduct) {
                $sirProduct->delete();
            }
        }
        // dd($request['po']);
        foreach ($request['po'] as $po) {

            if ($po['quantity'] != null && $po['product_id'] != null) {
                supplierInvoiceReturnProduct::create([
                    'product_id' => $po['product_id'],
                    'qty' => $po['quantity'],
                    'supplier_invoice_return_id' => $sir_id,
                    'supplier_invoice_id' => $id,
                ]);
            }
        }
        return redirect('supplierInvoice')->with('success', 'Product return has been submitted successfully.');
    }


    public function serviceModal($id)
    {
        $supplierInvoice = supplierServiceInvoice::where('id', $id)->first();
        //dd($supplierInvoice);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = Service::where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $supplierInvoiceProduct = serviceProductTable::where('supplier_invoice_id', $id)->with('sub_service_product')->get();
        $subsupplierInvoiceProduct = SubServiceProductTable::where('supplier_invoice_id', $id)->with('serviceProductTableIN')->get();

        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = serviceTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
            }
            if (isset($supplierInvoiceProduct[$key]['potable']->gstslab)) {
                $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($supplierInvoiceProduct[$key]['potable']->gstslab / 100);
            } else {
                $supplierInvoiceProduct[$key]['gstamount'] = 0;
            }
        }
        //dd($supplierInvoiceProduct);
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('supplierInvoice/service_modal', ['supplierInvoice' => $supplierInvoice, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $supplierInvoiceProduct, 'purchaseOrder' => $purchaseOrder, 'fileMap1' => $fileMap1, 'subsupplierInvoiceProduct' => $subsupplierInvoiceProduct]);
    }


    public function serviceApprove($id)
    {

        $supplierInvoice = supplierServiceInvoice::where('id', $id)->first();
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = Service::where('id', $supplierInvoice->purchase_order_id)->first();
            ServiceInvoiceQuantityService::syncAllForPo((int) $purchaseOrder->id);
        }
        $supplier = supplier::find($purchaseOrder->supplier_id);
        $bills = servicePurchaseBill::where('purchaseOrder_id', $purchaseOrder->id)->first();
        $supplierInvoiceProduct = serviceProductTable::where('supplier_invoice_id', $id)->get();
        $subServicePoduct = SubServiceProductTable::where('supplier_invoice_id', $id)->with('serviceProductTableIN')->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = serviceTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
            }
            if (isset($supplierInvoiceProduct[$key]['potable']->gstslab)) {
                $supplierInvoiceProduct[$key]['gstamount'] = round(
                    (float) $sup->amount * ((float) $supplierInvoiceProduct[$key]['potable']->gstslab / 100),
                    2
                );
            } else {
                $supplierInvoiceProduct[$key]['gstamount'] = 0;
            }
        }
        $admins = User::whereIn('role', ['Admin'])->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Supplier Invoice no. - ' . $supplierInvoice->supplier_invoice_number . ' has been approved by Puran.',
                'is_read' => 0
            ]);
        }
        //    return $supplierInvoiceProduct;
        return view('supplierInvoice/create_via_serviceInvoice', ['bills' => $bills, 'supplierInvoiceProduct' => $supplierInvoiceProduct, 'purchaseOrder' => $purchaseOrder, 'supplier' => $supplier, 'supplierInvoice' => $supplierInvoice, 'subServicePoduct' => $subServicePoduct]);
    }

    public function store_service_approve(Request $request, $id)
    {
        // return $request->all();
        $supplierInvoice = supplierServiceInvoice::find($id);

        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = Service::where('id', $supplierInvoice->purchase_order_id)->first();
        }


        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $carbonDate = Carbon::parse($request['supp_inv_date']);
            $Batchmonth = $carbonDate->format('m') . $carbonDate->format('y');

            $alreadyBilledTotal = (float) servicePurchaseBill::where('purchaseOrder_id', $purchaseOrder->id)->sum('total');
            $billSubtotal = round((float) ($request['pbSubTotal'] ?? 0), 2);
            $billGst = round((float) ($request['pbGST'] ?? 0), 2);
            $billTotal = round($billSubtotal + $billGst, 2);
            $billQty = round((float) ($request['pbQty'] ?? 0), 2);

            ServiceInvoiceQuantityService::syncAllForPo((int) $purchaseOrder->id);
            $purchaseOrder->refresh();

            if (ServiceInvoiceQuantityService::isPoComplete((int) $purchaseOrder->id)) {
                $remainingAmount = round((float) $purchaseOrder->tamount - $alreadyBilledTotal, 2);
                if ($remainingAmount > 0 && abs($remainingAmount - $billTotal) > 0.001) {
                    $billTotal = $remainingAmount;
                    $billGst = round($billTotal - $billSubtotal, 2);
                }
            }

            $q = servicePurchaseBill::create([
                'purchaseOrder_id' => $purchaseOrder->id,
                'ewaybill' => strtoupper($request['ewaybill']),
                'supp_inv_no' => strtoupper($request['supp_inv_no']),
                'supp_inv_date' => $request['supp_inv_date'],
                'quantity' => $billQty,
                'subtotal' => $billSubtotal,
                'gst' => $billGst,
                'freight' => round((float) ($request['freight'] ?? 0), 2),
                'total' => $billTotal,
                'tdsTotal' => round((float) ($request['tdsTotal'] ?? 0), 2),
                'invoice_status' => 1,
                'supplier_invoice_id' => $supplierInvoice->id
            ]);
            foreach ($request['pb'] as $pb) {
                if ((isset($pb['receiveqty']) && $pb['receiveqty'] > 0) || (isset($pb['percentage']) && $pb['percentage'] > 0)) {

                    $poProduct = serviceTable::where('poid', $purchaseOrder->id)->where('product_id', $pb['product'])->first();
                    $soProduct = serviceProductTable::where('supplier_invoice_id', $supplierInvoice->id)->where('purchase_order_id', $purchaseOrder->id)->where('product_id', $pb['product'])->first();
                    $p = ServicePbTable::create([
                        'product_id' => $pb['product'],
                        'purchaseOrder_id' => $q->purchaseOrder_id,
                        'purchaseBill_id' => $q->id,
                        'orderqty' => $poProduct->remqty + $soProduct->quantity,
                        'receiveqty' => ($pb['unit'] == 'Hours') ? $pb['receiveqty'] : null,
                        'receivepercentage' => ($pb['unit'] == 'Count') ? $pb['percentage'] : null,
                        'remainingqty' => $poProduct->remqty + $soProduct->quantity - ($pb['unit'] == 'Hours' ? $pb['receiveqty'] : 0), // Adjust remainingqty accordingly
                        'rate' => round((float) ($pb['rate'] ?? 0), 2),
                        'unit' => $pb['unit'],
                        'amount' => round((float) ($pb['amount'] ?? 0), 2),
                        'location' => $pb['location'],
                    ]);
                    if (isset($pb['sub_name']) && isset($pb['sub_rate'])) {
                        foreach ($pb['sub_name'] as $index => $name) {
                            if (isset($pb['sub_rate'][$index])) {
                                SubServicePbTable::create([
                                    'purchase_bill_id' => $q->id,
                                    'pb_id' => $p->id,
                                    'product_id' => $p->product_id,
                                    'sub_name' => $name,
                                    'sub_receiveqty' => $pb['sub_quantity'][$index] ?? 0,
                                    'sub_receivepercentage' => $pb['sub_percentage'][$index] ?? 0,
                                    'sub_rate' => round((float) ($pb['sub_rate'][$index] ?? 0), 2),
                                    'sub_amount' => round((float) ($pb['sub_amount'][$index] ?? 0), 2),
                                    'sub_gstslab' => round((float) ($pb['sub_gstslab'][$index] ?? 0), 2),
                                    'sub_gstamount' => round((float) ($pb['sub_gstamount'][$index] ?? 0), 2),
                                ]);
                            }
                            $subPoProduct = SubServiceTable::where('serviceTable_id', $poProduct->id)->where('sub_name', $name)->first();
                            $subsoProduct = SubServiceProductTable::where('supplier_invoice_product_id', $soProduct->id)->where('sub_name', $name)->first();

                            if ($subPoProduct && $subsoProduct) {
                                $approvedSubAmount = round((float) ($pb['sub_amount'][$index] ?? 0), 2);

                                if (($pb['unit'] ?? '') === 'Hours') {
                                    $approvedSubQty = (float) ($pb['sub_quantity'][$index] ?? 0);

                                    if ($approvedSubQty > (float) $subsoProduct->sub_quantity + 0.0001) {
                                        return redirect()->back()->withInput()->with(
                                            'danger',
                                            "Approved qty for {$name} cannot exceed invoiced qty ({$subsoProduct->sub_quantity})."
                                        );
                                    }

                                    $subsoProduct->sub_quantity = $approvedSubQty;
                                } else {
                                    $approvedSubPct = round((float) ($pb['sub_percentage'][$index] ?? 0), 2);

                                    if ($approvedSubPct > round((float) $subsoProduct->sub_percentage, 2) + 0.0001) {
                                        return redirect()->back()->withInput()->with(
                                            'danger',
                                            "Approved percentage for {$name} cannot exceed invoiced percentage ({$subsoProduct->sub_percentage}%)."
                                        );
                                    }

                                    $subsoProduct->sub_percentage = $approvedSubPct;
                                }

                                $subsoProduct->sub_amount = $approvedSubAmount;
                                $subsoProduct->sub_gstamount = round(
                                    (float) ($pb['sub_gstamount'][$index] ?? $subsoProduct->sub_gstamount),
                                    2
                                );
                                $subsoProduct->save();
                            }
                        }
                    }

                    $soProduct->quantity = ($pb['unit'] == 'Hours') ? ($pb['receiveqty'] ?? $soProduct->quantity) : $soProduct->quantity;
                    $soProduct->percentage = ($pb['unit'] == 'Count')
                        ? round((float) ($pb['percentage'] ?? $soProduct->percentage), 2)
                        : $soProduct->percentage;
                    $soProduct->amount = round((float) ($pb['amount'] ?? $soProduct->amount), 2);
                    $soProduct->total = round(
                        (float) ($pb['amount'] ?? $soProduct->amount) + (float) ($pb['gstamount'] ?? 0),
                        2
                    );
                    $soProduct->save();

                    $supplier = supplier::find($purchaseOrder->supplier_id);
                }
            }

            ServiceInvoiceQuantityService::syncAllForPo((int) $purchaseOrder->id);

            SubServiceProductTable::where('supplier_invoice_id', $supplierInvoice->id)->get()->each(function ($line) use ($purchaseOrder) {
                $sub = SubServiceTable::where('po_id', $purchaseOrder->id)
                    ->where('product_id', $line->product_id)
                    ->where('sub_name', $line->sub_name)
                    ->first();
                if ($sub) {
                    ServiceInvoiceQuantityService::snapshotSubRemainingOnInvoiceLine($line, $sub);
                }
            });
        }
        $supplierInvoice->is_approved = 1;
        $supplierInvoice->batch_no = $supplier->short_name . $Batchmonth . rand(1000, 9999);
        $supplierInvoice->tquantity = $billQty ?? ($request['pbQty'] ?? $supplierInvoice->tquantity);
        $supplierInvoice->subTotal = $billSubtotal ?? ($request['pbSubTotal'] ?? $supplierInvoice->subTotal);
        $supplierInvoice->tamount = $billTotal ?? ($request['pbTotal'] ?? $supplierInvoice->tamount);
        $supplierInvoice->tgst = isset($billGst) ? $billGst : (($request['pbGST'] ?? null) !== null ? $request['pbGST'] : $supplierInvoice->tgst);
        $supplierInvoice->update();
        return redirect('/supplier-service-Invoice')->with('success', 'Approved successfully.');
    }
    public function serviceIndex()
    {

        $supplierInvoice = supplierServiceInvoice::with(['user.supplier', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->get();

        $product = product::get();

        return view('supplierInvoice/service_index', ['supplierInvoice' => $supplierInvoice, 'product' => $product]);
    }



       public function store_approvecarton(Request $request, $id)
    {
        $request->validate([
            'supp_inv_no' => 'required|string',
            'invoice_date' => 'required|date',
            'ewaybill' => 'nullable|string',
            'tdsTotal' => 'nullable|numeric',
            'pb' => 'required|array|min:1',
            'pb.*.product' => 'required',
            'pb.*.receiveqty_box_1' => 'nullable|numeric|min:0',
            'pb.*.receiveqty_box_2' => 'nullable|numeric|min:0',
        ]);

        $supplierInvoice = supplierInvoice::find($id);
        if (!$supplierInvoice) {
            return back()->with('error', 'Supplier invoice not found.');
        }
        if ($supplierInvoice->purchase_order_type !== 'Carton') {
            return back()->with('error', 'This approval flow is only for carton invoices.');
        }
        if ((int) $supplierInvoice->is_approved === 1) {
            return back()->with('error', 'This carton invoice is already approved.');
        }

        $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
        if (!$purchaseOrder) {
            return back()->with('error', 'Purchase order not found for this invoice.');
        }

        $supplier = supplier::find($purchaseOrder->supplier_id);
        if (!$supplier) {
            return back()->with('error', 'Supplier not found for this purchase order.');
        }

        $hasReceipt = false;

        try {
            DB::transaction(function () use (
                $request,
                $supplierInvoice,
                $purchaseOrder,
                $supplier,
                &$hasReceipt
            ) {
                $billSubTotal = 0.0;
                $billGst = 0.0;
                $billQty = 0.0;

                $q = PurchaseBillCarton::create([
                    'purchaseOrder_id' => $purchaseOrder->id,
                    'ewaybill' => strtoupper((string) $request['ewaybill']),
                    'supp_inv_no' => strtoupper((string) $request['supp_inv_no']),
                    'supp_inv_date' => $request['invoice_date'],
                    'quantity' => 0,
                    'subtotal' => 0,
                    'gst' => 0,
                    'total' => 0,
                    'tdsTotal' => $request['tdsTotal'] ?? 0,
                    'invoice_status' => 1,
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'supplier_id' => $supplierInvoice->supplier_id,
                ]);

                foreach ($request['pb'] as $pb) {
                    $r1 = (float) ($pb['receiveqty_box_1'] ?? 0);
                    $r2 = (float) ($pb['receiveqty_box_2'] ?? 0);

                    if ($r1 <= 0 && $r2 <= 0) {
                        continue;
                    }
                    $hasReceipt = true;

                    $productId = (int) $pb['product'];
                    $poProduct = popTable::where('poid', $purchaseOrder->id)
                        ->where('product_id', $productId)
                        ->first();
                    if (!$poProduct) {
                        throw new \RuntimeException('Carton PO line not found for one of the products.');
                    }

                    $soProduct = soTable::where('supplier_invoice_id', $supplierInvoice->id)
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->where('product_id', $productId)
                        ->first();
                    if (!$soProduct) {
                        throw new \RuntimeException('Supplier invoice line not found for one of the carton products.');
                    }

                    $orderqty = (float) $poProduct->remqty_box1 + (float) $soProduct->quantity;
                    $orderqty2 = (float) $poProduct->remqty_box2 + (float) $soProduct->quantity2;
                    if ($r1 > $orderqty || $r2 > $orderqty2) {
                        throw new \RuntimeException('Receive quantity cannot exceed available carton quantity.');
                    }

                    $remainingqty = $orderqty - $r1;
                    $remainingqty2 = $orderqty2 - $r2;

                    $rate1 = (float) ($poProduct->box1_rate ?? 0);
                    $rate2 = (float) ($poProduct->box2_rate ?? 0);
                    $lineAmount = round(($rate1 * $r1) + ($rate2 * $r2), 2);
                    $gstSlab = (float) ($soProduct->gst ?? $poProduct->gstslab ?? 0);
                    $lineGst = round(($lineAmount * $gstSlab) / 100, 2);
                    $billSubTotal += $lineAmount;
                    $billGst += $lineGst;
                    $billQty += $r1 + $r2;

                    PbTableCorton::create([
                        'product_id' => $productId,
                        'purchaseOrder_id' => $q->purchaseOrder_id,
                        'purchaseBill_id' => $q->id,
                        'orderqty' => $orderqty,
                        'orderqty2' => $orderqty2,
                        'receiveqty' => $r1,
                        'receiveqty2' => $r2,
                        'remainingqty' => $remainingqty,
                        'remainingqty2' => $remainingqty2,
                        'rate' => $rate1,
                        'rate2' => $rate2,
                        'amount' => $lineAmount,
                    ]);

                    $poProduct->remqty_box1 = $remainingqty;
                    $poProduct->remqty_box2 = $remainingqty2;
                    $poProduct->save();

                    $product = packaging::where('product_id', $productId)->first();
                    if ($product) {
                        $open_balance = (float) $product->box_1_qty;
                        $open_balance2 = (float) $product->box_2_qty;
                        $product->box_1_qty = $open_balance + $r1;
                        $product->box_2_qty = $open_balance2 + $r2;
                        $product->save();

                        if ((int) $purchaseOrder->address_option !== 100) {
                            StockLogCarton::create([
                                'product_id' => $productId,
                                'voucher_no' => strtoupper((string) $request['supp_inv_no']),
                                'supplier_inv_no' => strtoupper((string) $request['supp_inv_no']),
                                'ref_no' => $purchaseOrder->ref_supplier,
                                'quantity' => $r1,
                                'quantity2' => $r2,
                                'opening_balance' => $open_balance,
                                'opening_balance2' => $open_balance2,
                                'remaining_stock' => $product->box_1_qty,
                                'remaining_stock2' => $product->box_2_qty,
                                'type' => 1,
                                'supplier_name' => $supplier->c_name,
                                'entity_id' => $q->id,
                            ]);
                        }
                    }

                }

                if (!$hasReceipt) {
                    throw new \RuntimeException('Enter a receive quantity on at least one carton line.');
                }

                $billSubTotal = round($billSubTotal, 2);
                $billGst = round($billGst, 2);
                $q->quantity = $billQty;
                $q->subtotal = $billSubTotal;
                $q->gst = $billGst;
                $q->total = round($billSubTotal + $billGst, 2);
                $q->save();

                $statusremqty = 0.0;
                $statusremqty2 = 0.0;
                $poTableCheck = popTable::where('poid', $purchaseOrder->id)->get();
                foreach ($poTableCheck as $poTableChecks) {
                    $statusremqty += (float) $poTableChecks->remqty_box1;
                    $statusremqty2 += (float) $poTableChecks->remqty_box2;
                }
                $purchaseOrder->status = ($statusremqty == 0.0 && $statusremqty2 == 0.0) ? 1 : 0;
                $purchaseOrder->remqty = $statusremqty;
                $purchaseOrder->save();

                $supplierInvoice->is_approved = 1;
                $supplierInvoice->save();
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect('/supplierInvoice/carton')->with('success', 'Approved successfully.');
    }
    public function approvecarton($id)
    {
        $supplierInvoicecar = supplierInvoice::where('id', $id)->first();
        $userSplier = supplier::where('id', $supplierInvoicecar->supplier_id)->first();
        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $supplierInvoicecar->id)->get();

        if ($supplierInvoicecar->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoicecar->purchase_order_id)->first();
            $poTable = poTable::where('poid', $supplierInvoicecar->purchase_order_id)->get();
            $type = 1;
            $poModel = poTable::class;
        } elseif ($supplierInvoicecar->purchase_order_type == 'Consumable') {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoicecar->purchase_order_id)->first();
            $poTable = pocTable::where('poid', $supplierInvoicecar->purchase_order_id)->get();
            $type = 2;
            $poModel = pocTable::class;
        } elseif ($supplierInvoicecar->purchase_order_type == 'Carton') {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoicecar->purchase_order_id)->first();
            $poTable = popTable::where('poid', $supplierInvoicecar->purchase_order_id)->get();
            $type = 3;
            $poModel = popTable::class;
        }

        // unit types map
        $unitTypes = UnitType::all()->keyBy('name');
        foreach ($poTable as $key => $row) {
            $unitName = $row['unit'];
            $poTable[$key]['data_type'] = $unitTypes[$unitName]->data_type ?? 'unknown';
        }

        // attach potable info for each invoice product
        foreach ($supplierInvoiceProduct as $key => $sup) {
            $supplierInvoiceProduct[$key]['potable'] = $poModel::where('poid', $sup->purchase_order_id)
                ->where('product_id', $sup->product_id)
                ->first();
        }

        // return $supplierInvoiceProduct;
        return view('supplierInvoice/create_via_suppinvoicecarton', [
            'supplierInvoicecar'     => $supplierInvoicecar,
            'purchaseOrder'          => $purchaseOrder,
            'poTable'                => $poTable,
            'type'                   => $type,
            'id'                     => $supplierInvoicecar->purchase_order_id,
            'userSplier'             => $userSplier,
            'supplierInvoiceProduct' => $supplierInvoiceProduct
        ]);
    }


    public function indexmulti(Request $request)
    {

        $search = $request->input('search');

        $supplierInvoices = supplierInvoice::with(['supplier', 'user.supplier', 'supplierData'])
            ->whereNotNull('mulitple_po')
            ->where('purchase_order_type', 'Furniture')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_invoice_number', 'like', "%{$search}%")
                        ->orWhere('internal_invoice_number', 'like', "%{$search}%")
                        ->orWhere('eway_bill_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q2) use ($search) {
                            $q2->where('c_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('supplierInvoice/indexmulti', ['supplierInvoices' => $supplierInvoices]);
    }


    public function approvemulti($id)
    {
        $supplierInvoice = SupplierInvoice::find($id);
        if (!$supplierInvoice) {
            return back()->with('error', 'Supplier invoice not found.');
        }

        $poIds = array_filter(explode(',', $supplierInvoice->purchase_order_id));
        if (empty($poIds)) {
            return back()->with('error', 'No linked purchase orders found for this invoice.');
        }

        $purchaseOrders = PurchaseOrder::whereIn('id', $poIds)->get();
        if ($purchaseOrders->isEmpty()) {
            return back()->with('error', 'No purchase orders found for this invoice.');
        }

        $supplierNames = $purchaseOrders->pluck('supplier.c_name')->unique()->values();
        $poNumbers = $purchaseOrders->pluck('pono')->unique()->values();
        $deliveryDates = $purchaseOrders->pluck('del_date')->unique()->values();
        $supplierRefs = $purchaseOrders->pluck('ref_supplier')->unique()->values();


        $firstPO = $purchaseOrders->first();
        $supplier = Supplier::find($firstPO->supplier_id);

        $bills = PurchaseBill::whereIn('purchaseOrder_id', $poIds)->first();

        $supplierInvoiceProducts = SupplierInvoiceProduct::where('supplier_invoice_id', $id)->get();

        $purchaseOrdersById = $purchaseOrders->keyBy('id');

        foreach ($supplierInvoiceProducts as $key => $sup) {
            if ($supplierInvoice->purchase_order_type === 'Furniture') {
                $linePoId = (int) $sup->purchase_order_id;
                $potable = PoTable::where('poid', $linePoId)
                    ->where('product_id', $sup->product_id)
                    ->first();

                $supplierInvoiceProducts[$key]['potable'] = $potable;
                $gstSlab = $potable->gstslab ?? 0;
                $linePo = $purchaseOrdersById->get($linePoId);
                $supplierInvoiceProducts[$key]['mulitple_po'] = $linePo->pono ?? $sup->mulitple_po;
            } else {
                $gstSlab = 0;
            }

            $supplierInvoiceProducts[$key]['gstamount'] = $sup->amount * ($gstSlab / 100);
        }

        $admins = User::where('role', 'Admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Supplier Invoice no. - '
                    . $supplierInvoice->supplier_invoice_number
                    . ' has been approved by ' . auth()->user()->name,
                'is_read' => 0
            ]);
        }

        return view('supplierInvoice/create_via_suppinvoiceMulti', [
            'bills' => $bills,
            'supplierInvoiceProduct' => $supplierInvoiceProducts,
            'purchaseOrder' => $firstPO,
            'allPurchaseOrders' => $purchaseOrders,
            'supplier' => $supplier,
            'supplierInvoice' => $supplierInvoice,
            'supplierNames' => $supplierNames,
            'poNumbers' => $poNumbers,
            'deliveryDates' => $deliveryDates,
            'supplierRefs' => $supplierRefs,
        ]);
    }




     public function store_approvemulti(Request $request, $id)
    {
        // return $request['mulitple_po_purchaseBill'];
        $poIds = array_values(array_filter(array_map('trim', explode(',', (string)$request->mulitple_po_purchaseBill))));
        $poIdsCsv = implode(',', $poIds);
        //return $poIdsCsv;
        $purchaseOrders = PurchaseOrder::whereIn('pono', $poIds)->get();
        $allPoIds = $purchaseOrders->pluck('id')->implode(',');

        $exists = purchaseBill::where('getinserailno', $request['getinserailno'])->exists();

        if ($exists) {
            return redirect('/supplierInvoice/multi')->with('danger', 'Get In Serial No already exists.');
        }
        $supplierInvoice = supplierInvoice::find($id);
        if (! $supplierInvoice) {
            return redirect('/supplierInvoice/multi')->with('danger', 'Supplier invoice not found.');
        }

        if ($supplierInvoice->purchase_order_type != 'Furniture') {
            return redirect('/supplierInvoice/multi')->with('danger', 'Multi-PO approve is only supported for Furniture invoices here.');
        }

        try {
            DB::transaction(function () use ($request, $supplierInvoice, $allPoIds, $poIdsCsv) {
                $carbonDate = Carbon::parse($request['supp_inv_date']);
                $Batchmonth = $carbonDate->format('m') . $carbonDate->format('y');
                $Batchmonth1 = $carbonDate->format('m') . '/' . $carbonDate->format('y');

                $rounded = 0;
                if ($request['tdsTotal']) {
                    $number = $request['tdsTotal'];
                    $intPart = floor($number);
                    $decimal = $number - $intPart;

                    if ($decimal < 0.5) {
                        $rounded = $intPart;
                    } else {
                        $rounded = $intPart + 1;
                    }
                }

                $purchaseBill = purchaseBill::create([
                    'purchaseOrder_id' => $allPoIds,
                    'ewaybill' => strtoupper($request['ewaybill']),
                    'supp_inv_no' => strtoupper($request['supp_inv_no']),
                    'supp_inv_date' => $request['supp_inv_date'],
                    'quantity' => $request['pbQty'],
                    'subtotal' => $request['pbSubTotal'],
                    'gst' => $request['pbGST'],
                    'freight' => $request['freight'],
                    'total' => $request['pbTotal'],
                    'totaldiscount' => $request['totaldiscount'],
                    'tdsTotal' => $rounded,
                    'roundoff' => $request['roundoff'],
                    'mulitple_po_purchaseBill' => $poIdsCsv,
                    'invoice_status' => 1,
                    'getinserailno' => $request['getinserailno'],
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'supplier_id' => $supplierInvoice->supplier_id,
                ]);

                $usedPoIds = [];
                $totalquantitybatch = 0;
                $hasReceipt = false;

                $supplier = supplier::find($supplierInvoice->supplier_id);
                if (! $supplier) {
                    throw new \RuntimeException('Supplier not found for this invoice.');
                }

                $prefix = $supplier->short_name . $Batchmonth;

                $batch = Batch::where('date', $Batchmonth1)
                    ->whereRaw('LEFT(batch_no, LENGTH(batch_no) - 4) = ?', [$prefix])
                    ->first();

                if (! $batch) {
                    $batch = Batch::create([
                        'batch_no' => $supplier->short_name . $Batchmonth . rand(1000, 9999),
                        'quantity' => 0,
                        'date' => $Batchmonth1,
                        'supplier_id' => $supplier->id,
                    ]);
                }

                if ($batch) {
                    $supplierInvoice->batch_no = $batch->batch_no;
                }

                foreach ($request['pb'] as $pb) {
                    if ($pb['receiveqty'] <= 0) {
                        continue;
                    }

                    $purchaseOrderwf = ! empty($pb['poid'])
                        ? PurchaseOrder::find((int) $pb['poid'])
                        : null;
                    if (! $purchaseOrderwf) {
                        $poidRef = trim((string) ($pb['mulitple_po'] ?? ''));
                        $purchaseOrderwf = PurchaseOrder::where('pono', $poidRef)->first();
                        if (! $purchaseOrderwf && $poidRef !== '' && ctype_digit($poidRef)) {
                            $purchaseOrderwf = PurchaseOrder::find((int) $poidRef);
                        }
                    }
                    if (! $purchaseOrderwf) {
                        throw new \RuntimeException(
                            'Purchase order not found for line (PO ref: ' . ($pb['mulitple_po'] ?? 'unknown') . ').'
                        );
                    }

                    $usedPoIds[] = $purchaseOrderwf->id;

                    $poProduct = poTable::where('poid', $purchaseOrderwf->id)
                        ->where('product_id', $pb['product'])
                        ->first();

                    $soProduct = soTable::where('supplier_invoice_id', $supplierInvoice->id)
                        ->where('purchase_order_id', $purchaseOrderwf->id)
                        ->where('product_id', $pb['product'])
                        ->first();

                    if (! $poProduct || ! $soProduct) {
                        throw new \RuntimeException(
                            'Purchase order line or supplier invoice line not found for product id ' . ($pb['product'] ?? '?') . '.'
                        );
                    }

                    $orderQty = $poProduct->remqty + $soProduct->quantity;
                    $remainingQty = $orderQty - $pb['receiveqty'];
                    $amountdiscount = $pb['amount'] - $pb['rem_discount'];

                    pbTable::create([
                        'product_id' => $pb['product'],
                        'ean' => $pb['EAN'],
                        'purchaseOrder_id' => $poProduct->poid,
                        'purchaseBill_id' => $purchaseBill->id,
                        'orderqty' => $orderQty,
                        'receiveqty' => $pb['receiveqty'],
                        'remainingqty' => $remainingQty,
                        'rate' => $pb['rate'],
                        'amount' => $amountdiscount,
                        'discount_type' => $pb['discount_type'],
                        'discount' => $pb['rem_discount'],
                        'location' => $pb['location'],
                        'mulitple_po_purchaseBill' => $purchaseOrderwf->id,
                    ]);
                    $hasReceipt = true;

                    $poProduct->remqty = $remainingQty;
                    $poProduct->remaining_discount += ($soProduct->discount - $pb['rem_discount']);
                    $poProduct->save();

                    if (trim($pb['location']) != '') {
                        $plocation = productLocations::where('product_id', $pb['product'])
                            ->where('location', $pb['location'])
                            ->first();

                        if ($plocation) {
                            $plocation->quantity += $pb['receiveqty'];
                            $plocation->save();
                        } else {
                            productLocations::create([
                                'product_id' => $pb['product'],
                                'location' => $pb['location'],
                                'quantity' => $pb['receiveqty'],
                            ]);
                        }
                    }

                    $product = product::find($pb['product']);
                    if ($product) {
                        $open_balance = $product->quantity;
                        $product->quantity += $pb['receiveqty'];
                        $product->location = $pb['location'];
                        $product->save();

                        if ($batch) {
                            $batchproduct = BatchProduct::firstOrCreate(
                                ['batch_id' => $batch->id, 'product_id' => $pb['product']],
                                [
                                    'quantity' => 0,
                                    'date' => now()->toDateString(),
                                    'supp_in_no' => strtoupper($request['supp_inv_no']),
                                ]
                            );
                            $batchproduct->quantity += $pb['receiveqty'];
                            $batchproduct->save();

                            $this->ensureSupplierInvoiceReferenceNumber($supplierInvoice);
                            if ($supplierInvoice->isDirty()) {
                                $supplierInvoice->save();
                            }
                            $formattedReferenceNumber = $supplierInvoice->supplier_invoice_number . '(' . (int) $pb['receiveqty'] . '/0)';
                            stockLog::create([
                                'product_id' => $pb['product'],
                                'voucher_no' => strtoupper($request['supp_inv_no']),
                                'supplier_inv_no' => strtoupper($request['supp_inv_no']),
                                'ref_no' => $purchaseOrderwf->ref_supplier ?? '',
                                'quantity' => $pb['receiveqty'],
                                'opening_balance' => $open_balance,
                                'remaining_stock' => $product->quantity,
                                'type' => 1,
                                'supplier_name' => $supplier->c_name ?? '',
                                'entity_id' => $purchaseBill->id,
                                'batch_no' => $batch->batch_no,
                                'batch_balance' => $batchproduct->quantity,
                                'reference_number' => $formattedReferenceNumber,
                                'reference_quantity' => (int) $pb['receiveqty'],
                            ]);

                            $this->syncApprovedRefToBothTables(
                                $supplierInvoice,
                                (string) $batch->batch_no,
                                (int) $pb['product'],
                                (int) $pb['receiveqty']
                            );
                            $this->syncApprovedRefToLegacySupplierReferenceTables(
                                $supplierInvoice,
                                (string) $batch->batch_no,
                                (int) $pb['product'],
                                (int) $pb['receiveqty']
                            );

                            $totalquantitybatch += $pb['receiveqty'];
                        }
                    }
                }

                if (! $hasReceipt) {
                    throw new \RuntimeException('No purchase bill lines were created. Approval aborted.');
                }

                foreach (array_unique($usedPoIds) as $poId) {
                    $poUpdate = purchaseOrder::find($poId);
                    if ($poUpdate) {
                        $statusremqty = poTable::where('poid', $poUpdate->id)->sum('remqty');
                        $poUpdate->remqty = $statusremqty;
                        $poUpdate->status = ($statusremqty == 0) ? 1 : 0;
                        $poUpdate->save();
                    }
                }

                $supplierInvoice->is_approved = 1;
                if ($batch) {
                    $batch->quantity += $totalquantitybatch;
                    $batch->save();
                }
                $supplierInvoice->save();
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect('/supplierInvoice/multi')->with(
                'danger',
                'Approval failed: ' . $e->getMessage()
            );
        }

        return redirect('/supplierInvoice/multi')->with('success', 'Approved successfully.');
    }




    public function modalmulti($id)
    {
        $supplierInvoice = supplierInvoice::findOrFail($id);
        $companyDetails = setting::first();

        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $isFurniture = $supplierInvoice->purchase_order_type === 'Furniture';
        if ($isFurniture) {
            $poIds = array_values(array_filter(array_map('intval', array_map(
                'trim',
                explode(',', (string) $supplierInvoice->purchase_order_id)
            ))));
            $purchaseOrders = purchaseOrder::whereIn('id', $poIds)->get()->keyBy('id');
            $firstPOId = $poIds[0] ?? null;
            $firstPO = $firstPOId ? ($purchaseOrders[$firstPOId] ?? purchaseOrder::find($firstPOId)) : null;
        } else {
            $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
            $purchaseOrders = purchaseOrderConsumable::whereIn('id', $poIds)->get()->keyBy('id');
            $firstPOId = $poIds[0] ?? null;
            $firstPO = $firstPOId ? ($purchaseOrders[$firstPOId] ?? purchaseOrderConsumable::find($firstPOId)) : null;
        }

        $podate = $purchaseOrders->pluck('podate')->filter()->unique()->values();
        $del_date = $purchaseOrders->pluck('del_date')->filter()->unique()->values();

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)
            ->with(['product', 'consumable'])
            ->get();

        foreach ($supplierInvoiceProduct as $key => $sup) {
            $poid = $sup->purchase_order_id;

            if ($isFurniture) {
                $poTableRow = poTable::where('poid', $poid)
                    ->where('product_id', $sup->product_id)
                    ->first();

                $supplierInvoiceProduct[$key]['potable'] = $poTableRow;
                $gstRate = $poTableRow->gstslab ?? 0;
                $supplierInvoiceProduct[$key]['rate'] = $poTableRow->rate ?? 0;
                $supplierInvoiceProduct[$key]['gstslab'] = $gstRate;
                $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($gstRate / 100);
                $linePo = $purchaseOrders->get($poid);
                $supplierInvoiceProduct[$key]['mulitple_po'] = $linePo->pono ?? $sup->mulitple_po;
            } else {
                $po = $purchaseOrders->get($poid) ?? purchaseOrderConsumable::find($poid);
                if (! $po) {
                    continue;
                }
                if ((int) $po->type === 2) {
                    $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $poid)
                        ->where('product_id', $sup->product_id)
                        ->first();
                    $potable = $supplierInvoiceProduct[$key]['potable'];
                    $gstslab = (float) (optional($potable)->gstslab ?? $sup->gst ?? 0);
                    $supplierInvoiceProduct[$key]['gstslab'] = $gstslab;
                    $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstslab / 100.0, 2);
                } else {
                    $potable = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                        $po,
                        $sup
                    );
                    $supplierInvoiceProduct[$key]['potable'] = $potable;
                    $gstslab = (float) (optional($potable)->gstslab ?? $sup->gst ?? 0);
                    $supplierInvoiceProduct[$key]['gstslab'] = $gstslab;
                    $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                        $po,
                        $sup,
                        $potable
                    );
                }
                $supplierInvoiceProduct[$key]['mulitple_po'] = $po->pono;
            }
        }

        $print = request('print') == 1 ? 1 : 0;

        $footerTgst = null;
        if (! $isFurniture) {
            $hasMonthEndPo = $purchaseOrders->contains(
                fn ($po) => $po instanceof purchaseOrderConsumable
                    && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)
            );
            if ($hasMonthEndPo) {
                $footerTgst = (float) $supplierInvoice->tgst;
            }
        }

        return view('supplierInvoice/modalmulti', [
            'supplierInvoice' => $supplierInvoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'poTable' => $supplierInvoiceProduct,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $firstPO,
            'fileMap1' => $fileMap1,
            'podate' => $podate,
            'del_date' => $del_date,
            'multiPoNumbers' => SupplierMultiPoSupport::displayMultiPoNumbers($supplierInvoice),
            'footerTgst' => $footerTgst,
        ]);
    }





    /**
     * Set unique reference_number when empty.
     * Format: SI-YYYYMMDD-XXXXXX (example: SI-20260331-09DW1Q)
     */
    protected function ensureSupplierInvoiceReferenceNumber($supplierInvoice)
    {
        if (!$supplierInvoice || !empty($supplierInvoice->reference_number)) {
            return;
        }
        do {
            $generatedReference = 'SI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (
            supplierInvoice::where('reference_number', $generatedReference)
                ->where('id', '!=', (int) $supplierInvoice->id)
                ->exists()
        );
        $supplierInvoice->reference_number = $generatedReference;
    }

    /**
     * On approve flows, store reference pool only in:
     * 1) unique_referencenumber
     * 2) unique_referencenumber_product
     */
    protected function syncApprovedRefToBothTables(supplierInvoice $supplierInvoice, string $batchNo, int $productId, int $qty): void
    {
        $batchNo = trim((string) $batchNo);
        $productId = (int) $productId;
        $qty = (int) $qty;
        if ($batchNo === '' || $productId <= 0 || $qty <= 0) {
            return;
        }

        $this->ensureSupplierInvoiceReferenceNumber($supplierInvoice);
        if ($supplierInvoice->isDirty()) {
            $supplierInvoice->save();
        }
        $referenceNumber = trim((string) ($supplierInvoice->reference_number ?? ''));
        if ($referenceNumber === '') {
            return;
        }

        $batch = Batch::where('batch_no', $batchNo)->first();
        if (! $batch) {
            return;
        }

        $resolvedSupplierId = $this->resolveSupplierIdForSupplierInvoice($supplierInvoice);
        if (empty($resolvedSupplierId) && ! empty($batch->supplier_id)) {
            $resolvedSupplierId = (int) $batch->supplier_id;
        }

        // Header row (unique_referencenumber) — supplier_id only (no supplier_name column on table).
        $ref = UniqueReferenceNumber::firstOrCreate(
            [
                'ref_no' => $referenceNumber,
                'batch_id' => (int) $batch->id,
            ],
            [
                'supplier_id' => $resolvedSupplierId,
                'supplier_invoice_id' => (int) $supplierInvoice->id,
                'original_qty' => 0,
                'remqty' => 0,
                'is_old' => 0,
            ]
        );

        $ref->original_qty = (int) ($ref->original_qty ?? 0) + $qty;
        $ref->remqty = (int) ($ref->remqty ?? 0) + $qty;
        if (empty($ref->supplier_invoice_id)) {
            $ref->supplier_invoice_id = (int) $supplierInvoice->id;
        }
        if (! empty($resolvedSupplierId)) {
            $ref->supplier_id = (int) $resolvedSupplierId;
        }
        $ref->save();

        // Detail row (unique_referencenumber_product)
        $refProd = UniqueReferenceNumberProduct::firstOrCreate(
            [
                'unique_referencenumber_id' => (int) $ref->id,
                'product_id' => $productId,
            ],
            [
                'originalqty' => 0,
                'remaining_qty' => 0,
                'remark' => 'Approved from supplier invoice',
            ]
        );
        $refProd->originalqty = (int) ($refProd->originalqty ?? 0) + $qty;
        $refProd->remaining_qty = (int) ($refProd->remaining_qty ?? 0) + $qty;
        $refProd->save();
    }

    /**
     * Keep legacy supplier reference pool tables in sync for swapping UI:
     * - supplier_referacne_number (header: batch_no + refreance_number)
     * - supplier_referance_product (lines: product_id + ref_quanitty)
     *
     * This is intentionally idempotent per (batch_no, reference_number, product_id, remark='Supplier Invoice'):
     * it updates the existing row's ref_quanitty instead of inserting duplicates.
     */
    protected function syncApprovedRefToLegacySupplierReferenceTables(supplierInvoice $supplierInvoice, string $batchNo, int $productId, int $qty): void
    {
        $batchNo = trim((string) $batchNo);
        $productId = (int) $productId;
        $qty = (int) $qty;
        if ($batchNo === '' || $productId <= 0 || $qty <= 0) {
            return;
        }

        if (! Schema::hasTable('supplier_referacne_number') || ! Schema::hasTable('supplier_referance_product')) {
            return;
        }

        $this->ensureSupplierInvoiceReferenceNumber($supplierInvoice);
        if ($supplierInvoice->isDirty()) {
            $supplierInvoice->save();
        }
        $referenceNumber = trim((string) ($supplierInvoice->reference_number ?? ''));
        if ($referenceNumber === '') {
            return;
        }

        $referenceRow = DB::table('supplier_referacne_number')
            ->where('batch_no', $batchNo)
            ->where('refreance_number', $referenceNumber)
            ->first();

        if (! $referenceRow) {
            $referenceId = DB::table('supplier_referacne_number')->insertGetId([
                'refreance_number' => $referenceNumber,
                'batch_no' => $batchNo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $referenceId = (int) $referenceRow->id;
        }

        $remark = 'Supplier Invoice';
        $existingLine = DB::table('supplier_referance_product')
            ->where('supplier_referacne_number_id', $referenceId)
            ->where('product_id', $productId)
            ->where('remark', $remark)
            ->first();

        if ($existingLine) {
            DB::table('supplier_referance_product')
                ->where('id', (int) $existingLine->id)
                ->update([
                    'ref_quanitty' => $qty,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('supplier_referance_product')->insert([
                'supplier_referacne_number_id' => $referenceId,
                'product_id' => $productId,
                'ref_quanitty' => $qty,
                'remark' => $remark,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * From batch numbers like VHCT09257596: trailing digits 09257596 → first four (0925) = MMYY.
     */
    protected function parseMmyyFromBatchNo($batchNo)
    {
        if (empty($batchNo) || !is_string($batchNo)) {
            return null;
        }
        if (!preg_match('/(\d+)$/', trim($batchNo), $m)) {
            return null;
        }
        $digits = $m[1];
        $len    = strlen($digits);
        if ($len >= 8) {
            $mmyy = substr($digits, 0, 4);
        } elseif ($len === 4) {
            $mmyy = $digits;
        } else {
            return null;
        }
        $mm = (int) substr($mmyy, 0, 2);
        $yy = (int) substr($mmyy, 2, 2);
        if ($mm < 1 || $mm > 12) {
            return null;
        }
        $year = 2000 + $yy;

        return ['month' => $mm, 'year' => $year];
    }

    /**
     * supplier_id on row, else from purchase order (Furniture → purchase_orders, else consumables).
     */
    protected function resolveSupplierIdForSupplierInvoice(supplierInvoice $supplierInvoice): ?int
    {
        if (!empty($supplierInvoice->supplier_id)) {
            return (int) $supplierInvoice->supplier_id;
        }
        if (empty($supplierInvoice->purchase_order_id)) {
            return null;
        }
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $po = purchaseOrder::find($supplierInvoice->purchase_order_id);

            return ($po && !empty($po->supplier_id)) ? (int) $po->supplier_id : null;
        }
        $poc = purchaseOrderConsumable::find($supplierInvoice->purchase_order_id);

        return ($poc && !empty($poc->supplier_id)) ? (int) $poc->supplier_id : null;
    }

    protected function normalizeSupplierNameForMatch(?string $name): string
    {
        $s = trim(preg_replace('/\s+/', ' ', (string) $name));

        return mb_strtolower($s);
    }

    /**
     * supplier_invoices.supplier_name vs batch supplier; if invoice name empty, same resolved supplier id is enough.
     */
    protected function supplierInvoiceSupplierNameMatchesBatch(
        supplierInvoice $supplierInvoice,
        supplier $batchSupplier,
        int $resolvedSupplierId
    ): bool {
        $batchLabel = $this->normalizeSupplierNameForMatch($batchSupplier->name ?: $batchSupplier->c_name);
        $invoiceName = trim((string) ($supplierInvoice->supplier_name ?? ''));
        if ($invoiceName !== '') {
            return $this->normalizeSupplierNameForMatch($invoiceName) === $batchLabel;
        }

        return $resolvedSupplierId === (int) $batchSupplier->id;
    }

    /**
     * refquanityupdate: no SI.batch_no filter — approved SIs by created_at month/year (MMYY from batch_no),
     * resolved supplier_id + name vs batch supplier; PO supplies supplier_id when SI.supplier_id null.
     */
    protected function supplierInvoicesForRefQuantityUpdateFromBatch(Batch $batch, supplier $batchSupplier, array $mmyy)
    {
        $batchSupplierId = (int) $batch->supplier_id;
        $year            = (int) $mmyy['year'];
        $month           = (int) $mmyy['month'];

        $candidates = supplierInvoice::where('is_approved', 1)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->get();

        return $candidates->filter(function ($si) use ($batchSupplierId, $batchSupplier) {
            $resolved = $this->resolveSupplierIdForSupplierInvoice($si);
            if ($resolved === null || $resolved !== $batchSupplierId) {
                return false;
            }

            return $this->supplierInvoiceSupplierNameMatchesBatch($si, $batchSupplier, $resolved);
        })->values();
    }

    protected function assignBatchNoToSupplierInvoicesFromBatch(Batch $batch, $supplierInvoices): void
    {
        if ($supplierInvoices->isEmpty() || empty($batch->batch_no)) {
            return;
        }
        foreach ($supplierInvoices as $si) {
            if ($si->batch_no !== $batch->batch_no) {
                $si->batch_no = $batch->batch_no;
                $si->save();
            }
        }
    }
      public function backfillRefQtyForFilteredBatchesExcel(Request $request)
    {
        set_time_limit(0);

        
        $excludedPrefixes = ['GlobalVisionDirectLimited', 'NP', 'FP'];

        $batchProductGroups = DB::select("
            SELECT b.id AS batch_id, b.batch_no, bp.product_id, SUM(bp.quantity) AS active_batch_qty
            FROM batch_product bp
            JOIN batch b ON b.id = bp.batch_id
            WHERE bp.quantity > 0
              AND b.batch_no NOT LIKE ?
              AND b.batch_no NOT LIKE ?
              AND b.batch_no NOT LIKE ?
            GROUP BY b.id, b.batch_no, bp.product_id
        ", [$excludedPrefixes[0] . '%', $excludedPrefixes[1] . '%', $excludedPrefixes[2] . '%']);

        // Backfill into unique reference tables (UniqueReferenceNumber + UniqueReferenceNumberProduct).
        foreach ($batchProductGroups as $grp) {
            $batch = Batch::find((int) $grp->batch_id);
            if (! $batch || empty($batch->supplier_id) || empty($batch->batch_no)) {
                continue;
            }

            $mmyy = $this->parseMmyyFromBatchNo($batch->batch_no);
            if (! $mmyy) {
                continue;
            }

            $batchSupplier = supplier::find($batch->supplier_id);
            if (! $batchSupplier) {
                continue;
            }

            $supplierInvoices = $this->supplierInvoicesForRefQuantityUpdateFromBatch($batch, $batchSupplier, $mmyy);
            if ($supplierInvoices->isEmpty()) {
                continue;
            }

            $this->assignBatchNoToSupplierInvoicesFromBatch($batch, $supplierInvoices);

            $productId = (int) $grp->product_id;
            $activeBatchQty = (int) $grp->active_batch_qty;

            foreach ($supplierInvoices as $supplierInvoice) {
                $this->ensureSupplierInvoiceReferenceNumber($supplierInvoice);
                if ($supplierInvoice->isDirty()) {
                    $supplierInvoice->save();
                }

                $referenceNumber = trim((string) ($supplierInvoice->reference_number ?? ''));
                if ($referenceNumber === '') {
                    continue;
                }

                $totalInvoiceProductQty = (int) supplierInvoiceProduct::where('supplier_invoice_id', $supplierInvoice->id)
                    ->where('product_id', $productId)
                    ->sum('quantity');

                // remaining_qty must come from legacy ref pool (ref_quanitty) for same batch+reference+product.
                $legacyRefQty = (int) DB::table('supplier_referance_product as srp')
                    ->join('supplier_referacne_number as srn', 'srn.id', '=', 'srp.supplier_referacne_number_id')
                    ->where('srn.batch_no', $batch->batch_no)
                    ->where('srn.refreance_number', $referenceNumber)
                    ->where('srp.product_id', $productId)
                    ->sum(DB::raw('COALESCE(srp.ref_quanitty, 0)'));

                // Skip if nothing to store for this invoice+product.
                if ($totalInvoiceProductQty <= 0 && $legacyRefQty <= 0) {
                    continue;
                }

                $resolvedSupplierId = $this->resolveSupplierIdForSupplierInvoice($supplierInvoice);
                if (empty($resolvedSupplierId) && ! empty($batch->supplier_id)) {
                    $resolvedSupplierId = (int) $batch->supplier_id;
                }

                $ref = UniqueReferenceNumber::firstOrCreate(
                    [
                        'ref_no' => $referenceNumber,
                        'batch_id' => (int) $batch->id,
                    ],
                    [
                        'supplier_id' => $resolvedSupplierId,
                        'supplier_invoice_id' => (int) $supplierInvoice->id,
                        'original_qty' => 0,
                        'remqty' => 0,
                        'is_old' => 0,
                    ]
                );
                if (empty($ref->supplier_invoice_id)) {
                    $ref->supplier_invoice_id = (int) $supplierInvoice->id;
                }
                if (! empty($resolvedSupplierId)) {
                    $ref->supplier_id = (int) $resolvedSupplierId;
                }
                $ref->save();

                UniqueReferenceNumberProduct::updateOrCreate(
                    [
                        'unique_referencenumber_id' => (int) $ref->id,
                        'product_id' => $productId,
                    ],
                    [
                        'originalqty' => (int) $totalInvoiceProductQty,
                        'remaining_qty' => (int) $legacyRefQty,
                        'remark' => 'Backfilled from supplier invoice + legacy ref pool',
                    ]
                );

                // Keep header totals aligned with product rows (idempotent).
                $totals = UniqueReferenceNumberProduct::where('unique_referencenumber_id', (int) $ref->id)
                    ->selectRaw('COALESCE(SUM(originalqty),0) as o, COALESCE(SUM(remaining_qty),0) as r')
                    ->first();
                $ref->original_qty = (int) ($totals->o ?? 0);
                $ref->remqty = (int) ($totals->r ?? 0);
                $ref->save();
            }
        }

        // Report source is UniqueReferenceNumberProduct (backfilled).
        $batchProductGroups = DB::select("
            SELECT b.batch_no, bp.product_id, SUM(bp.quantity) AS active_batch_qty
            FROM batch_product bp
            JOIN batch b ON b.id = bp.batch_id
            WHERE bp.quantity > 0
              AND b.batch_no NOT LIKE ?
              AND b.batch_no NOT LIKE ?
              AND b.batch_no NOT LIKE ?
            GROUP BY b.batch_no, bp.product_id
        ", [$excludedPrefixes[0] . '%', $excludedPrefixes[1] . '%', $excludedPrefixes[2] . '%']);

        $saveToStorage = $request->boolean('save_to_storage');
        $ts = now()->format('Ymd_His');

        if (empty($batchProductGroups)) {
            $fileName = 'ref_qty_backfill_empty_' . $ts . '.xlsx';
            $export = new RefQtyBackfillReportExport([], []);
            if ($saveToStorage) {
                Excel::store($export, 'reports/' . $fileName, 'local');
                return response()->json(['saved_to_storage' => true, 'file' => storage_path('app/reports/' . $fileName)]);
            }
            return Excel::download($export, $fileName);
        }

        $batchNos = [];
        $productIds = [];
        $batchProductIndex = [];
        foreach ($batchProductGroups as $row) {
            $bn = (string) $row->batch_no;
            $pid = (int) $row->product_id;
            $qty = (int) $row->active_batch_qty;
            $batchNos[$bn] = true;
            $productIds[$pid] = true;
            $batchProductIndex[$bn . '|' . $pid] = [
                'batch_no' => $bn,
                'product_id' => $pid,
                'active_batch_qty' => $qty,
                'total_ref_pool' => 0,
                'product_code' => '',
                'product_name' => '',
            ];
        }
        $batchNos = array_values(array_keys($batchNos));
        $productIds = array_values(array_keys($productIds));

        $products = product::whereIn('id', $productIds)->get(['id', 'code', 'name']);
        $productsById = [];
        foreach ($products as $p) $productsById[(int)$p->id] = $p;
        foreach ($batchProductIndex as $k => $v) {
            $m = $productsById[(int)$v['product_id']] ?? null;
            if ($m) {
                $batchProductIndex[$k]['product_code'] = (string) $m->code;
                $batchProductIndex[$k]['product_name'] = (string) $m->name;
            }
        }

        $refTotals = DB::select("
            SELECT b.batch_no,
                   urp.product_id,
                   COALESCE(si.id, 0) AS supplier_invoice_id,
                   COALESCE(si.supplier_invoice_number, ur.ref_no) AS supplier_invoice_number,
                   SUM(COALESCE(urp.remaining_qty, 0)) AS ref_qty
            FROM unique_referencenumber_product urp
            JOIN unique_referencenumber ur ON ur.id = urp.unique_referencenumber_id
            JOIN batch b ON b.id = ur.batch_id
            LEFT JOIN supplier_invoices si ON si.reference_number = ur.ref_no
                                          AND si.is_approved = 1
            WHERE b.batch_no IN (" . implode(',', array_fill(0, count($batchNos), '?')) . ")
              AND COALESCE(urp.remaining_qty, 0) > 0
            GROUP BY b.batch_no, urp.product_id, si.id, si.supplier_invoice_number, ur.ref_no
        ", $batchNos);

        $contribMap = [];
        $apply = function ($bn, $pid, $sid, $invNo, $sipQty, $swapQty) use (&$contribMap, &$batchProductIndex) {
            $keyBP = $bn . '|' . $pid;
            if (!isset($batchProductIndex[$keyBP])) return;
            $key = $bn . '|' . $pid . '|' . $sid;
            if (!isset($contribMap[$key])) {
                $contribMap[$key] = [
                    'batch_no' => $bn,
                    'product_id' => $pid,
                    'supplier_invoice_id' => $sid,
                    'supplier_invoice_number' => $invNo,
                    'sip_ref_qty' => 0,
                    'swap_ref_qty' => 0,
                    'total_ref_qty' => 0,
                ];
            }
            $contribMap[$key]['sip_ref_qty'] += (int)$sipQty;
            $contribMap[$key]['swap_ref_qty'] += (int)$swapQty;
            $contribMap[$key]['total_ref_qty'] = $contribMap[$key]['sip_ref_qty'] + $contribMap[$key]['swap_ref_qty'];
            $batchProductIndex[$keyBP]['total_ref_pool'] += (int)$sipQty + (int)$swapQty;
        };

        foreach ($refTotals as $r) {
            $apply(
                (string) $r->batch_no,
                (int) $r->product_id,
                (int) $r->supplier_invoice_id,
                (string) ($r->supplier_invoice_number ?? ''),
                (int) $r->ref_qty,
                0
            );
        }

        $assignedRows = [];
        foreach ($contribMap as $row) {
            if ((int)$row['total_ref_qty'] <= 0) continue;
            $bp = $batchProductIndex[$row['batch_no'] . '|' . (int)$row['product_id']] ?? null;
            if (!$bp) continue;
            $assignedRows[] = [
                'batch_no' => $row['batch_no'],
                'product_id' => (int)$row['product_id'],
                'product_code' => $bp['product_code'],
                'product_name' => $bp['product_name'],
                'supplier_invoice_id' => (int)$row['supplier_invoice_id'],
                'supplier_invoice_number' => (string)$row['supplier_invoice_number'],
                'sip_ref_qty' => (int)$row['sip_ref_qty'],
                'swap_ref_qty' => (int)$row['swap_ref_qty'],
                'total_ref_qty' => (int)$row['total_ref_qty'],
            ];
        }

        $unassignedRows = [];
        foreach ($batchProductIndex as $bp) {
            if ((int)$bp['total_ref_pool'] < (int)$bp['active_batch_qty']) {
                $unassignedRows[] = [
                    'batch_no' => $bp['batch_no'],
                    'product_id' => (int)$bp['product_id'],
                    'product_code' => (string)$bp['product_code'],
                    'product_name' => (string)$bp['product_name'],
                    'active_batch_qty' => (int)$bp['active_batch_qty'],
                    'total_ref_pool' => (int)$bp['total_ref_pool'],
                ];
            }
        }

        $fileName = 'ref_qty_backfill_assigned_unassigned_' . $ts . '.xlsx';
        $export = new RefQtyBackfillReportExport($assignedRows, $unassignedRows);
        if ($saveToStorage) {
            Excel::store($export, 'reports/' . $fileName, 'local');
            return response()->json(['saved_to_storage' => true, 'file' => storage_path('app/reports/' . $fileName)]);
        }
        return Excel::download($export, $fileName);
    }

}
