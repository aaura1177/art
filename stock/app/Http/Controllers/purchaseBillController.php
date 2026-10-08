<?php

namespace App\Http\Controllers;

use App\ServiceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\purchaseOrder;
use App\supplier;
use App\product;
use App\purchaseOrderConsumable;
use App\poTable;
use App\SubServicePbTable;
use App\pbTable;
use App\Service;

use App\supplierInvoiceProduct;
use App\supplierInvoice;
use App\Batch;
use App\BatchProduct;
use App\serviceTable;
use App\ServicePbTable;
use App\Exports\InwardSupplyExport;
use App\stockLog;
use App\productLocations;
use App\purchaseBill;
use App\PurchaseBillCarton;
use App\purchaseBillConsumable;
use App\PbTableCorton;
use App\pbTableConsumable;
use App\servicePurchaseBill;
use App\setting;
use App\Exports\ServiceInwardSupplyExport;
use App\Exports\ServiceBillsDueTrackingExport;
use App\Exports\purchaseBillTallyExport;
use App\Services\InwardSupply\ServiceAdminInwardService;
use App\Services\ServiceInvoiceQuantityService;
use App\Support\SupplierMultiPoSupport;
use App\Exports\BillsTracking;
use App\samplePurchaseBill;
use App\samplePurchaseOrder;
use App\sampleSupplierInvoice;
use App\sampleSupplierInvoiceProduct;
use Maatwebsite\Excel\Facades\Excel;
use App\posTable;
use App\spbTable;
use App\sample;
use App\Exports\InwardSupplySampleExport;
use Carbon\Carbon;
use App\UniqueReferenceNumber;
use App\UniqueReferenceNumberProduct;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
class purchaseBillController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function exportcsv(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new InwardSupplyExport($fsd, $fed))->download('inwardSupply.xlsx');
    }

    public function billstracking(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new BillsTracking($fsd, $fed))->download('billstracking.xlsx');
    }

    public function create()
    {
        $purchaseOrder = purchaseOrder::where('status', '0')->get();
        $supplier = supplier::get();
        return view('purchaseBill/create', ['purchaseOrder' => $purchaseOrder, 'supplier' => $supplier]);
    }

  
      public function store(Request $request)
    {
        // return $request->all();
        $purchaseOrder = purchaseOrder::find($request['purchaseOrder_id']);
        if (!$purchaseOrder) {
            return back()->with('error', 'Purchase Order not found');
        }
        $eligibilityDate = $this->getInwardSupplyEligibilityDate($purchaseOrder);
        if ($eligibilityDate && Carbon::today()->lt($eligibilityDate)) {
            return back()->with(
                'danger',
                'Inward supply can be generated only from 7 days before delivery date (eligible on ' . $eligibilityDate->format('d-m-Y') . ').'
            )->withInput();
        }

$exists = purchaseBill::where('getinserailno', $request['getinserailno'])->exists();

        if ($exists) {
            return redirect('/purchaseBill/create')->with('danger', 'Get In Serial No already exists.');
        }

        $statusremqty = 0;
        $carbonDate   = Carbon::parse($request['supp_inv_date']);

        $Batchmonth  = $carbonDate->format('m') . $carbonDate->format('y');
        $Batchmonth1 = $carbonDate->format('m') . '/' . $carbonDate->format('y');

        $supplier = supplier::find($purchaseOrder->supplier_id);
        $prefix   = $supplier->short_name . $Batchmonth;

        $suppInvNo = strtoupper($request->supp_inv_no);
        $ewayBill  = strtoupper($request->ewaybill);

        $existingBill = supplierInvoice::where('supplier_id', $supplier->id)
    ->where(function ($q) use ($suppInvNo, $ewayBill) {
        $q->where('supplier_invoice_number', $suppInvNo);

        if (!empty($ewayBill)) {
            $q->orWhere('eway_bill_no', $ewayBill);
        }
    })
    ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->first();

if ($existingBill) {
    return back()->with('error', 'Supplier Invoice No. or E-Way Bill No. already exists!')
                 ->withInput();
}

        $totalquantitybatch = 0;

        $batch = Batch::where('date', $Batchmonth1)
            ->whereRaw("LEFT(batch_no, LENGTH(batch_no) - 4) = ?", [$prefix])
            ->first();

        if (!$batch) {
            $batch = Batch::create([
                'batch_no' => $supplier->short_name . $Batchmonth . rand(1000, 9999),
                'quantity' => 0,
'supplier_id'=>$supplier->id,
                'date'     => $Batchmonth1,
            ]);
        }

        $q = purchaseBill::create([
            'purchaseOrder_id' => $request['purchaseOrder_id'],
            'ewaybill'    => strtoupper($request['ewaybill']),
            'supp_inv_no' => strtoupper($request['supp_inv_no']),
            'supp_inv_date'    => $request['supp_inv_date'],
            'supplier_id'    => $supplier->id,
            'quantity'         => $request['pbQty'],
            'subtotal'         => $request['pbSubTotal'],
            'totaldiscount'         => $request['totaldiscount'],
            'gst'              => $request['pbGST'],
            'freight'          => $request['freight'],
'getinserailno' => $request['getinserailno'],
            'total'            => $request['pbTotal']
        ]);

        // Create Supplier Invoice master from Purchase Bill flow.
        do {
            $referenceNumber = 'SI-' . now()->format('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (supplierInvoice::where('reference_number', $referenceNumber)->exists());

        $supplierInvoice = supplierInvoice::create([
            'purchase_order_id' => $request['purchaseOrder_id'],
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => strtoupper($request['supp_inv_no']),
            'eway_bill_no' => strtoupper($request['ewaybill']),
            'tquantity' => (int) ($request['pbQty'] ?? 0),
            'tgst' => (float) ($request['pbGST'] ?? 0),
            'subTotal' => (float) ($request['pbSubTotal'] ?? 0),
            'tamount' => (float) ($request['pbTotal'] ?? 0),
            'totaldiscount' => (float) ($request['totaldiscount'] ?? 0),
            'invoice_date' => $request['supp_inv_date'],
            'purchase_order_type' => 'Furniture',
            'user_id' => auth()->id(),
            'status' => 0,
            'reference_number' => $referenceNumber,
            'batch_no' => $batch ? $batch->batch_no : null,
        ]);
        $supplierInvoice->is_approved = 1;
        $supplierInvoice->save();

        $q->supplier_invoice_id = $supplierInvoice->id;
        $q->save();

        // Maintain reference pool in new tables (unique_referencenumber / unique_referencenumber_product)
        $totalReceivedQty = 0;
        foreach (($request['pb'] ?? []) as $pbLine) {
            $totalReceivedQty += (int) ($pbLine['receiveqty'] ?? 0);
        }

        $uniqueReference = UniqueReferenceNumber::create([
            'ref_no' => $referenceNumber,
            'batch_id' => $batch ? $batch->id : null,
            'supplier_id' => $supplier->id,
            'supplier_invoice_id' => $supplierInvoice->id,
            'original_qty' => $totalReceivedQty,
            'remqty' => $totalReceivedQty,
            'is_old' => 0,
        ]);

        foreach ($request['pb'] as $pb) {
            $receiveQty = (int) ($pb['receiveqty'] ?? 0);
            $p = pbTable::create([
                'product_id'       => $pb['product'],
                'ean'              => $pb['EAN'],
                'purchaseOrder_id' => $q->purchaseOrder_id,
                'purchaseBill_id'  => $q->id,
                'orderqty'         => $pb['remqty'],
                'receiveqty'       => $receiveQty,
                'remainingqty'     => $pb['remqty'] - $receiveQty,
                'rate'             => $pb['rate'],
                'amount'           => $pb['amount'],
                'discount_type'           => $pb['discount_type'],
                'discount'           => $pb['producttotalDiscount'],
                'location'         => $pb['location']
            ]);

            // Supplier invoice line (supplier_invoice_products)
            if ($receiveQty > 0) {
                supplierInvoiceProduct::create([
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'purchase_order_id' => $request['purchaseOrder_id'],
                    'product_id' => (int) $pb['product'],
                    'quantity' => $receiveQty,
                    'amount' => (float) ($pb['amount'] ?? 0),
                    'discount_type' => $pb['discount_type'] ?? null,
                    'discount' => (float) ($pb['producttotalDiscount'] ?? 0),
                    'gst' => 0,
                    'total' => (float) (($pb['amount'] ?? 0) - ($pb['producttotalDiscount'] ?? 0)),
                    'ref_quantity' => $receiveQty,
                ]);

                UniqueReferenceNumberProduct::create([
                    'unique_referencenumber_id' => $uniqueReference->id,
                    'product_id' => (int) $pb['product'],
                    'originalqty' => $receiveQty,
                    'remaining_qty' => $receiveQty,
                    'remark' => 'Created from purchase bill store',
                ]);
            }

            $poProduct = poTable::where('poid', $request['purchaseOrder_id'])
                ->where('product_id', $pb['product'])
                ->first();

            if ($poProduct) {
                $poProduct->remqty = $p->remainingqty;
                $poProduct->remaining_discount -= $pb['producttotalDiscount'];
                $poProduct->save();
            }

            if (trim($pb['location']) != "") {
                $plocation = productLocations::where('product_id', $pb['product'])
                    ->where('location', $pb['location'])
                    ->first();

                if ($plocation) {
                    $plocation->quantity += $pb['receiveqty'];
                    $plocation->save();
                } else {
                    productLocations::create([
                        'product_id' => $pb['product'],
                        'location'   => $pb['location'],
                        'quantity'   => $pb['receiveqty'],
                    ]);
                }
            }

            $product = product::find($pb['product']);

            if ($batch) {
                $batchproduct = BatchProduct::where('product_id', $pb['product'])
                    ->where('batch_id', $batch->id)
                    ->first();

                if ($batchproduct) {
                    $batchproduct->quantity += $pb['receiveqty'];
                    $batchproduct->save();
                } else {
                    $batchproduct = BatchProduct::create([
                        'batch_id'   => $batch->id,
                        'product_id' => $pb['product'],
                        'quantity'   => $pb['receiveqty'],
                        'date'       => now()->toDateString(),
                        'supp_in_no' => strtoupper($request['supp_inv_no']),
                    ]);
                }

                // stock update
                $open_balance      = $product->quantity;
                $product->quantity = $product->quantity + $pb['receiveqty'];
                $product->location = $pb['location'];
                $product->save();

                // stock log entry (same reference pattern as supplierInvoice store_approve)
                $formattedReferenceNumber = $supplierInvoice->supplier_invoice_number . '(' . (int) $pb['receiveqty'] . '/0)';
                stockLog::create([
                    'product_id'      => $pb['product'],
                    'voucher_no'      => strtoupper($request['supp_inv_no']) . ' by ' . $supplier->c_name,
                    'ref_no'          => $purchaseOrder->ref_supplier,
                    'quantity'        => $pb['receiveqty'],
                    'opening_balance' => $open_balance,
                    'remaining_stock' => $product->quantity,
                    'type'            => 1,
                    'entity_id'       => $q->id,
                    'supplier_name'   => $supplier->c_name,
                    'batch_no'        => $batch->batch_no,
                    'batch_balance'   => $batchproduct->quantity,
                    'reference_number' => $formattedReferenceNumber,
                    'reference_quantity' => (int) $pb['receiveqty'],
                ]);

                $totalquantitybatch += $pb['receiveqty'];
            }
        }

        // update total batch quantity
        $batch->quantity += $totalquantitybatch;
        $batch->save();

        // check PO remaining qty
        $poTableCheck = poTable::where('poid', $request['purchaseOrder_id'])->get();
        foreach ($poTableCheck as $poTableChecks) {
            $statusremqty += $poTableChecks->remqty;
        }

        $purchaseOrder->status = $statusremqty == 0 ? 1 : 0;
        $purchaseOrder->remqty = $statusremqty;
        $purchaseOrder->save();

        return redirect('/purchaseBill')->with('success', 'Stock was added successfully.');
    }


    public function index(Request $request)
    {
        $search = $request->input('search');
        $date = $request->input('date'); // legacy single date filter
        $from = $request->input('from_date');
        $to = $request->input('to_date');

        $purchaseBill = purchaseBill::with(['purchaseOrder.supplier'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supp_inv_no', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                            $q2->where('ref_supplier', 'like', "%{$search}%")
                                ->orWhere('pono', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseOrder.supplier', function ($q3) use ($search) {
                            $q3->where('c_name', 'like', "%{$search}%");
                        });
                });
            })
            // Date filters
            ->when($from || $to, function ($query) use ($from, $to) {
                // both from and to provided
                if ($from && $to) {
                    try {
                        $fromDate = Carbon::parse($from)->startOfDay();
                        $toDate = Carbon::parse($to)->endOfDay();
                        $query->whereBetween('supp_inv_date', [$fromDate, $toDate]);
                    } catch (\Exception $e) {
                        // invalid date(s) — ignore date filtering
                    }
                } elseif ($from) {
                    try {
                        $fromDate = Carbon::parse($from)->startOfDay();
                        $query->where('supp_inv_date', '>=', $fromDate);
                    } catch (\Exception $e) {
                        //
                    }
                } elseif ($to) {
                    try {
                        $toDate = Carbon::parse($to)->endOfDay();
                        $query->where('supp_inv_date', '<=', $toDate);
                    } catch (\Exception $e) {
                        //
                    }
                }
            }, function ($query) use ($date) {
                // fallback when no from/to but legacy single date param present
                if ($date) {
                    $query->where('supp_inv_date', 'like', "%{$date}%");
                }
            })
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->except('page')); // preserve filters on pagination links

        return view('purchaseBill/index', ['purchaseBills' => $purchaseBill]);
    }
    public function purchase_bills(Request $request)
    {
        $da = '2021-02-28';
        $search = $request->input('search');

        $purchaseBill = purchaseBill::with(['purchaseOrder.supplier'])
            ->where('supp_inv_date', '>', $da)
            ->whereNull('swap_id')
            ->whereNull('mulitple_po_purchaseBill')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supp_inv_date', 'like', "%{$search}%")
                        ->orWhere('supp_inv_no', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                            $q2->where('ref_supplier', 'like', "%{$search}%")
                                ->orWhere('pono', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseOrder.supplier', function ($q3) use ($search) {
                            $q3->where('c_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        //$purchaseBill=purchaseBill::where('supp_inv_date','>',$da)->whereNull('swap_id')->get();
        return view('purchaseBill/purchase_bills', ['purchaseBills' => $purchaseBill]);
    }
    public function data()
    {
        $purchaseOrder = purchaseOrder::all();
        $supplier = supplier::all();
        $product = product::all();
        return response()->json(['purchaseOrder' => $purchaseOrder, 'supplier' => $supplier, 'product' => $product]);
    }

    public function viewdata()
    {
        $product = product::all();
        return response()->json(['product' => $product]);
    }

    public function poTableData($id)
    {
        $poTable = poTable::where('poid', $id)->get();
        return response()->json(['poTable' => $poTable]);
    }

    public function view($id)
    {
        $purchaseBill = purchaseBill::where('purchaseOrder_id', $id)->first();
        $poStatus = $purchaseBill->purchaseOrder->status;
        $pbTable = pbTable::where('purchaseOrder_id', $id)->get();
        if ($purchaseBill) {
            if ($poStatus != 2) {
                return view('purchaseBill/view', ['purchaseBill' => $purchaseBill], ['pbTable' => $pbTable]);
            } else {
                return redirect('/purchaseBill')->with('danger', 'Purchase Order is marked complete. Can not edit the Purchase Bill.');
            }
        } else {
            return redirect('/purchaseBill')->with('danger', 'Purchase Bill was not found.');
        }
    }

    public function modal($id)
    {
        $purchaseBill = purchaseBill::where('id', $id)->first();
        $purchaseOrderid = $purchaseBill->purchaseOrder_id;
        $purchaseOrder = purchaseOrder::where('id', $purchaseOrderid)->first();

        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        $pbTable = pbTable::where('purchasebill_id', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseBill/modal', ['purchaseBill' => $purchaseBill, 'print' => $print, 'companyDetails' => $companyDetails, 'pbTable' => $pbTable, 'purchaseOrder' => $purchaseOrder, 'fileMap' => $fileMap]);
    }

    public function isTallyExport()
    {
        $pbs = purchaseBill::where('is_downloaded', 0)->where('is_checked', 1)->get();
        foreach ($pbs as $pb) {
            $pb_ids[] = $pb->id;
        }
        //dd($pb_ids);
        return (new purchaseBillTallyExport($pb_ids))->download('pbTallyExport.xlsx');
    }

    public function verify($id)
    {

        purchaseBill::where('id', $id)->update(['is_checked' => 1]);
        echo 1;
        die;
        //return redirect('/purchaseBill')->with('success', 'Supply verified successfully.');
    }

    public function update_tally_status(Request $request, $id)
    {
        // Check if the ID is provided
        if (empty($id)) {
            return redirect('/purchaseBill/condition')->with('danger', 'Invalid purchaseBill ID.');
        }

        // Check if the record exists in the database
        $purchaseBill = purchaseBill::find($id);
        if (!$purchaseBill) {
            return redirect('/purchaseBill/condition')->with('danger', 'purchaseBill not found.');
        }

        // Update the tally_status
        $purchaseBill->status = $request->tallystatus;
        $purchaseBill->save();

        return redirect('/purchaseBill/condition')->with('success', 'Tally Status updated successfully.');
    }

    public function update_tally_status_multi(Request $request, $id)
    {
        // Check if the ID is provided
        if (empty($id)) {
            return redirect('/purchaseBill/condition/Multi')->with('danger', 'Invalid purchaseBill ID.');
        }

        // Check if the record exists in the database
        $purchaseBill = purchaseBill::find($id);
        if (!$purchaseBill) {
            return redirect('/purchaseBill/condition/Multi')->with('danger', 'purchaseBill not found.');
        }

        // Update the tally_status
        $purchaseBill->status = $request->tallystatus;
        $purchaseBill->save();

        return redirect('/purchaseBill/condition/Multi')->with('success', 'Tally Status updated successfully.');
    }

    public function revert_verify($id)
    {

        purchaseBill::where('id', $id)->update(['is_checked' => 0]);
        echo 1;
        die;
        //return redirect('/purchaseBill')->with('success', 'Supply verified successfully.');
    }

    public function verified()
    {
        //$purchaseBill=purchaseBill::get();
        $purchaseBill = purchaseBill::where('is_checked', '!=', '0')->where('is_downloaded', 0)->orderBy('id', 'desc')->get();
        $purchaseBillCount = $purchaseBill->count();

        return view('purchaseBill/verified', ['purchaseBill' => $purchaseBill]);
    }
    public function downloaded()
    {
        //$purchaseBill=purchaseBill::get();
        $purchaseBill = purchaseBill::where('is_checked', '!=', '0')->where('is_downloaded', 1)->get();
        $purchaseBillCount = $purchaseBill->count();

        return view('purchaseBill/downloaded', ['purchaseBill' => $purchaseBill]);
    }
    public function updatePaymentStatus($id, $stat)
    {
        purchaseBill::where('id', $id)->update(['payment_status' => $stat]);
        if ($stat == 1) {
            echo "UnShipped + UnPaid";
        }
        if ($stat == 2) {
            echo "Payment When Due";
        }
        if ($stat == 3) {
            echo "UnShipped + Paid";
        }
        if ($stat == 4) {
            echo "Shipped + UnPaid";
        }
        if ($stat == 5) {
            echo "Shipped + Paid";
        }
        die;
    }

    public function samples()
    {
        $purchaseBill = samplePurchaseBill::get();

        //  echo "<pre>";
        //  print_r($purchaseBill);die;
        return view('purchaseBill/samples', ['purchaseBill' => $purchaseBill]);
    }

    public function createSample()
    {
        $samplePurchaseOrder = samplePurchaseOrder::where('status', '0')->orderBy('created_at', 'desc')->get();
        $supplier = supplier::get();
        // echo "<pre>";
        // print_r($supplier);die;
        return view('purchaseBill/createSample', ['samplePurchaseOrder' => $samplePurchaseOrder, 'supplier' => $supplier]);
    }
    public function posTableData($id)
    {
        $poTable = posTable::where('poid', $id)->get();

        return response()->json(['poTable' => $poTable]);
    }

    public function dataSample()
    {
        $samplePurchaseOrder = samplePurchaseOrder::all();
        $supplier = supplier::all();
        $product = product::all();
        $sample = sample::all();
        return response()->json(['purchaseOrder' => $samplePurchaseOrder, 'supplier' => $supplier, 'product' => $product, 'sample' => $sample]);
    }


  public function storeSample(Request $request)
    {
        // return $request->all();
        $purchaseOrder = samplePurchaseOrder::where('id', $request['purchaseOrder_id'])->first();
        if (!$purchaseOrder) {
            return back()->with('error', 'Sample Purchase Order not found');
        }
        $eligibilityDate = $this->getInwardSupplyEligibilityDate($purchaseOrder);
        if ($eligibilityDate && Carbon::today()->lt($eligibilityDate)) {
            return back()->with(
                'danger',
                'Inward supply can be generated only from 7 days before delivery date (eligible on ' . $eligibilityDate->format('d-m-Y') . ').'
            )->withInput();
        }
        $statusremqty = 0;

        $carbonDate   = Carbon::parse($request['supp_inv_date']);

        $Batchmonth  = $carbonDate->format('m') . $carbonDate->format('y');
        $Batchmonth1 = $carbonDate->format('m') . '/' . $carbonDate->format('y');

        $supplier = supplier::find($purchaseOrder->supplier_id);
        $prefix   = $supplier->short_name . $Batchmonth;

        $suppInvNoUpper = strtoupper($request['supp_inv_no']);
        $ewayBillUpper = strtoupper($request['ewaybill'] ?? '');

        $existingSampleInvoice = sampleSupplierInvoice::where('supplier_id', $supplier->id)
            ->where(function ($q) use ($suppInvNoUpper, $ewayBillUpper) {
                $q->where('supplier_invoice_number', $suppInvNoUpper);
                if (!empty($ewayBillUpper)) {
                    $q->orWhere('eway_bill_no', $ewayBillUpper);
                }
            })
            ->where(function ($q) {
                $q->where('status', '!=', 2)
                    ->orWhereNull('status');
            })
            ->first();

        if ($existingSampleInvoice) {
            return back()->with('error', 'Sample Supplier Invoice No. or E-Way Bill No. already exists!')
                ->withInput();
        }

        // echo "<pre>";
        // print_r($purchaseOrder);die;
        $q = samplePurchaseBill::create([
            'purchaseOrder_id' => $request['purchaseOrder_id'],
            'ewaybill' => $ewayBillUpper,
            'supp_inv_no' => $suppInvNoUpper,
            'supp_inv_date' => $request['supp_inv_date'],
            'quantity' => $request['pbQty'],
            'subtotal' => $request['pbSubTotal'],
            'gst' => $request['pbGST'],
            'freight' => $request['freight'],
            'total' => $request['pbTotal']
        ]);

        $totalquantitybatch = 0;
        $batch = Batch::where('date', $Batchmonth1)
            ->whereRaw("LEFT(batch_no, LENGTH(batch_no) - 4) = ?", [$prefix])
            ->first();

        if (!$batch) {
            $batch = Batch::create([
                'batch_no' => $supplier->short_name . $Batchmonth . rand(1000, 9999),
                'quantity' => 0,
                'supplier_id' => $purchaseOrder->supplier_id,
                'date'     => $Batchmonth1,
            ]);
        }

        // Create sample supplier invoice (separate from furniture supplier_invoices).
        // Unique-reference / packing flow remains unchanged below.
        do {
            $sampleSiRef = 'SSI-' . now()->format('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (sampleSupplierInvoice::where('reference_number', $sampleSiRef)->exists());

        $sampleSupplierInvoice = sampleSupplierInvoice::create([
            'purchase_order_id' => (int) $request['purchaseOrder_id'],
            'supplier_id' => $supplier->id,
            'sample_purchase_bill_id' => $q->id,
            'supplier_invoice_number' => $suppInvNoUpper,
            'eway_bill_no' => $ewayBillUpper,
            'invoice_date' => $request['supp_inv_date'],
            'tquantity' => (float) ($request['pbQty'] ?? 0),
            'tgst' => (float) ($request['pbGST'] ?? 0),
            'subTotal' => (float) ($request['pbSubTotal'] ?? 0),
            'tamount' => (float) ($request['pbTotal'] ?? 0),
            'freight' => (float) ($request['freight'] ?? 0),
            'status' => 0,
            'is_approved' => 1,
            'user_id' => auth()->id(),
            'batch_no' => $batch ? $batch->batch_no : null,
            'reference_number' => $sampleSiRef,
        ]);

        $q->supplier_invoice_id = $sampleSupplierInvoice->id;
        $q->save();

        $totalFurnitureReceiveQty = 0;
        foreach (($request['pb'] ?? []) as $pbLine) {
            $recv = (int) ($pbLine['receiveqty'] ?? 0);
            if ($recv <= 0) {
                continue;
            }
            $sampleRow = sample::where('id', $pbLine['product'])->first();
            if (! $sampleRow || $sampleRow->prod_code === null || $sampleRow->prod_code === '') {
                continue;
            }
            $linkedProduct = product::where('id', $sampleRow->product_id)->first();
            if ($linkedProduct) {
                $totalFurnitureReceiveQty += $recv;
            }
        }

        $sampleUniqueReference = null;
        if ($totalFurnitureReceiveQty > 0 && $batch) {
            do {
                $sampleRefNo = 'SMP-' . now()->format('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
            } while (UniqueReferenceNumber::where('ref_no', $sampleRefNo)->exists());

            $sampleUniqueReference = UniqueReferenceNumber::create([
                'ref_no' => $sampleRefNo,
                'batch_id' => $batch->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'supplier_invoice_id' => null,
                'original_qty' => $totalFurnitureReceiveQty,
                'remqty' => $totalFurnitureReceiveQty,
                'is_old' => 1,
            ]);
        }

        foreach ($request['pb'] as $pb) {
                        $product = sample::where('id', $pb['product'])->first();

            $p = spbTable::create([
                'sample_id' => $pb['product'],
                'product_id' => $product->product_id,
                'EAN' => $pb['EAN'],
                'purchaseOrder_id' => $q->purchaseOrder_id,
                'sample_purchasebill_id' => $q->id,
                'orderqty' => $pb['remqty'],
                'receiveqty' => $pb['receiveqty'],
                'remainingqty' => $pb['remqty'] - $pb['receiveqty'],
                'rate' => $pb['rate'],
                'amount' => $pb['amount'],
                'location' => $pb['location']
            ]);

            $receiveQty = (int) ($pb['receiveqty'] ?? 0);
            if ($receiveQty > 0) {
                sampleSupplierInvoiceProduct::create([
                    'sample_supplier_invoice_id' => $sampleSupplierInvoice->id,
                    'purchase_order_id' => (int) $request['purchaseOrder_id'],
                    'sample_id' => (int) $pb['product'],
                    'product_id' => $product->product_id ?? null,
                    'quantity' => $receiveQty,
                    'amount' => (float) ($pb['amount'] ?? 0),
                    'gst' => 0,
                    'total' => (float) ($pb['amount'] ?? 0),
                ]);
            }

            $poProduct = posTable::where('poid', $request['purchaseOrder_id'])->where('sample_id', $pb['product'])->first();

            $poProduct->remqty = $p->remainingqty;
            $poProduct->save();
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
            //$product = product::where('id', $pb['product'])->first();
            if ($batch) {
               

                $open_balance = $product->quantity;
                $product->quantity = $product->quantity + $pb['receiveqty'];
                $product->location = $pb['location'];
                $product->save();
                if (!is_null($product->prod_code)) {
                    $product1 = product::where('id', $product->product_id)->first();

                    


                    if ($product1) {
                        $opening_balance = $product1->quantity;
                        $product1->quantity = $product1->quantity + $pb['receiveqty'];
                        $product1->save();

                         $batchproduct = BatchProduct::where('product_id', $product->product_id)
                    ->where('batch_id', $batch->id)
                    ->first();

                if ($batchproduct) {
                    $batchproduct->quantity += $pb['receiveqty'];
                    $batchproduct->save();
                } else {
                    $batchproduct = BatchProduct::create([
                        'batch_id'   => $batch->id,
                        'product_id' => $product->product_id,
                        'quantity'   => $pb['receiveqty'],
                        'date'       => now()->toDateString(),
                        'supp_in_no' => strtoupper($request['supp_inv_no']),
                    ]);
                }

                  $batch->quantity += $pb['receiveqty'];
        $batch->save();

                        $lineRecv = (int) $pb['receiveqty'];
                        $formattedSampleRef = $suppInvNoUpper . '(' . $lineRecv . '/0)';

                        if ($sampleUniqueReference && $lineRecv > 0) {
                            UniqueReferenceNumberProduct::create([
                                'unique_referencenumber_id' => $sampleUniqueReference->id,
                                'product_id' => $product1->id,
                                'originalqty' => $lineRecv,
                                'remaining_qty' => $lineRecv,
                                'remark' => 'Created from sample purchase bill store',
                            ]);
                        }

                        $s = stockLog::create([
                            'product_id' => $product1->id,
                            'voucher_no' => strtoupper($product->code),
                            'supplier_inv_no' => $suppInvNoUpper,
                            'ref_no' => $purchaseOrder->ref_supplier,
                            'quantity' => $pb['receiveqty'],
                            'opening_balance' => $opening_balance,
                            'remaining_stock' => $product1->quantity,
                            'type' => 1,
                            'batch_no'        => $batch->batch_no,
                            'supplier_name'        => $supplier->c_name,
                            'batch_balance'   => $batchproduct->quantity,
                            'reference_number' => $formattedSampleRef,
                            'reference_quantity' => $lineRecv,
                        ]);

                    }
                }

            }
        }
      
        $poTableCheck = posTable::where('poid', $request['purchaseOrder_id'])->get();

        foreach ($poTableCheck as $poTableChecks) {
            $statusremqty = $statusremqty + $poTableChecks->remqty;
        }

        if ($statusremqty == 0) {
            $purchaseOrder->status = 1;
            $purchaseOrder->remqty = $statusremqty;
            $purchaseOrder->save();
        } else {
            $purchaseOrder->status = 0;
            $purchaseOrder->remqty = $statusremqty;
            $purchaseOrder->save();
        }

        return redirect('/purchaseBill/samples')->with('success', 'Sample Stock was added successfully.');
    }




    public function modalSample($id)
    {
        $samplePurchaseBill = samplePurchaseBill::where('id', $id)->first();
        $purchaseOrderid = $samplePurchaseBill->purchaseOrder_id;
        $samplePurchaseOrder = samplePurchaseOrder::where('id', $purchaseOrderid)->first();

        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }

        $spbTable = spbTable::where('sample_purchasebill_id', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseBill/modalSample', ['samplePurchaseBill' => $samplePurchaseBill, 'print' => $print, 'companyDetails' => $companyDetails, 'spbTable' => $spbTable, 'samplePurchaseOrder' => $samplePurchaseOrder, 'fileMap' => $fileMap]);
    }
    public function exportcsvsample(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new InwardSupplySampleExport($fsd, $fed))->download('inwardSupplySample.xlsx');
    }



    public function purchase_billService()
    {
        $da = '2021-02-28';
        $purchaseBill = servicePurchaseBill::where('supp_inv_date', '>', $da)
            ->whereNull('swap_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchaseBill/purchase_bills_service', ['purchaseBill' => $purchaseBill]);
    }
    public function modalservice($id)
    {
        $purchaseBill = servicePurchaseBill::where('id', $id)->first();
        $purchaseOrderid = $purchaseBill->purchaseOrder_id;
        $purchaseOrder = Service::where('id', $purchaseOrderid)->first();

        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        $pbTable = ServicePbTable::where('purchasebill_id', $id)->with('ServiceProduct')->get();
        $subpbtable = SubServicePbTable::where('purchase_bill_id', $id)->with('purchaseBillProduct')->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseBill/modal_service', ['purchaseBill' => $purchaseBill, 'print' => $print, 'companyDetails' => $companyDetails, 'pbTable' => $pbTable, 'purchaseOrder' => $purchaseOrder, 'fileMap' => $fileMap, 'subpbtable' => $subpbtable]);
    }
    public function verifyService($id)
    {
        servicePurchaseBill::where('id', $id)->update(['is_checked' => 1]);
        echo 1;
        die;
        //return redirect('/purchaseBill')->with('success', 'Supply verified successfully.');
    }


    public function service()
    {
        $purchaseBill = servicePurchaseBill::get();

        //  echo "<pre>";
        //  print_r($purchaseBill);die;
        return view('purchaseBill/service', ['purchaseBill' => $purchaseBill]);
    }

    public function createService()
    {
        $purchaseOrder = Service::query()
            ->where('status', 0)
            ->whereHas('poTable', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('remqty', '>', 0)
                        ->orWhere('remaining_amount', '>', 0.01)
                        ->orWhere('remaining_percentage', '>', 0.0001);
                });
            })
            ->with('supplier')
            ->orderByDesc('id')
            ->get();

        return view('purchaseBill/create_service', ['purchaseOrder' => $purchaseOrder]);
    }

    public function servicePoLines(int $id)
    {
        $po = Service::with('supplier')->find($id);
        if (!$po) {
            throw new NotFoundHttpException();
        }

        ServiceInvoiceQuantityService::syncAllForPo((int) $id);

        $poLines = serviceTable::where('poid', $id)
            ->with(['product', 'sub_serviceproduct'])
            ->orderBy('id')
            ->get();

        $lines = [];
        foreach ($poLines as $line) {
            $hasRemaining = $line->unit === 'Count'
                ? ((float) $line->remaining_percentage > 0.0001 || (float) $line->remaining_amount > 0.01)
                : ((float) $line->remqty > 0.0001 || (float) $line->remaining_amount > 0.01);

            if (!$hasRemaining) {
                continue;
            }

            $subs = [];
            foreach ($line->sub_serviceproduct as $sub) {
                $subOpen = $line->unit === 'Count'
                    ? ((float) $sub->sub_remaining_percentage > 0.0001 || (float) $sub->sub_remaining_amount > 0.01)
                    : ((float) $sub->sub_remqty > 0.0001);

                if (!$subOpen) {
                    continue;
                }

                $subs[] = [
                    'id' => $sub->id,
                    'sub_name' => $sub->sub_name,
                    'sub_rate' => round((float) $sub->sub_rate, 2),
                    'sub_gstslab' => round((float) ($sub->sub_gstslab ?: $line->gstslab), 2),
                    'sub_remqty' => round((float) $sub->sub_remqty, 2),
                    'sub_remaining_percentage' => round((float) $sub->sub_remaining_percentage, 2),
                    'sub_remaining_amount' => round((float) $sub->sub_remaining_amount, 2),
                ];
            }

            $lines[] = [
                'line_id' => $line->id,
                'product_id' => $line->product_id,
                'name' => optional($line->product)->name ?? ('Product #' . $line->product_id),
                'unit' => $line->unit,
                'rate' => round((float) $line->rate, 2),
                'gstslab' => round((float) $line->gstslab, 2),
                'remqty' => round((float) $line->remqty, 2),
                'remaining_percentage' => round((float) $line->remaining_percentage, 2),
                'remaining_amount' => round((float) $line->remaining_amount, 2),
                'subs' => $subs,
            ];
        }

        return response()->json([
            'purchase_order' => [
                'id' => $po->id,
                'pono' => $po->pono,
                'supplier_name' => optional($po->supplier)->c_name,
                'supplier_city' => optional($po->supplier)->city,
                'supplier_tds_percent' => optional($po->supplier)->tdspercent,
                'del_date' => $po->del_date,
                'ref_supplier' => $po->ref_supplier,
                'remarks' => $po->remarks,
                'ordered_qty' => $po->tquantity,
                'po_sub_total' => round((float) $po->subTotal, 2),
                'remaining_qty' => round((float) $po->remqty, 2),
                'remaining_amount' => round((float) serviceTable::where('poid', $id)->sum('remaining_amount'), 2),
            ],
            'lines' => $lines,
        ]);
    }

    public function storeservice(Request $request, ServiceAdminInwardService $service)
    {
        return $service->execute($request);
    }

    public function dataService()
    {
        $purchaseOrder = Service::all();
        $supplier = supplier::all();
        $product = ServiceProduct::all();
        return response()->json(['purchaseOrder' => $purchaseOrder, 'supplier' => $supplier, 'product' => $product]);
    }
    public function serviceTableData($id)
    {
        $poTable = serviceTable::where('poid', $id)->get();
        return response()->json(['poTable' => $poTable]);
    }



    public function Serviceexportcsv(Request $request)
    {
        $fsd = $request->input('fsd');
        $fed = $request->input('fed');

        if (!$fsd || !$fed) {
            return response()->json(['error' => 'Both "fsd" and "fed" dates are required.'], 400);
        }

        return (new ServiceInwardSupplyExport($fsd, $fed))->download('ServiceinwardSupply.xlsx');
    }


    public function Servicebillstracking(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new ServiceBillsDueTrackingExport($fsd, $fed))->download('billstracking.xlsx');
    }


    public function purchase_billsconsumables()
    {
        $da = '2021-02-28';
        $purchaseBill = purchaseBillConsumable::with(['purchaseOrder.supplier', 'pbTable', 'supplier'])
            ->where('supp_inv_date', '>', $da)
            ->whereNull('swap_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchaseBill/purchase_billsConsumables', ['purchaseBill' => $purchaseBill]);
    }

    public function purchase_billscarton()
    {
        $da = '2021-02-28';
        $purchaseBill = PurchaseBillCarton::with(['purchaseOrder.supplier', 'pbTable', 'supplier'])
            ->where('supp_inv_date', '>', $da)
            ->whereNull('swap_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchaseBill/purchase_billsCarton', ['purchaseBill' => $purchaseBill]);
    }


    public function modalcarton($id)
    {

        $purchaseBill = PurchaseBillCarton::where('id', $id)->first();
        $purchaseOrder = purchaseOrderConsumable::find(
            SupplierMultiPoSupport::firstConsumablePurchaseBillPoId($purchaseBill)
        );

        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        $pbTable = PbTableCorton::where('purchasebill_id', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseBill/modalcarton', ['purchaseBill' => $purchaseBill, 'print' => $print, 'companyDetails' => $companyDetails, 'pbTable' => $pbTable, 'purchaseOrder' => $purchaseOrder, 'fileMap' => $fileMap]);
    }


    public function modalconsumables($id)
    {
        $purchaseBill = purchaseBillConsumable::with('supplier')->where('id', $id)->first();
        $poIds = SupplierMultiPoSupport::consumablePurchaseBillPoIds($purchaseBill);
        $purchaseOrders = $poIds !== []
            ? purchaseOrderConsumable::whereIn('id', $poIds)->with('supplier')->get()
            : collect();
        $purchaseOrder = $purchaseOrders->first()
            ?? purchaseOrderConsumable::find(SupplierMultiPoSupport::firstConsumablePurchaseBillPoId($purchaseBill));
        $isMultiPoBill = SupplierMultiPoSupport::isMultiPoPurchaseBill($purchaseBill);

        $podates = $purchaseOrders->pluck('podate')->filter()->unique()->values();
        $delDates = $purchaseOrders->pluck('del_date')->filter()->unique()->values();
        $buyerOrderNos = $purchaseOrders->pluck('buyer_orderno')->filter()->unique()->values();
        $refSuppliers = $purchaseOrders->pluck('ref_supplier')->filter()->unique()->values();
        $payterms = $purchaseOrders->pluck('payterms')->filter()->unique()->values();
        $remarks = $purchaseOrders->pluck('remarks')->filter()->unique()->values();

        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        $pbTable = pbTableConsumable::where('purchasebill_id', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseBill/modalconsumable', [
            'purchaseBill' => $purchaseBill,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'pbTable' => $pbTable,
            'purchaseOrder' => $purchaseOrder,
            'fileMap' => $fileMap,
            'isMultiPoBill' => $isMultiPoBill,
            'podates' => $podates,
            'delDates' => $delDates,
            'buyerOrderNos' => $buyerOrderNos,
            'refSuppliers' => $refSuppliers,
            'payterms' => $payterms,
            'remarks' => $remarks,
        ]);
    }


    public function verifyconsumable($id)
    {

        purchaseBillConsumable::where('id', $id)->update(['is_checked' => 1]);
        echo 1;
        die;
    }

    public function verifycarton($id)
    {

        PurchaseBillCarton::where('id', $id)->update(['is_checked' => 1]);
        echo 1;
        die;
    }


    public function purchase_billsMulti(Request $request)
    {
        $da = '2021-02-28';
        $search = $request->input('search');

        $purchaseBill = purchaseBill::with(['purchaseOrder.supplier'])
            ->where('supp_inv_date', '>', $da)
            ->whereNull('swap_id')
            ->whereNotNull('mulitple_po_purchaseBill')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supp_inv_date', 'like', "%{$search}%")
                        ->orWhere('supp_inv_no', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                            $q2->where('ref_supplier', 'like', "%{$search}%")
                                ->orWhere('pono', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseOrder.supplier', function ($q3) use ($search) {
                            $q3->where('c_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->except('page')); // <-- pagination with filters preserved

        return view('purchaseBill/purchase_billsMulti', ['purchaseBills' => $purchaseBill]);
    }



    public function modalMulti($id)
    {
        $purchaseBill = purchaseBill::where('id', $id)->with('supplier')->first();
        $purchaseOrderIds = explode(',', $purchaseBill->purchaseOrder_id);
        $purchaseOrderid = $purchaseBill->purchaseOrder_id;
        $purchaseOrder = purchaseOrder::where('id', $purchaseOrderid)->get();
        $deliveryDates = purchaseOrder::whereIn('id', $purchaseOrderIds)
            ->pluck('podate')
            ->unique()
            ->values();
        $buyer_orderno = purchaseOrder::whereIn('id', $purchaseOrderIds)
            ->pluck('buyer_orderno')
            ->values();
        $ref_supplier = purchaseOrder::whereIn('id', $purchaseOrderIds)
            ->pluck('ref_supplier')
            ->values();
        $payterms = purchaseOrder::whereIn('id', $purchaseOrderIds)
            ->pluck('payterms')
            ->unique()
            ->values();
        $remarks = purchaseOrder::whereIn('id', $purchaseOrderIds)
            ->pluck('remarks')
            ->unique()
            ->values();

        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        $pbTable = pbTable::where('purchasebill_id', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseBill/modalmulti', ['purchaseBill' => $purchaseBill, 'print' => $print, 'companyDetails' => $companyDetails, 'pbTable' => $pbTable, 'purchaseOrder' => $purchaseOrder, 'fileMap' => $fileMap, 'deliveryDates' => $deliveryDates, 'buyer_orderno' => $buyer_orderno, 'ref_supplier' => $ref_supplier, 'payterms' => $payterms, 'remarks' => $remarks]);
    }

    protected function getInwardSupplyEligibilityDate($purchaseOrder): ?Carbon
    {
        if (!$purchaseOrder) {
            return null;
        }

        $rawDeliveryDate = null;
        foreach (['del_date', 'delivery_date', 'podate'] as $field) {
            if (!empty(optional($purchaseOrder)->{$field})) {
                $rawDeliveryDate = optional($purchaseOrder)->{$field};
                break;
            }
        }

        if (empty($rawDeliveryDate)) {
            return null;
        }

        try {
            $deliveryDate = Carbon::parse($rawDeliveryDate)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        return $deliveryDate->copy()->subDays(7)->startOfDay();
    }
}
