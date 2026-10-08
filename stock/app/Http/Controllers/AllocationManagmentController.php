<?php

namespace App\Http\Controllers;

use App\ContainersAllocation;
use App\ContainersAllocationItem;
use App\ContainersAllocationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\supplier;
use App\product;
use App\Notification;
use App\purchaseOrder;
use App\supplierProduct;
use App\poTable;
use App\User;
use DB;
use App\Mail\SupplierReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\packingList;
use App\packingListProduct;
use App\packagingPrice;
use App\packaging;
use App\popTable;
use App\setting;
use App\purchaseOrderConsumable;

use App\Exports\AllocationListExport;
use App\Exports\PackagingListExport;
use App\Exports\NegativeItemListExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Common;
use App\Support\SupplierProductPriceLogWriter;

class AllocationManagmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        $records = ContainersAllocation::with('containerAllocationItems')
                ->where('status','!=','completed')
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('tenant', 'like', '%' . $search . '%')
                        ->orWhere('container_number', 'like', '%' . $search . '%');
                    });
                })
                ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                    $query->whereBetween('planned_date', [$date_from, $date_to]);
                })
                ->orderByDesc('id')
                ->paginate(50);

        return view("ContainerAllocations.index", compact("records"));
    }

    public function history(Request $request)
    {
        $search = $request->input('search');
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        $records = ContainersAllocation::with('containerAllocationItems')
                ->where('status','completed')
                ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tenant', 'like', '%' . $search . '%')
                    ->orWhere('container_number', 'like', '%' . $search . '%');
                });
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('planned_date', [$date_from, $date_to]);
            })
            ->orderByDesc('id')
            ->paginate(50);

        return view("ContainerAllocations.history", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("ContainerAllocations.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(ContainersAllocation $allocation)
    {
        return view("ContainerAllocations.view", compact('allocation'));
    }
    public function showHistory(ContainersAllocation $allocation)
    {
        return view("ContainerAllocations.view-history-detail", compact('allocation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ContainersAllocation $allocation)
    {
        return view("ContainerAllocations.edit", compact('allocation'));
    }

    public function updateVolume(Request $request, $id)
    {
        ContainersAllocationItem::where('container_allocation_id', $request->allocation_container_id)->where('product_id', $request->product_id)->where('id',$id)->update(['physical_volume' => $request->volume]);

        return redirect()->back()->with('success', 'Volume updated successfully.');

    }
    public function markCompletedAndDispatched(Request $request, $id)
    {
        ContainersAllocation::where('id', $id)->update(['status' => 'completed','completed_and_dispatched_on'=> date("Y-m-d")]);
        return redirect()->back()->with('success', 'Container Marked As Dispatched and Completed Successfully.');

    }

    public function pushToInventory(ContainersAllocation $allocation)
    {
        $allocation->update(['push_to_inventory' =>true,'status'=>'in_progress']);
        return redirect()->back()->with('success', 'Pushed to inventory successfully.');

    }


    public function viewSupplier(Request $request, $id)
    {
        $record = ContainersAllocation::find($id);
        if (!$record) {
            return redirect()->back()->with('error', 'Allocation not found.');
        }
        $sku_records = [];
        $productSupplierRates = [];

        $furnitureSupplierTypes = ['furniture', 'both'];
        $allSuppliers = supplier::orderBy('c_name')
            ->whereRaw('LOWER(TRIM(type)) IN (?, ?)', $furnitureSupplierTypes)
            ->select('id', 'c_name', 'monthly_invoice_limit')
            ->get();

        // Fallback if supplier type data is inconsistent in master.
        if ($allSuppliers->isEmpty()) {
            $allSuppliers = supplier::orderBy('c_name')
                ->select('id', 'c_name', 'monthly_invoice_limit')
                ->get();
        }

        $allSuppliersForJs = $allSuppliers->map(static function ($s) {
            return [
                'id'                    => $s->id,
                'c_name'                => $s->c_name,
                'monthly_invoice_limit' => (float) ($s->monthly_invoice_limit ?? 0),
            ];
        })->values();

        foreach ($record->containerAllocationItems as $key => $sku_record) {
            $sku = $sku_record['sku'];
            $qty = $sku_record['qty'];
            $product = product::where('code', $sku)->first();
            if (!$product) {
                $sku_records[$key]['product'] = null;
                continue;
            }

            $sku_records[$key]['product'] = $product->name;
            $sku_records[$key]['product_id'] = $product->id;
            $sku_records[$key]['product_sku'] = $product->code;
            $sku_records[$key]['product_qty'] = $qty;
            $sku_records[$key]['current_allocation'] = $sku_record['current_allocation'];

            $suppliers = supplier::whereRaw('LOWER(TRIM(type)) IN (?, ?)', $furnitureSupplierTypes)
            ->whereHas('supplierProduct.product', function ($query) use ($sku) {
                $query->where('code', $sku);
            })->with(['supplierProduct' => function ($query) use ($sku) {
                $query->whereHas('product', function ($q) use ($sku) {
                    $q->where('code', $sku);
                })->with(['product' => function ($q) use ($sku) {
                    $q->where('code', $sku);
                }]);
            }])->get();

            $sku_records[$key]['supplier'] = $suppliers;
            $productSupplierRates[$product->id] = supplierProduct::where('product_id', $product->id)
                ->pluck('rate', 'supplier_id')
                ->map(fn ($r) => (float) $r)
                ->toArray();
        }


        return view("ContainerAllocations.view-supplier", compact('record', 'sku_records', 'allSuppliersForJs', 'productSupplierRates', 'allSuppliers'));
    }

    public function addSupplierProduct(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'supplier_id' => 'required|integer',
            'rate' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $allocation = ContainersAllocation::find($id);
        if (! $allocation) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Allocation not found.'], 404);
            }
            return redirect()->back()->with('error', 'Allocation not found.');
        }

        $supplier = supplier::find($request->supplier_id);
        if (! $supplier) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Supplier not found.'], 404);
            }
            return redirect()->back()->with('error', 'Supplier not found.');
        }

        $supplierType = strtolower(trim((string) ($supplier->type ?? '')));
        if (! in_array($supplierType, ['furniture', 'both'], true)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Only suppliers with type Furniture or Both can be assigned.'], 422);
            }
            return redirect()->back()->with('error', 'Only suppliers with type Furniture or Both can be assigned.');
        }

        $product = product::find($request->product_id);
        if (! $product) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
            }
            return redirect()->back()->with('error', 'Product not found.');
        }

        $exists = supplierProduct::where('product_id', $request->product_id)
            ->where('supplier_id', $request->supplier_id)
            ->exists();
        if ($exists) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'This supplier is already assigned to the selected product.'], 422);
            }
            return redirect()->back()->with('error', 'This supplier is already assigned to the selected product.');
        }

        $mapping = supplierProduct::create([
            'product_id' => (int) $request->product_id,
            'supplier_id' => (int) $request->supplier_id,
            'rate' => (float) $request->rate,
        ]);
        SupplierProductPriceLogWriter::log($mapping, 'create', 'allocation', null);

        if ($request->ajax() || $request->wantsJson()) {
            $commonHelper = new Common();
            $invoiceGeneratedInMonth = (float) $commonHelper->getSupplierMonthlyInvoice((int) $supplier->id, $allocation->planned_date);

            return response()->json([
                'success' => true,
                'message' => 'Supplier assigned to product successfully.',
                'data' => [
                    'product_id' => (int) $product->id,
                    'supplier_id' => (int) $supplier->id,
                    'supplier_name' => $supplier->c_name,
                    'rate' => (float) $mapping->rate,
                    'monthly_invoice_limit' => (float) ($supplier->monthly_invoice_limit ?? 0),
                    'invoice_generated_in_month' => $invoiceGeneratedInMonth,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Supplier assigned to product successfully. You can now allocate quantity.');
    }

    public function updateAllocation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'product_sku' => 'required',
            'supplier_id' => 'required',
            'asked_quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try
        {
            $allocation = ContainersAllocation::find($id);
            if (! $allocation) {
                return response()->json(['error' => 'Allocation not found'], 404);
            }

            $productIds   = $request->input('product_id', []);
            $skus         = $request->input('product_sku', []);
            $supplierIds  = $request->input('supplier_id', []);
            $askedQs      = $request->input('asked_quantity', []);
            // Check if arrays are consistent in length
            if (count($productIds) !== count($skus) || count($productIds) !== count($supplierIds) || count($productIds) !== count($askedQs)) {
                return redirect()->back()->with('error', 'Data mismatch in the provided input arrays.');
            }

            foreach ($productIds as $i => $productId) {
                $sku = $skus[$i] ?? null;

                if (empty($supplierIds[$i]) || ! is_array($supplierIds[$i]) || empty($askedQs[$i])) {
                    continue;
                }

                foreach ($supplierIds[$i] as $j => $supplierId) {
                    $qty = (float) ($askedQs[$i][$j] ?? 0);
                    $normalizedSupplierId = ($supplierId === "IN STOCK") ? 0 : $supplierId;

                    if ($qty > 0)
                    {
                        $data = [
                            'container_allocation_id' => $allocation->id,
                            'product_sku'             => $sku,
                            'product_id'              => $productId,
                            'supplier_id'             => $normalizedSupplierId,
                            'is_in_stock'             => ($supplierId === "IN STOCK"),
                            'asked_quantity'          => $qty,
                            'status'                  => 'pending',
                            'updated_at'              => now(),
                            'created_at'              => now(), // Laravel ignores this if record exists
                        ];


                        $query = ContainersAllocationDetail::where('container_allocation_id', $allocation->id)
                            ->where('product_id', $productId)
                            ->where('supplier_id', $normalizedSupplierId);

                        $exists = $query->first();

                        if ($exists) {
                            if (is_null($exists->purchase_order_id)) {
                                $exists->update($data);
                            }
                        } else {
                            ContainersAllocationDetail::create($data);
                        }


                        $total_asked_qty = ContainersAllocationDetail::where('container_allocation_id', $allocation->id)
                            ->where('product_id', $productId)->sum('asked_quantity');

                        $query = ContainersAllocationItem::where('container_allocation_id', $allocation->id)
                                ->where('product_id', $productId)->first();
                        if($query)
                        {
                            $query->current_allocation = $total_asked_qty;
                            $query->save();
                        }


                    }
                }
            }



            // $users = \DB::table('users')->where('role', 'Procurement Manager')->get();
            // $notifications = [];
            // foreach ($users as $user) {
            //     $notifications[] = [
            //         'user_id' => $user->id,
            //         'notification' => 'Container Allocation Data is approved and is ready for po generation successfully.',
            //         'is_read' => 0,
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ];
            // }

            // // Bulk insert notifications
            // if (!empty($notifications)) {
            //     \DB::table('notifications')->insert($notifications);
            // }

            return redirect()->route('allocation-management.index')->with('success', 'Container Allocation Data approved successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update data: ' . $e->getMessage());
        }
    }

    public function updateAllocationDetail(Request $request)
    {
        DB::beginTransaction();

        try {
            $record = ContainersAllocationDetail::findOrFail($request->row_id);

            if ($request->asked_quantity != 0) {
                $record->update(['asked_quantity' => $request->asked_quantity]);
            } else {
                $record->delete();
            }

            $totalAskedQty = ContainersAllocationDetail::where('container_allocation_id', $record->container_allocation_id)
                ->where('product_id', $record->product_id)
                ->sum('asked_quantity');

            ContainersAllocationItem::where('container_allocation_id', $record->container_allocation_id)
                ->where('product_id', $record->product_id)
                ->update(['current_allocation' => $totalAskedQty]);

            DB::commit();

            return redirect()->back()->with('success', 'Data updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update data: ' . $e->getMessage());
        }
    }

    public function updatePODetails($old_record)
    {
        $purchase_order_id = $old_record->purchase_order_id;
        $supplier_id = $old_record->supplier_id;
        $container_allocation_id = $old_record->container_allocation_id;

        $lastPo = purchaseOrder::find($purchase_order_id);

        if (!$lastPo) {
            return [
                'status' => 0,
                'message' => "PO Not Found"
            ];
        }

        if ($lastPo->supplier_status == 1) {
            return [
                'status' => 0,
                'message' => "Supplier has accepted the PO. You can not change it now."
            ];
        }

        // Save values from old PO
        $pono = $lastPo->pono;
        $delivery_date = $lastPo->delivery_date;

        // Delete old PO data
        poTable::where('poid', $purchase_order_id)->delete();
        $lastPo->delete();

        // Constants
        $payterms = "30-45 Days";
        $remarks = "1. Wood must be seasoned & chemically treated.\n2. Timber MUST be sourced from regulated & legal plantations only.";
        $address_option = "1";
        $now = now();

        $allocation_ids = ContainersAllocationDetail::where('container_allocation_id', $container_allocation_id)
            ->pluck('id');

        $allocationDetails = ContainersAllocationDetail::with('containerAllocationInfo')
            ->whereIn('id', $allocation_ids)
            ->get()
            ->keyBy('id');

        $productIds = $allocationDetails->pluck('product_id')->unique()->values();
        $products = product::whereIn('id', $productIds)->get()->keyBy('id');

        $supplierProducts = supplierProduct::where('supplier_id', $supplier_id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $poItems = [];
        $subTotal = $totalGst = $totalQty = 0;

        foreach ($allocation_ids as $detailId) {
            $record = $allocationDetails[$detailId];
            $containerInfo = $record->containerAllocationInfo;
            $product = $products[$record->product_id];
            $supplierProduct = $supplierProducts[$record->product_id];

            $amount = $record->asked_quantity * $supplierProduct->rate;
            $gst = round(($amount * $product->gstslab) / 100, 2);

            $poItems[] = [
                'product_id' => $record->product_id,
                'description' => 'NA',
                'ean' => $product->EAN,
                'quantity' => $record->asked_quantity,
                'unit' => $product->unit ?? 'No.',
                'remqty' => $record->asked_quantity,
                'rate' => $supplierProduct->rate,
                'amount' => $amount,
                'gstslab' => $product->gstslab,
                'gstamount' => $gst,
                'priority' => "1",
                'delivery_point' => "Unit 1",
                'legs' => "1",
                'created_at' => $now,
                'updated_at' => $now
            ];

            $subTotal += $amount;
            $totalGst += $gst;
            $totalQty += $record->asked_quantity;

            // Assuming all rows share same container/buyer info
            $podate = $containerInfo->planned_date;
            $buyerOrderNo = strtoupper($containerInfo->buyer_order_number);
        }

        $poData = [
            'pono' => $pono,
            'supplier_id' => $supplier_id,
            'podate' => $podate ?? now(),
            'del_date' => $delivery_date,
            'ref_supplier' => $buyerOrderNo ?? '',
            'buyer_orderno' => $buyerOrderNo ?? '',
            'payterms' => $payterms,
            'remarks' => $remarks,
            'tgst' => $totalGst,
            'tquantity' => $totalQty,
            'tamount' => $subTotal + $totalGst,
            'subTotal' => $subTotal,
            'remqty' => $totalQty,
            'address_option' => $address_option
        ];

        $purchaseOrder = purchaseOrder::create($poData);

        foreach ($poItems as &$item) {
            $item['poid'] = $purchaseOrder->id;
        }

        poTable::insert($poItems);

        return [
            'status' => 1,
            'message' => "PO Updated"
        ];
    }

    public function deleteOldEntry(Request $request)
    {

        DB::beginTransaction();
        try
        {
            $exists =  ContainersAllocationDetail::where('container_allocation_id', $request->container_allocation_id)
                        ->where('product_id', $request->product_id)
                        ->where('supplier_id', $request->supplier_id)->first();
            if ($exists) {
                $purchase_order_id = $exists->purchase_order_id;

                // Update all records with purchase order
                ContainersAllocationDetail::where('purchase_order_id',$purchase_order_id)->update(['status'=>'pending','purchase_order_id'=>null,'po_consumable_id'=>null]);

                // Cancel the OLD PO

                $purchaseOrder = purchaseOrder::where('id', $purchase_order_id)->first();
                if($purchaseOrder){
                    $poRelationCount = $purchaseOrder->purchaseBill->count();
                    if ($poRelationCount > 0) {
                        return response()->json(['error' => 'Purchase Order cannot be canceled. It exists in other relations.'], 404);
                    }

                    $purchaseOrder->status = 2;
                    $purchaseOrder->save();
                }
                DB::commit();

                return response()->json(['success' => 'Old entry deleted successfully.']);
            } else {
                return response()->json(['error' => 'No matching record found to delete.'], 404);
            }
        }
        catch(\Exception $e)
        {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function viewSupplierAllocation(Request $request, $id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails'])->findOrFail($id);

        $pendingCount = ContainersAllocationDetail::where('container_allocation_id', $id)
                ->where('status', 'pending')->where('is_in_stock',0)->count();

        return view("ContainerAllocations.po-generation", compact('record','pendingCount'));
    }

    public function viewSupplierAllocation2(Request $request, $id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails'])->findOrFail($id);
        $packaging_suppliers = supplier::where('do_packaging','Yes')->get();

        $pendingCount = ContainersAllocationDetail::where('container_allocation_id', $id)
                ->where('po_consumable_id',null)->where('status','po_generated')->where('is_in_stock',0)->count();

        return view("ContainerAllocations.carton-po-generation", compact('record','pendingCount','packaging_suppliers'));
    }


    public function generatePo(Request $request, $id)
    {
        // DB::beginTransaction();
        try
        {

            $selected_sellers = $request->input('selected_keys');
            $payterms = "30-45 Days";
            $remarks = "1. Wood must be seasoned & chemically treated.\n2. Timber MUST be sourced from regulated & legal plantations only.";
            $address_option = "1";

           // dd($selected_sellers);

            foreach ($selected_sellers as $supplier_id => $allocation_ids) {
                $lastPo = purchaseOrder::where('supplier_id', '!=', '17')
                    ->where('address_option', '!=', 100)
                    ->where('podate', '>', '2021-03-31')
                    ->where('pono', 'not like', 'BO/%')
                    ->orderBy('id', 'desc')
                    ->first();
                $pono = $lastPo ? (int) $lastPo->pono + 1 : 1;

                // Pre-fetch all required ContainerAllocationDetails with relationships
                $allocationDetails = ContainersAllocationDetail::with('containerAllocationInfo')
                    ->whereIn('id', $allocation_ids)
                    ->get()
                    ->keyBy('id');

                $productIds = $allocationDetails->pluck('product_id')->unique()->values();
                $products = product::whereIn('id', $productIds)->get()->keyBy('id');

                $supplierProducts = supplierProduct::where('supplier_id', $supplier_id)
                    ->whereIn('product_id', $productIds)
                    ->get()
                    ->keyBy('product_id');

                $poItems = [];
                $subTotal = $totalGst = $totalQty = 0;
                $ref_supplier = $buyer_orderno = $podate = $del_date = null;

                foreach ($allocation_ids as $detailId) {
                    $record = $allocationDetails[$detailId];
                    $containerInfo = $record->containerAllocationInfo;
                    $product = $products[$record->product_id];
                    $supplierProduct = $supplierProducts[$record->product_id];

                    $podate = $containerInfo->planned_date;
                    $del_date = $request->delivery_date;

                    $ref_supplier = $buyer_orderno = $containerInfo->buyer_order_number;

                    $amount = $record->asked_quantity * $supplierProduct->rate;
                    $gst = round(($amount * $product->gstslab) / 100, 2);

                    $poItems[] = [
                        'product_id' => $record->product_id,
                        'description' => 'NA',
                        'ean' => $product->EAN,
                        'quantity' => $record->asked_quantity,
                        'unit' => $product->unit ?? 'No.',
                        'remqty' => $record->asked_quantity,
                        'rate' => $supplierProduct->rate,
                        'amount' => $amount,
                        'gstslab' => $product->gstslab,
                        'gstamount' => $gst,
                        'priority' => "1",
                        'delivery_point' => "Unit 1",
                        'legs' => "1",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Totals
                    $subTotal += $amount;
                    $totalGst += $gst;
                    $totalQty += $record->asked_quantity;
                }

                $poData = [
                    'pono' => $pono,
                    'supplier_id' => $supplier_id,
                    'podate' => $podate,
                    'del_date' => $del_date,
                    'ref_supplier' => strtoupper($ref_supplier),
                    'buyer_orderno' => strtoupper($buyer_orderno),
                    'payterms' => $payterms,
                    'remarks' => $remarks,
                    'tgst' => $totalGst,
                    'tquantity' => $totalQty,
                    'tamount' => $subTotal + $totalGst,
                    'subTotal' => $subTotal,
                    'remqty' => $totalQty,
                    'address_option' => $address_option
                ];

                $purchaseOrder = purchaseOrder::create($poData);

                foreach ($poItems as &$item) {
                    $item['poid'] = $purchaseOrder->id;
                }
                unset($item);

                poTable::insert($poItems); // Use batch insert for performance

                // Generate Notification
                $user = User::where('supplier_id',$supplier_id)->first();
                if(isset($user->id)){
                    Notification::create([
                        'user_id' => $user->id,
                        'notification' => 'Received new Purchase Order - '. strtoupper($pono),
                        'is_read' => 0
                    ]);
                }

                // Update status of allocation in a single query for efficiency
                ContainersAllocationDetail::whereIn('id', $allocation_ids)->update(['status' => 'po_generated','purchase_order_id' => $purchaseOrder->id]);

            }

            // DB::commit();
            return redirect()->back()->with('success', 'PO Generated successfully.');

        } catch (\Exception $e) {
            // DB::rollback();
            return redirect()->back()->with('error', 'Failed to generate PO: ' . $e->getMessage());
        }
    }

     public function generateCartonPo(Request $request, $id)
    {
        DB::beginTransaction();
        try
        {
            $supplier_id = $request->input('supplier_id');

            $record = ContainersAllocation::with(['containerAllocationDetails' => function ($query) {
                $query->where('status', 'po_generated');
            }])->findOrFail($id);

            $buyer_order_no = $record->buyer_order_number;

            //get Distinct Purchase Orders
            $distinctPoIds = $record->containerAllocationDetails->pluck('purchase_order_id')->unique()->values()->all();
            // Cache setting to avoid multiple DB hits
            $companyDetails = setting::first();

            if(!empty($supplier_id))
            {
                foreach ($distinctPoIds as $po_id) {

                    $purchase_order = purchaseOrder::with('poTable')->find($po_id);
                    if (!$purchase_order) continue;

                    // Cache pricing details
                    $pricingDetails = packagingPrice::where('supplier_id',  $supplier_id)->first();
                    $_3ply = $pricingDetails->{"3ply"} ?? 0;
                    $_5ply = $pricingDetails->{"5ply"} ?? 0;
                    $_7ply = $pricingDetails->{"7ply"} ?? 0;

                    $subtotalamount = $tgst = $tquantity = $tamount = 0;
                    $pop_data = [];

                    foreach ($purchase_order->poTable as $po_item) {
                        $packaging = packaging::where('product_id', $po_item->product_id)->first();
                        if (!$packaging) continue;

                        $package_request = [
                            'box1_type' => $packaging->box1_type,
                            'box2_type' => $packaging->box2_type,
                            'box1_height' => $packaging->box1_height,
                            'box1_width' => $packaging->box1_width,
                            'box1_depth' => $packaging->box1_depth,
                            'box1_ply' => $packaging->box1_ply,
                            'box2_height' => $packaging->box2_height,
                            'box2_width' => $packaging->box2_width,
                            'box2_depth' => $packaging->box2_depth,
                            'box2_ply' => $packaging->box2_ply,
                            'no_of_boxes' => $packaging->no_of_boxes
                        ];

                        $boxData = $this->calculate_box($package_request);

                        $box1_sqinch = $boxData['box1_sqinch'] ?? 0;
                        $box2_sqinch = $boxData['box2_sqinch'] ?? 0;


                        $totrate1 = match((int) $packaging->box1_ply) {
                            3 => $box1_sqinch * $_3ply,
                            5 => $box1_sqinch * $_5ply,
                            7 => $box1_sqinch * $_7ply,
                            default => 0
                        };

                        $totrate2 = match((int) $packaging->box2_ply) {
                            3 => $box2_sqinch * $_3ply,
                            5 => $box2_sqinch * $_5ply,
                            7 => $box2_sqinch * $_7ply,
                            default => 0
                        };

                        //$qty = $po_item->quantity - $po_item->remqty;
                        $qty = $po_item->quantity;
                        $box2_qty = 0;

                        $box1_amount = $totrate1 * $qty;
                        $box2_amount = $totrate2 * $box2_qty;
                        $amount = round($box1_amount + $box2_amount, 2);

                        $gstSlab = 12;
                        $gstAmount = round(($amount * $gstSlab) / 100, 2);

                        $pop_data[] = [
                            'product_id' => $po_item->product_id,
                            'quantity' => $qty,
                            'unit' => 'Sq. Inch',
                            'sq_inches' => 0,
                            'amount' => $amount,
                            'gstslab' => $gstSlab,
                            'gstamount' => $gstAmount,
                            'box1_height' => $packaging->box1_height,
                            'box1_width' => $packaging->box1_width,
                            'box1_depth' => $packaging->box1_depth,
                            'box2_height' => $packaging->box2_height,
                            'box2_width' => $packaging->box2_width,
                            'box2_depth' => $packaging->box2_depth,
                            'box1_sqinch' => $packaging->box1_sqinch,
                            'box2_sqinch' => $packaging->box2_sqinch,
                            'box1_ply' => $packaging->box1_ply,
                            'box2_ply' => $packaging->box2_ply,
                            'box1_rate' => $packaging->box1_rate,
                            'box2_rate' => $packaging->box2_rate,
                            'box1_amount' => $packaging->box1_amount,
                            'box2_amount' => $packaging->box2_amount,
                            'line_drawing' => $packaging->line_drawing,
                            'box2_qty' => $packaging->box2_qty,
                            'remqty_box1' => $qty,
                            'remqty_box2' => $packaging->box2_qty,
                        ];

                        $subtotalamount += $amount;
                        $tgst += $gstAmount;
                        $tquantity += $qty;
                        $tamount += $amount + $gstAmount;
                    }

                    // Generate PO number
                    $pono = 'CTN/' . ($companyDetails->ctnpo_no + 1);
                    $payterms = "30-45 Days";
                    $remarks = implode("\n", [
                        "1. Goods must be delivered to our Factory Address.",
                        "2. Invoice and E waybill should be attached at the time of delivery.",
                        "3. All rates including freight charges."
                    ]);

                    $q = purchaseOrderConsumable::create([
                        'pono' => strtoupper($pono),
                        'supplier_id' => $supplier_id,
                        'podate' => $purchase_order->podate,
                        'del_date' => $purchase_order->del_date,
                        'month' => date('m-Y'),
                        'buyer_orderno' => strtoupper($buyer_order_no),
                        'payterms' => $payterms,
                        'remarks' => $remarks,
                        'subTotal' => $subtotalamount,
                        'tgst' => $tgst,
                        'tquantity' => $tquantity,
                        'tamount' => $tamount,
                        'remqty' => $tquantity,
                        'type' => 2,
                    ]);

                    foreach($pop_data as $podata)
                    {
                        $podata['poid'] = $q->id;
                        popTable::create($podata);
                    }

                    // Update counter
                    $companyDetails->ctnpo_no += 1;
                    $companyDetails->save();

                    ContainersAllocationDetail::where('purchase_order_id',$po_id)->update(['po_consumable_id'=>$q->id]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Carton PO Generated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            // dd($e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate Carton PO: ' . $e->getMessage());
        }
    }


    public function viewAllocatedSuppliers(Request $request, $id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails' => function($query){
            //$query->where('status','po_generated');
        }, 'containerAllocationDetails.purchaseOrderInfo.poTable'])->findOrFail($id);

        return view("ContainerAllocations.view_allocated_suppliers", compact('record'));
    }
    public function viewSupplierDetailedHistory(Request $request, $id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails' => function($query){
            //$query->where('status','po_generated');
        }, 'containerAllocationDetails.purchaseOrderInfo.poTable'])->findOrFail($id);

        return view("ContainerAllocations.view_supplier_detailed_history", compact('record'));
    }


    public function sendReminderEmails(Request $request, $id)
    {
        try
        {
            // $container_allocation = ContainersAllocationDetail::where('purchase_order_id', $id)->first();

            $purchase_order = purchaseOrder::with('supplier','poTable.product')->findOrFail($id);
            $user = user::where('supplier_id', $purchase_order->supplier->id)->first();

            $sent_data = [];
            foreach($purchase_order->poTable as $po_data)
            {
                if($po_data->remqty!=0)
                {
                    $data['item_name'] = $po_data->product->name;
                    $data['item_code'] = $po_data->product->code;
                    $data['asked_qty'] = $po_data->quantity;
                    $data['remaining_qty'] = $po_data->remqty;
                    $data['po_date'] = $purchase_order->podate;
                    $data['del_date'] = $purchase_order->del_date;
                    array_push($sent_data,$data);
                }

            }

            $emailData = [
                        'supplier_email' => $user->email,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'invoice_link' => URL::to('/purchaseOrder/modal/' . $id),
                        'body' => $sent_data,
                    ];

            $request = new Request($emailData);

            Mail::send(new SupplierReminderMail($request));
            return redirect()->back()->with('success', 'Reminder Email sent successfully.');

        } catch (\Exception $e) {
            // DB::rollback();
            return redirect()->back()->with('error', 'Failed to Send Email' . $e->getMessage());
        }
    }


    public function generatePackingList(Request $request, $id )
    {
        try
        {
            $record = ContainersAllocation::with(['containerAllocationDetails' => function($query){
                $query->where('status','po_generated');
            }])->findOrFail($id);

            $buyer_order_no = $record->buyer_order_number;

            $q = packingList::create
            ([
                'buyer_order_no'=> $buyer_order_no,
                'totalbox'=> 0,
                'tquantity'=> 0,
                'totalwt'=> 0,
                'grosswt'=> 0
            ]);

            foreach ($record->containerAllocationDetails as $inv) {
                packingListProduct::create([
                    'product_id' => $inv->product_id,
                    'packinglist_id' => $q->id,
                    'quantity' => $inv->asked_quantity,
                    'weight' => 0,
                    'subtotalnetwt' => 0,
                    'grosswt' => 0,
                    'subtotalgrosswt' => 0,
                    'box' => 0,
                    'endBox' => 0,
                    'subTotalBox' => 0,
                    'qtybox' => 0,
                    'batch_no' => ''
                ]);
            }

            $record->package_list_generated = true;
            $record->package_list_id = $q->id;
            $record->update();

            // DB::commit();
            return redirect()->back()->with('success', 'Package List Generated successfully.');

        } catch (\Exception $e) {
            // DB::rollback();
            return redirect()->back()->with('error', 'Failed to generate Package List: ' . $e->getMessage());
        }

    }

    public function calculate_box($request){
		//print_r($_REQUEST);die;
		if($request['box1_type'] == ""){
			$request['box1_type'] = "Standard Box";
		}
		if($request['box2_type'] == ""){
			$request['box2_type'] = "Standard Box";
		}
		$box2_sqinch = 0;
		if($request['box1_type'] == "Standard Box"){
			$box1_sqinch = $request['box1_width'] + $request['box1_depth'];
			$box1_sqinch = ceil($box1_sqinch);
			if(($box1_sqinch % 2) != 0){
				$box1_sqinch = $box1_sqinch + 1;
			}
			$box1_sqinch = $box1_sqinch * ($request['box1_height'] + $request['box1_width'] +2);
			$box1_sqinch = ($box1_sqinch * 2)/100;
			$box1_sqinch = round($box1_sqinch,2);
		}
		if($request['box1_type'] == "Over Flap"){
			$box1_sqinch = $request['box1_width'] + $request['box1_width'] + $request['box1_depth'];
			$box1_sqinch = ceil($box1_sqinch);
			if(($box1_sqinch % 2) != 0){
				$box1_sqinch = $box1_sqinch + 1;
			}
			$box1_sqinch = $box1_sqinch * ($request['box1_height'] + $request['box1_width'] +2);
			$box1_sqinch = ($box1_sqinch * 2)/100;
			$box1_sqinch = round($box1_sqinch,2);
		}
		if($request['box1_type'] == "Lateral Box"){
			$box1_sqinch = $request['box1_height'] + $request['box1_width'];
			$box1_sqinch = ceil($box1_sqinch);
			if(($box1_sqinch % 2) != 0){
				$box1_sqinch = $box1_sqinch + 1;
			}
			$box1_sqinch = $box1_sqinch * ($request['box1_width'] + $request['box1_depth'] +1);
			$box1_sqinch = ($box1_sqinch * 2)/100;
			$box1_sqinch = round($box1_sqinch,2);
		}

		if($request['no_of_boxes'] == 2){
			if($request['box2_type'] == "Standard Box"){
				$box2_sqinch = $request['box2_width'] + $request['box2_depth'];
				$box2_sqinch = ceil($box2_sqinch);
				if(($box2_sqinch % 2) != 0){
					$box2_sqinch = $box2_sqinch + 1;
				}
				$box2_sqinch = $box2_sqinch * ($request['box2_height'] + $request['box2_width'] +2);
				$box2_sqinch = ($box2_sqinch * 2)/100;
				$box2_sqinch = round($box2_sqinch,2);
			}
			if($request['box2_type'] == "Over Flap"){
				$box2_sqinch = $request['box2_width'] + $request['box2_width'] + $request['box2_depth'];
				$box2_sqinch = ceil($box2_sqinch);
				if(($box2_sqinch % 2) != 0){
					$box2_sqinch = $box2_sqinch + 1;
				}
				$box2_sqinch = $box2_sqinch * ($request['box2_height'] + $request['box2_width'] +2);
				$box2_sqinch = ($box2_sqinch * 2)/100;
				$box2_sqinch = round($box2_sqinch,2);
			}
			if($request['box2_type'] == "Lateral Box"){
				$box2_sqinch = $request['box2_height'] + $request['box2_width'];
				$box2_sqinch = ceil($box2_sqinch);
				if(($box2_sqinch % 2) != 0){
					$box2_sqinch = $box2_sqinch + 1;
				}
				$box2_sqinch = $box2_sqinch * ($request['box2_width'] + $request['box2_depth'] +1);
				$box2_sqinch = ($box2_sqinch * 2)/100;
				$box2_sqinch = round($box2_sqinch,2);
			}
		}

		$response['box1_sqinch'] = $box1_sqinch;
		$response['box2_sqinch'] = $box2_sqinch;
		return $response;
	}


    public function printAllocationList(Request $request,$id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails' => function($query){
                $query->where('status','po_generated');
            }])->findOrFail($id);

        $filename = "AllocationPackingList-" . $record->buyer_order_number;

        return Excel::download(new AllocationListExport($id,$record->buyer_order_number), $filename . '.xlsx');
    }
    public function printPackagingList(Request $request,$id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails' => function($query){
                $query->where('status','po_generated');
            }])->findOrFail($id);

        $filename = "PackingList-" . $record->buyer_order_number;

        return Excel::download(new PackagingListExport($record->package_list_id,$record->buyer_order_number), $filename . '.xlsx');
    }

    public function printNegativeItemList($id)
    {
        $record = ContainersAllocation::with(['containerAllocationDetails' => function($query){
                $query->where('status','po_generated');
            }])->findOrFail($id);

        $filename = "NegativeItemList-" . $record->buyer_order_number."-".date("dmy");

        return Excel::download(new NegativeItemListExport($id,$record->buyer_order_number), $filename . '.xlsx');
    }
}
