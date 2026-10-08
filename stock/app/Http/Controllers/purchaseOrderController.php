<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use \auth;
use App\Exports\PoExport;
use App\Exports\PosExport;
use App\Exports\purchaseOrderTallyExport;
use App\Exports\purchaseOrderTallySampleExport;
use App\supplier;
use App\supplierProduct;
use App\product;
use App\contractor;
use App\Challan;
use App\ChallanProduct;
use App\stockLogConsumable;
use App\UnitType;
use App\poTable;
use App\purchaseOrder;
use App\purchaseOrderConsumable;
use App\setting;
use App\SubServiceTable;
use App\Service;
use App\serviceCategories;
use App\serviceTable;
use App\Services\ServiceInvoiceQuantityService;
use App\pbTable;
use App\consumable;
use App\purchaseBill;
use App\pocTable;
use App\popTable;
use App\packaging;
use App\packagingPrice;
use App\invoice;
use App\sample;
use App\samplePurchaseOrder;
use App\posTable;
use App\purchaseOrderRecommended;
use App\porTable;
use App\pricingTable;
use App\Notification;
use App\User;
use App\DraftPurchaseOrder;
use App\DraftConsumablePurchaseOrder;
use App\Support\SendToSupplierPo;
use App\Support\PurchaseOrderVersionWriter;
use Maatwebsite\Excel\Facades\Excel;
use App\spbTable;
use App\samplePurchaseBill;
use App\ServiceProduct;
use App\ContainersAllocationDetail;

class purchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    // public function exportcsv()
    // {
    //     return Excel::download(new purchaseOrder(), 'purchaseOrder.csv');

    // }

    public function exportcsv(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new PoExport($fsd, $fed))->download('purchaseOrder.xlsx');
    }

    public function poTallyExport(Request $request)
    {
        $po_ids = trim((string) $request->input('po_ids', ''), ',');

        // If no checkboxes selected, export all POs matching the list filters
        if ($po_ids === '') {
            $search = $request->input('search');
            $date_from = $request->input('date-from');
            $date_to = $request->input('date-to');

            if (!$date_from || !$date_to) {
                return redirect('/purchaseOrder')->with('danger', 'Please set From and To dates, or select at least 1 PO.');
            }

            $po_ids = purchaseOrder::where('address_option', '!=', 100)
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('ref_supplier', 'like', "%{$search}%")
                            ->orWhere('buyer_orderno', 'like', "%{$search}%")
                            ->orWhere('pono', 'like', "%{$search}%")
                            ->orWhere('remarks', 'like', "%{$search}%")
                            ->orWhereHas('supplier', function ($q2) use ($search) {
                                $q2->where('c_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('poTable.product', function ($q3) use ($search) {
                                $q3->where('code', 'like', "%{$search}%");
                            });
                    });
                })
                ->whereBetween('podate', [$date_from, $date_to])
                ->pluck('id')
                ->implode(',');

            if ($po_ids === '') {
                return redirect('/purchaseOrder')->with('danger', 'No POs found for the selected filters.');
            }
        }

        return (new purchaseOrderTallyExport($po_ids))->download('poTallyExport.xlsx');
    }

    public function view($id)
    {

        $purchaseOrder = purchaseOrder::find($id);
        if($purchaseOrder->status != 1){
            $product=product::all();
            $supplier=supplier::where('type','!=','Consumable')->get();
            $poTable = poTable::where('poid', $id)->get();
            if ($purchaseOrder) {
                return view('purchaseOrder/view', ['purchaseOrder'=>$purchaseOrder, 'product'=>$product, 'supplier'=>$supplier, 'poTable' => $poTable]);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }

    }

    public function create(Request $request)
    {
        $supplier = supplier::where('type', '!=', 'Consumable')->get();
        $boSeries = $request->query('series') === 'bo';
        $companyDetails = setting::first();

        $nextPono = $boSeries
            ? 'BO/' . ((int) ($companyDetails->bopo_no ?? 0) + 1)
            : $this->nextFurniturePono();

        $draftPrefill = null;
        $fromDraftId = null;
        if ($request->query('from_draft')) {
            $draftPrefill = DraftPurchaseOrder::with(['lines.product'])
                ->where('id', (int) $request->query('from_draft'))
                ->where('status', DraftPurchaseOrder::STATUS_OPEN)
                ->first();
            if ($draftPrefill) {
                $fromDraftId = $draftPrefill->id;
            }
        }

        $draftPrefillLines = $draftPrefill
            ? $draftPrefill->lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'EAN' => $line->EAN,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'rate' => $line->rate,
                    'amount' => $line->amount,
                    'gstslab' => $line->gstslab,
                    'gstamount' => $line->gstamount,
                    'discount_type' => $line->discount_type,
                    'discount' => $line->discount,
                    'priority' => $line->priority,
                    'delivery_point' => $line->delivery_point,
                    'legs' => $line->legs,
                    'description' => $line->description,
                ];
            })->values()
            : collect();

        return view('purchaseOrder/create', [
            'supplier' => $supplier,
            'companyDetails' => $companyDetails,
            'nextPono' => $nextPono,
            'boSeries' => $boSeries,
            'draftPrefill' => $draftPrefill,
            'fromDraftId' => $fromDraftId,
            'draftPrefillLines' => $draftPrefillLines,
        ]);
    }

    /** Next numeric furniture PO (excludes BO/ series). */
    private function nextFurniturePono(): string
    {
        $last = purchaseOrder::query()
            ->where('supplier_id', '!=', 17)
            ->where('address_option', '!=', 100)
            ->where('podate', '>', '2021-03-31')
            ->where('pono', 'not like', 'BO/%')
            ->orderBy('id', 'desc')
            ->first();

        return (string) (($last ? (int) $last->pono : 0) + 1);
    }

    public function createservice()
    {


        $supplier = supplier::where('type', 'service')->get();
        $po = Service::where('supplier_id','!=','17')->where('address_option','!=','100')->where('podate','>','2021-03-31')->orderBy('id','desc')->first();
        return view('purchaseOrder/createService', ['supplier'=>$supplier,'po'=>$po]);
    }



    public function store(Request $request)
    {
        // Check if the 'pono' already exists in the database
        $existingOrder = purchaseOrder::where('pono', strtoupper($request['pono']))->first();

        if ($existingOrder) {
            // Return an error message if the 'pono' already exists
            return redirect()->back()->with('error', 'The Purchase Order number already exists.');
        }
        $limitErr = $this->supplierPoMonthlyLimitError(
            (int) $request['supplier_id'],
            $request['podate'],
            $request['subtotalamount'],
            'furniture'
        );
        if ($limitErr) {
            return redirect()->back()->with('error', $limitErr)->withInput();
        }

        $isWholesale = $request->boolean('is_wholesale');
        $buyerOrderno = strtoupper(trim((string) $request['buyer_orderno']));
        $wholesaleShipmentId = null;
        if ($isWholesale) {
            $wholesaleShipment = \App\WholesaleShipment::where('buyer_orderno', $buyerOrderno)->first();
            if ($wholesaleShipment) {
                $wholesaleShipmentId = $wholesaleShipment->id;
            }
        }

        $q = purchaseOrder::create(array_merge([
            'pono' => strtoupper($request['pono']),
            'supplier_id' => $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'ref_supplier' => strtoupper($request['ref_supplier']),
            'buyer_orderno' => $buyerOrderno,
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'remqty' => $request['tquantity'],
            'totaldiscount' => $request['totaldiscount'],
            'address_option' => $request['address_option'],
            'is_wholesale' => $isWholesale ? 1 : 0,
            'wholesale_shipment_id' => $wholesaleShipmentId,
        ], SendToSupplierPo::createAttributes((int) $request['address_option'], strtoupper($request['pono']), 'purchase_order')));

        foreach ($request['po'] as $po) {
            $remainingDiscount = 0;

            if ($po['discount_type'] == 'Amount') {
                $remainingDiscount = $po['discount'];
            } else {
                $remainingDiscount = ($po['amount'] * $po['discount']) / 100;
            }

            poTable::create([
                'product_id'       => $po['product'],
                'ean'              => $po['EAN'],
                'poid'             => $q->id,
                'quantity'         => $po['quantity'],
                'unit'             => $po['unit'],
                'remqty'           => $po['quantity'],
                'rate'             => $po['rate'],
                'amount'           => $po['amount'],
                'gstslab'          => $po['gstslab'],
                'gstamount'        => $po['gstamount'],
                'priority'         => $po['priority'],
                'delivery_point'   => $po['delivery_point'],
                'discount'         => $po['discount'],
                'discount_type'    => $po['discount_type'],
                'remaining_discount' => $remainingDiscount,
                'legs'             => $po['legs'],
                'description'      => $po['description']
            ]);
        }

        $this->incrementBopoCounterIfNeeded((string) $q->pono);

        if (SendToSupplierPo::isSent($q)) {
            SendToSupplierPo::notifySupplier((int) $request['supplier_id'], $q->pono);
        }

        if ($request->filled('from_draft_id')) {
            DraftPurchaseOrderController::markConverted((int) $request['from_draft_id'], (int) $q->id);
        }

        if ($isWholesale) {
            \App\WholesaleShipmentLog::record(
                $wholesaleShipmentId,
                $buyerOrderno,
                'direct_po',
                'Furniture PO ' . $q->pono . ' created from Create PO with Is Wholesale checked'
                    . ($wholesaleShipmentId ? ' and linked to existing shipment.' : ' (no matching wholesale shipment found for this buyer order number).'),
                ['purchase_order_id' => $q->id, 'pono' => $q->pono]
            );
        }

        $successMsg = 'Order was added successfully.';
        if ($isWholesale && ! $wholesaleShipmentId) {
            $successMsg .= ' Note: Is Wholesale was ticked, but no Wholesale shipment exists for buyer order ' . $buyerOrderno . '. The PO was still saved; create/upload the shipment if you need tracking.';
        }

        return redirect('/purchaseOrder')->with('success', $successMsg);
    }

    private function incrementBopoCounterIfNeeded(string $pono): void
    {
        if (! preg_match('/^BO\//i', $pono)) {
            return;
        }
        $companyDetails = setting::first();
        $companyDetails->bopo_no = (int) ($companyDetails->bopo_no ?? 0) + 1;
        $companyDetails->save();
    }

    public function sendFurniturePoToSupplier($id)
    {
        $po = purchaseOrder::findOrFail($id);
        if (SendToSupplierPo::isSent($po)) {
            return redirect()->back()->with('info', 'PO is already sent to supplier.');
        }
        if (!SendToSupplierPo::markSent($po)) {
            return redirect()->back()->with('error', 'Could not send PO to supplier.');
        }
        PurchaseOrderVersionWriter::logActivity($po, 'send', '0', '1', 'PO sent to supplier');

        return redirect()->back()->with('success', 'PO sent to supplier.');
    }

    public function sendConsumablePoToSupplier($id)
    {
        $po = purchaseOrderConsumable::findOrFail($id);
        if (SendToSupplierPo::isMonthEnd($po->address_option, $po->pono)) {
            return redirect()->back()->with('info', 'Month-end PO is already visible to supplier.');
        }
        if (SendToSupplierPo::isSent($po)) {
            return redirect()->back()->with('info', 'PO is already sent to supplier.');
        }
        if (!SendToSupplierPo::markSent($po)) {
            return redirect()->back()->with('error', 'Could not send PO to supplier.');
        }

        return redirect()->back()->with('success', 'PO sent to supplier.');
    }

    public function sendServicePoToSupplier($id)
    {
        $po = Service::findOrFail($id);
        if (SendToSupplierPo::isMonthEnd((int) $po->address_option, $po->pono)) {
            return redirect()->back()->with('info', 'Month-end PO is already visible to supplier.');
        }
        if (SendToSupplierPo::isSent($po)) {
            return redirect()->back()->with('info', 'PO is already sent to supplier.');
        }
        if (!SendToSupplierPo::markSent($po)) {
            return redirect()->back()->with('error', 'Could not send PO to supplier.');
        }

        return redirect()->back()->with('success', 'PO sent to supplier.');
    }

    public function sendSamplePoToSupplier($id)
    {
        $po = samplePurchaseOrder::findOrFail($id);
        if (SendToSupplierPo::isSent($po)) {
            return redirect()->back()->with('info', 'PO is already sent to supplier.');
        }
        if (!SendToSupplierPo::markSent($po)) {
            return redirect()->back()->with('error', 'Could not send PO to supplier.');
        }

        return redirect()->back()->with('success', 'PO sent to supplier.');
    }

    /**
     * AJAX: supplier PO monthly limit status for furniture/consumable PO forms (carton exempt).
     */
    public function supplierPoLimitStatus(Request $request)
    {
        try {
            $status = $this->buildSupplierPoLimitStatus(
                (int) $request->input('supplier_id'),
                $request->input('podate'),
                (float) $request->input('amount', 0),
                (string) $request->input('po_kind', 'furniture'),
                $request->input('exclude_furniture_po_id') ? (int) $request->input('exclude_furniture_po_id') : null,
                $request->input('exclude_consumable_po_id') ? (int) $request->input('exclude_consumable_po_id') : null
            );

            return response()->json($status);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'PO limit check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enforces supplier monthly PO cap. is_merged uses merged columns only; else track by PO kind.
     * No limit or no start date => no enforcement. Carton PO must not call this.
     *
     * @param string $poKind furniture|consumable
     * @return string|null Error message if exceeded; null if allowed or limit not configured
     */
    private function supplierPoMonthlyLimitError(
        $supplierId,
        $podate,
        $additionalAmount,
        string $poKind,
        $excludePurchaseOrderId = null,
        $excludeConsumablePoId = null
    ) {
        $status = $this->buildSupplierPoLimitStatus(
            (int) $supplierId,
            $podate,
            (float) $additionalAmount,
            $poKind,
            $excludePurchaseOrderId ? (int) $excludePurchaseOrderId : null,
            $excludeConsumablePoId ? (int) $excludeConsumablePoId : null
        );

        if (!$status['configured'] || $status['level'] !== 'blocked') {
            return null;
        }

        return $status['message'];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSupplierPoLimitStatus(
        int $supplierId,
        $podate,
        float $additionalAmount,
        string $poKind,
        ?int $excludePurchaseOrderId = null,
        ?int $excludeConsumablePoId = null
    ): array {
        $notConfigured = [
            'configured' => false,
            'level' => 'ok',
            'percent_after' => 0,
            'message' => '',
        ];

        $supplier = supplier::find($supplierId);
        if (!$supplier || empty($podate)) {
            return $notConfigured;
        }

        $isMerged = (int) ($supplier->is_merged ?? 0) === 1;
        if ($isMerged) {
            $limitVal = $supplier->po_monthly_limit_merged;
            $startDateVal = $supplier->po_limit_start_date_merged;
            $label = 'Merged (Furniture + Consumable)';
            $track = 'merged';
        } elseif ($poKind === 'furniture') {
            $limitVal = $supplier->po_monthly_limit_furniture;
            $startDateVal = $supplier->po_limit_start_date_furniture;
            $label = 'Furniture';
            $track = 'furniture';
        } elseif ($poKind === 'consumable') {
            $limitVal = $supplier->po_monthly_limit_consumable;
            $startDateVal = $supplier->po_limit_start_date_consumable;
            $label = 'Consumable';
            $track = 'consumable';
        } else {
            return $notConfigured;
        }

        if ($limitVal === null || (float) $limitVal <= 0 || empty($startDateVal)) {
            return $notConfigured;
        }

        $limitStart = \Carbon\Carbon::parse($startDateVal)->startOfDay();
        $podateCarbon = \Carbon\Carbon::parse($podate);
        $limitYm = $limitStart->format('Y-m');
        $poYm = $podateCarbon->format('Y-m');

        if ($poYm < $limitYm) {
            return $notConfigured;
        }

        $windowEnd = $podateCarbon->copy()->endOfMonth();
        $windowStart = $poYm === $limitYm
            ? $limitStart->copy()
            : $podateCarbon->copy()->startOfMonth();

        if ($podateCarbon->lt($windowStart)) {
            return $notConfigured;
        }

        $dateFrom = $windowStart->toDateString();
        $dateTo = $windowEnd->toDateString();

        if ($track === 'merged') {
            $used = $this->sumFurniturePoAmountForLimit($supplierId, $dateFrom, $dateTo, $excludePurchaseOrderId)
                + $this->sumConsumablePoAmountForLimit($supplierId, $dateFrom, $dateTo, $excludeConsumablePoId);
        } elseif ($track === 'furniture') {
            $used = $this->sumFurniturePoAmountForLimit($supplierId, $dateFrom, $dateTo, $excludePurchaseOrderId);
        } else {
            $used = $this->sumConsumablePoAmountForLimit($supplierId, $dateFrom, $dateTo, $excludeConsumablePoId);
        }

        $limit = (float) $limitVal;
        $totalAfter = $used + $additionalAmount;
        $percentAfter = $limit > 0 ? ($totalAfter / $limit) * 100 : 0;

        $level = 'ok';
        if ($totalAfter > $limit) {
            $level = 'blocked';
        } elseif ($percentAfter >= 75) {
            $level = 'warning';
        }

        $message = sprintf(
            '%s monthly PO limit: %s%% used after this PO (limit %s | used %s | this PO %s | window %s to %s).',
            $label,
            number_format($percentAfter, 1),
            number_format($limit, 2),
            number_format($used, 2),
            number_format($additionalAmount, 2),
            $windowStart->toDateString(),
            $windowEnd->toDateString()
        );

        if ($level === 'blocked') {
            $message = sprintf(
                '%s monthly PO limit exceeded. Limit: %s | POs used (from %s to %s): %s | This PO: %s',
                $label,
                number_format($limit, 2),
                $windowStart->toDateString(),
                $windowEnd->toDateString(),
                number_format($used, 2),
                number_format($additionalAmount, 2)
            );
        }

        return [
            'configured' => true,
            'level' => $level,
            'track' => $track,
            'label' => $label,
            'limit' => $limit,
            'used' => $used,
            'this_po' => $additionalAmount,
            'total_after' => $totalAfter,
            'percent_after' => round($percentAfter, 2),
            'message' => $message,
        ];
    }

    private function sumFurniturePoAmountForLimit(
        int $supplierId,
        string $dateFrom,
        string $dateTo,
        ?int $excludePurchaseOrderId = null
    ): float {
        return (float) purchaseOrder::where('supplier_id', $supplierId)
            ->whereBetween('podate', [$dateFrom, $dateTo])
            ->where('status', '!=', 2)
            ->when($excludePurchaseOrderId, function ($q) use ($excludePurchaseOrderId) {
                $q->where('id', '!=', $excludePurchaseOrderId);
            })
            ->sum('subTotal');
    }

    private function sumConsumablePoAmountForLimit(
        int $supplierId,
        string $dateFrom,
        string $dateTo,
        ?int $excludeConsumablePoId = null
    ): float {
        return (float) purchaseOrderConsumable::where('supplier_id', $supplierId)
            ->whereBetween('podate', [$dateFrom, $dateTo])
            ->where('status', '!=', 2)
            ->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 2);
            })
            ->where(function ($q) {
                $q->whereNull('address_option')->orWhere('address_option', '!=', 100);
            })
            ->when($excludeConsumablePoId, function ($q) use ($excludeConsumablePoId) {
                $q->where('id', '!=', $excludeConsumablePoId);
            })
            ->sum('subTotal');
    }


    public function index(Request $request)
    {
        $search = $request->input('search');
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        $purchaseOrders = purchaseOrder::with('supplier', 'poTable.product')
                        ->where('address_option','!=',100)
                        ->when($search, function ($query, $search) {
                            $query->where(function ($q) use ($search) {
                                $q->where('ref_supplier', 'like', "%{$search}%")
                                ->orWhere('buyer_orderno', 'like', "%{$search}%")
                                ->orWhere('pono', 'like', "%{$search}%")
                                ->orWhere('remarks', 'like', "%{$search}%")
                                ->orWhereHas('supplier', function ($q2) use ($search) {
                                    $q2->where('c_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('poTable.product', function ($q3) use ($search) {
                                    $q3->where('code', 'like', "%{$search}%");
                                });
                            });
                        })
                        ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                            $query->whereBetween('podate', [$date_from, $date_to]);
                        })

                        ->orderBy('id', 'desc')
                        ->paginate(50);

        $poIds = $purchaseOrders->pluck('id');
        $latestVersions = PurchaseOrderVersionWriter::latestVersionMap($poIds);
        $pendingVersions = PurchaseOrderVersionWriter::pendingVersionCountMap($poIds);

        return view('purchaseOrder/index', [
            'purchaseOrders' => $purchaseOrders,
            'latestVersions' => $latestVersions,
            'pendingVersions' => $pendingVersions,
        ]);
    }

    public function updatepo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrder::where('id', $id)->first();
        $poProduct = poTable::where('poid', $id)->get();

        if ($purchaseOrder && $purchaseOrder->status != 1) {
            $limitErr = $this->supplierPoMonthlyLimitError(
                (int) $request['supplier_id'],
                $request['podate'],
                $request['subtotalamount'],
                'furniture',
                (int) $id,
                null
            );
            if ($limitErr) {
                return redirect()->back()->with('error', $limitErr)->withInput();
            }

            $beforeSnapshot = PurchaseOrderVersionWriter::buildSnapshot($purchaseOrder);

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
            $purchaseOrder->address_option = $request['address_option'];
            $purchaseOrder->totaldiscount = $request['totaldiscount'];
            if ($request['po_revise_date'] != '') {
                $purchaseOrder->po_revise_date = $request['po_revise_date'];
            } else {
                $purchaseOrder->po_revise_date = date('Y-m-d');
            }

            if (isset($poProduct)) {
                foreach ($poProduct as $poProduct) {
                    $poProduct->delete();
                }
            }

            foreach ($request['po'] as $po) {
                //return($po['consumed']);
                if ($po['consumed'] == '') {
                       $remainingDiscount = 0;

            if ($po['discount_type'] == 'Amount') {
                $remainingDiscount = $po['discount'];
            } else {
                $remainingDiscount = ($po['amount'] * $po['discount']) / 100;
            }


                    poTable::create([
                        'product_id' => $po['product'],
                        'ean' => $po['EAN'],
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
                        'discount_type' => $po['discount_type'],
                        'discount' => $po['discount'],
                        'remaining_discount' => $remainingDiscount,
                        'description' => $po['description']
                    ]);
                } else {
                           $remainingDiscount = 0;

            if ($po['discount_type'] == 'Amount') {
                $remainingDiscount = $po['discount'];
            } else {
                $remainingDiscount = ($po['amount'] * $po['discount']) / 100;
            }

                    poTable::create([
                        'product_id' => $po['product'],
                        'ean' => $po['EAN'],
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
                        'discount_type' => $po['discount_type'],
                        'discount' => $po['discount'],
                        'remaining_discount' => $remainingDiscount,
                        'description' => $po['description']
                    ]);
                }
            }

            if ($purchaseOrder->save()) {
                $version = PurchaseOrderVersionWriter::recordUpdate($purchaseOrder, $beforeSnapshot);
                $msg = 'PurchaseOrder was updated successfully.';
                if ($version) {
                    $msg .= ' Version v'.$version->version.' recorded (supplier must accept before raise invoice).';
                }

                return redirect('/purchaseOrder')->with('success', $msg);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'Error occurred while saving PurchaseOrder.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PurchaseOrder can not be edited.');
        }
    }

    public function data()
    {
        $product=product::all();
        foreach($product as $k=>$prod){
            $pricing = pricingTable::where('product_id',$prod->id)->where('buyer_id2',3)->orderBy('created_at','desc')->first();
            if(empty($pricing)){
                $pricing = pricingTable::where('product_id',$prod->id)->orderBy('created_at','desc')->first();
                if(empty($pricing)){
                    $pricing    =       0;
                }else{
                    $product[$k]['fob_pricing'] = $pricing->fobINCost;
                }
            }else{
                $product[$k]['fob_pricing'] = $pricing->fobINCost;
            }
        }
        return response()->json(['product'=>$product]);
    }

    public function spdata(Request $request)
    {

        $su=supplierProduct::where('supplier_id',$request['supplier_id'])->where('product_id',$request['product_id'])->orderBy('id','desc')->first();
        if(!isset($su->id)){
            $su['rate'] = 0;
        }
        return response()->json(['sp'=>$su]);
    }

        public function contractor_data()
    {
        $contractor=contractor::all();
        return response()->json(['contractor'=>$contractor]);
    }

    public function update_status(Request $request, $id){
        $purchaseOrder = purchaseOrder::where('id', $id)->first();

        if (isset($purchaseOrder->id)) {
            $from = (string) $purchaseOrder->status;
            $purchaseOrder->status = $request->status;
            $purchaseOrder->save();
            PurchaseOrderVersionWriter::logActivity(
                $purchaseOrder,
                'status',
                (string) $from,
                (string) $request->status,
                'Status changed from '
                    .PurchaseOrderVersionWriter::poStatusLabel($from)
                    .' to '
                    .PurchaseOrderVersionWriter::poStatusLabel($request->status)
            );
            return redirect('/purchaseOrder')->with('success', 'Purchase Order status updated successfully.');
        }else{
            return redirect('/purchaseOrder')->with('danger', 'Purchase Order was not found.');
        }
    }

    public function cancel(Request $request, $id)
    {
        $purchaseOrder = purchaseOrder::where('id', $id)->first();

        // Check if the purchase order exists
        if (!$purchaseOrder) {
            return redirect('/purchaseOrder')->with('danger', 'Purchase Order not found.');
        }

        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if ($poRelationCount > 0) {
            return redirect('/purchaseOrder')->with('danger', 'Purchase Order cannot be canceled. It exists in other relations.');
        }

        $from = (string) $purchaseOrder->status;
        $purchaseOrder->status = 2;

        if ($purchaseOrder->save()) {
            PurchaseOrderVersionWriter::logActivity($purchaseOrder, 'cancel', $from, '2', 'PO canceled');
            return redirect('/purchaseOrder')->with('success', 'Purchase Order canceled successfully.');
        } else {
            return redirect('/purchaseOrder')->with('danger', 'There was an error canceling the Purchase Order.');
        }
    }

    public function poVersionHistory($id)
    {
        $purchaseOrder = purchaseOrder::with('supplier')->findOrFail($id);
        $versions = collect();
        $activities = collect();
        $tableMissing = ! PurchaseOrderVersionWriter::versionsTableExists();

        if (! $tableMissing) {
            $versions = \App\PurchaseOrderVersion::where('purchase_order_id', $id)
                ->orderByDesc('version')
                ->get();
        }
        if (PurchaseOrderVersionWriter::activityTableExists()) {
            $activities = \App\PurchaseOrderActivityLog::where('purchase_order_id', $id)
                ->orderByDesc('id')
                ->get();
        }

        return view('purchaseOrder.version_history', [
            'purchaseOrder' => $purchaseOrder,
            'versions' => $versions,
            'activities' => $activities,
            'tableMissing' => $tableMissing,
        ]);
    }


    public function destroy(Request $request, $id)
    {
        try {
            $purchaseOrder = purchaseOrder::where('id', $id)->first();
            // Check if the purchase order exists
            if (!$purchaseOrder) {
                return redirect('/purchaseOrder')->with('danger', 'Purchase Order not found.');
            }

            $purchaseOrder->delete();
            // Update all records with purchase order
            ContainersAllocationDetail::where('purchase_order_id',$id)->update(['status'=>'pending','purchase_order_id'=>null,'po_consumable_id'=>null]);
            return redirect()->back()->with('success', 'Purchase Order deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete Purchase Order: ' . $e->getMessage());
        }
    }




    public function cancelConsumablePo(Request $request, $id)
    {
         $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();

        // Check if the purchase order exists
        if (!$purchaseOrder) {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'Purchase  Order  Consumable not found.');
        }

        $purchaseOrder->status = 2;

        if ($purchaseOrder->save()) {
            return redirect('/purchaseOrder/consumablePos')->with('success', 'Purchase Order Consumable canceled successfully.');
        } else {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'There was an error canceling the Purchase Order Consumable.');
        }
    }

    // public function delete(Request $request, $id)
    // {
    //     $purchaseOrder = purchaseOrder::where('id', $id)->first();
    //     $poRelationCount = $purchaseOrder->purchaseBill->count();
    //     if($poRelationCount > 0)
    //     {
    //         return redirect('/purchaseOrder')->with('danger', 'Purchase Order cannot be deleted. Purchase Order exist in other relations.');
    //     }
    //     else
    //     {
    //         $poTable = poTable::where('poid', $id)->get();

    //         if ($poTable) {
    //             foreach ($poTable as $key => $value) {
    //                 $poTable[$key]->delete();
    //             }
    //         }

    //         if ($purchaseOrder) {
    //             if ($purchaseOrder->delete()) {
    //                 return redirect('/purchaseOrder')->with('success', 'Purchase Order deleted successfully.');
    //             } else {
    //                 return redirect('/purchaseOrder')->with('danger', 'Purchase Order was not found.');
    //             }
    //         }

    //     }
    // }

    public function modal($id)
    {
        $purchaseOrder = purchaseOrder::find($id)->where('id',$id)->first();
        $companyDetails = setting::first();
        $poTable = poTable::where('poid',$id)->get();
        $files = Storage::disk('s3')->files('stock/product');

        // Create a map: ['filename.jpg' => 'full-url']
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }

        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modal', ['purchaseOrder'=>$purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable,'fileMap'=>$fileMap,'fileMap1'=>$fileMap1]);
    }




        //Consumables Purchase Order functions


        public function viewConsumablePo($id)
    {

        $purchaseOrder = purchaseOrderConsumable::find($id);
        if($purchaseOrder->status != 1){
            $product=consumable::all();
            $supplier=supplier::where('type','!=','Furniture')->get();
            $poTable = pocTable::where('poid', $id)->get();
            if ($purchaseOrder) {
                return view('purchaseOrder/viewConsumablePo', ['purchaseOrder'=>$purchaseOrder, 'product'=>$product, 'supplier'=>$supplier, 'poTable' => $poTable]);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }

    }

        public function createConsumablePo(Request $request)
    {
        $supplier=supplier::where('type','!=','Furniture')->get();
                $po = purchaseOrderConsumable::orderBy('id','desc')->first();
                $companyDetails = setting::first();

        $draftPrefill = null;
        $fromDraftId = null;
        if ($request->query('from_draft')) {
            $draftPrefill = DraftConsumablePurchaseOrder::with(['lines.consumable'])
                ->where('id', (int) $request->query('from_draft'))
                ->where('status', DraftConsumablePurchaseOrder::STATUS_OPEN)
                ->first();
            if ($draftPrefill) {
                $fromDraftId = $draftPrefill->id;
            }
        }

        $draftPrefillLines = $draftPrefill
            ? $draftPrefill->lines->map(function ($line) {
                return [
                    'consumable_id' => $line->consumable_id,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'rate' => $line->rate,
                    'amount' => $line->amount,
                    'gstslab' => $line->gstslab,
                    'gstamount' => $line->gstamount,
                    'description' => $line->description,
                ];
            })->values()
            : collect();

        return view('purchaseOrder/createConsumablePo', [
            'supplier'=>$supplier,
            'po'=>$po,
            'companyDetails'=>$companyDetails,
            'draftPrefill' => $draftPrefill,
            'fromDraftId' => $fromDraftId,
            'draftPrefillLines' => $draftPrefillLines,
        ]);
    }

    public function storeConsumablePo(Request $request)
    {
        // Check if the provided 'pono' already exists in the database
    $existingPo = purchaseOrderConsumable::where('pono', strtoupper($request['pono']))->first();
foreach ($request['po'] as $key => $po) {
    if (!isset($po['unit']) || $po['unit'] == '' || $po['unit'] == null) {

        return back()->with('error', 'Unit is required for all products. Missing at row ');
    }
}

    $moqErr = $this->validateConsumablePoMoqRows($request->input('po', []));
    if ($moqErr) {
        return back()->with('error', $moqErr)->withInput();
    }

    if ($existingPo) {
        // If it exists, redirect back with an error message
        return back()->with('error', 'The purchase order number (PONo) already exists.');
    }
    $limitErr = $this->supplierPoMonthlyLimitError(
        (int) $request['supplier_id'],
        $request['podate'],
        $request['subtotalamount'],
        'consumable'
    );
    if ($limitErr) {
        return back()->with('error', $limitErr)->withInput();
    }
                        //echo '<pre>';print_r($_REQUEST);die;
                        $q = purchaseOrderConsumable::create(array_merge([
                    'pono'=>strtoupper($request['pono']),
                    'supplier_id'=>$request['supplier_id'],
                    'podate'=>$request['podate'],
                    'del_date'=>$request['del_date'],
                    'month'=>implode(',',$request['month']),
                    'buyer_orderno'=>strtoupper($request['buyer_orderno']),
                    'payterms'=>$request['payterms'],
                    'remarks'=>$request['remarks'],
                    'subTotal'=>$request['subtotalamount'],
                    'tgst'=>$request['tgst'],
                    'tquantity'=>$request['tquantity'],
                    'tamount'=>$request['tamount'],
                    'remqty'=>$request['tquantity'],
                    'type'=>1,
                    'address_option'=>$request['address_option'],
                ], SendToSupplierPo::createAttributes((int) $request['address_option'], strtoupper($request['pono']), 'purchase_order_consumables')));

                foreach ($request['po'] as $po) {
                    pocTable::create([
                        'consumable_id' => $po['consumable'],
                        'poid' => $q->id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => $po['quantity'],
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'description' => $po['description']
                    ]);
                }

                                $companyDetails                 = setting::first();
                                $companyDetails->cpo_no = $companyDetails->cpo_no + 1;
                                $companyDetails->save();

        if ($request->filled('from_draft_id')) {
            DraftConsumablePurchaseOrderController::markConverted((int) $request['from_draft_id'], (int) $q->id);
        }

           return redirect('/purchaseOrder/consumablePos')->with('success', 'Order was added successfully.');
    }

    public function consumablePos(Request $request)
    {

        $search = $request->input('search');
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

         $purchaseOrders = purchaseOrderConsumable::with('supplier', 'poTable.consumable', 'purchaseBill', 'purchaseBillCarton')
            ->where(function ($q) {
                $q->where('address_option', '!=', 100)
                    ->orWhereNull('address_option');
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ref_supplier', 'like', "%{$search}%")
                        ->orWhere('buyer_orderno', 'like', "%{$search}%")
                        ->orWhere('pono', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q2) use ($search) {
                            $q2->where('c_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('poTable.consumable', function ($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('podate', [$date_from, $date_to]);
            })

            ->orderBy('id', 'desc')
            ->paginate(50)
            ->appends(['search' => $search]);

        // $purchaseOrder=purchaseOrderConsumable::latest()->get();

        return view('purchaseOrder/consumablePos', ['purchaseOrders' => $purchaseOrders]);
    }

    public function updateConsumablePo(Request $request, $id)
    {

         foreach ($request['po'] as $key => $po) {
        if (!isset($po['unit']) || $po['unit'] == '' || $po['unit'] == null) {

            $rowNumber = $key + 1;

            return back()->with('error', 'Unit is required for all products. Missing at row ')
                         ->withInput();
        }
    }

        $moqErr = $this->validateConsumablePoMoqRows($request->input('po', []));
        if ($moqErr) {
            return back()->with('error', $moqErr)->withInput();
        }

        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $poProduct = pocTable::where('poid', $id)->get();

        if ($purchaseOrder && $purchaseOrder->status!=1) {
             $limitErr = $this->supplierPoMonthlyLimitError(
                 (int) $request['supplier_id'],
                 $request['podate'],
                 $request['subtotalamount'],
                 'consumable',
                 null,
                 (int) $id
             );
             if ($limitErr) {
                 return redirect()->back()->with('error', $limitErr)->withInput();
             }
             $purchaseOrder->pono=$request['pono'];
             $purchaseOrder->supplier_id=$request['supplier_id'];
             $purchaseOrder->podate=$request['podate'];
             $purchaseOrder->del_date=$request['del_date'];
             $purchaseOrder->month=implode(',',$request['month']);
             $purchaseOrder->buyer_orderno=strtoupper($request['buyer_orderno']);
             $purchaseOrder->payterms=$request['payterms'];
             $purchaseOrder->remarks=$request['remarks'];
             $purchaseOrder->subTotal=$request['subtotalamount'];
             $purchaseOrder->tgst=$request['tgst'];
             $purchaseOrder->tquantity=$request['tquantity'];
             $purchaseOrder->remqty=$request['tquantity'];
             $purchaseOrder->tamount=$request['tamount'];
             $purchaseOrder->address_option=$request['address_option'];
             if($request['po_revise_date'] != ''){
                $purchaseOrder->po_revise_date = $request['po_revise_date'];
             }else{
                $purchaseOrder->po_revise_date = date('Y-m-d');
             }

             if(isset($poProduct)){
                foreach ($poProduct as $poProduct) {
                    $poProduct->delete();
                }
             }

             foreach ($request['po'] as $po) {
               $consumed = isset($po['consumed']) && $po['consumed'] !== '' ? (float) $po['consumed'] : 0.0;
               if($consumed == 0.0){
                    pocTable::create([
                        'consumable_id' => $po['consumable'],
                        'poid' => $id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => $po['quantity'],
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'description' => $po['description']
                    ]);
                }

                else{
                    pocTable::create([
                        'consumable_id' => $po['consumable'],
                        'poid' => $id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => (float) $po['quantity'] - $consumed,
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'description' => $po['description']
                    ]);
                }
            }

            if ($purchaseOrder->save()) {
                return redirect('/purchaseOrder/consumablePos')->with('success', 'PurchaseOrder was updated successfully.');
            } else {
                return redirect('/purchaseOrder/consumablePos')->with('danger', 'Error occurred while saving PurchaseOrder.');
            }
        } else {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'PurchaseOrder can not be edited.');
        }
    }

         public function cdata()
    {
        $product=consumable::where('is_deleted',0)->get();
        return response()->json(['product'=>$product]);
    }

       public function cunitType()
    {
        $unittype = UnitType::all();
        return response()->json(['unittype' => $unittype]);
    }

    /**
     * Enforce MOQ rules for consumable PO rows:
     * - if is_moq is enabled then quantity must be >= moq_qty
     * - and quantity must be a multiple of moq_qty
     * - if MOQ is enabled but moq_qty is empty/invalid, block with config error
     *
     * @param array<int|string, mixed> $rows
     */
    protected function validateConsumablePoMoqRows(array $rows): ?string
    {
        if (empty($rows)) {
            return null;
        }

        foreach ($rows as $index => $po) {
            if (!is_array($po)) {
                continue;
            }

            $rowNumber = (int) $index;
            $consumableId = (int) ($po['consumable'] ?? 0);
            $qtyRaw = $po['quantity'] ?? 0;
            $quantity = is_numeric($qtyRaw) ? (float) $qtyRaw : 0.0;

            if ($consumableId <= 0) {
                continue;
            }

            $consumable = consumable::select('id', 'name', 'is_moq', 'moq_qty')->find($consumableId);
            if (!$consumable) {
                return "Invalid consumable selected at row {$rowNumber}.";
            }

            $isMoq = (int) ($consumable->is_moq ?? 0) === 1;
            if (!$isMoq) {
                continue;
            }

            $moqQty = is_numeric($consumable->moq_qty) ? (float) $consumable->moq_qty : 0.0;
            if ($moqQty <= 0) {
                return "Row {$rowNumber} ({$consumable->name}): MOQ is enabled but MOQ Qty is missing/invalid on consumable master.";
            }

            if ($quantity + 1e-9 < $moqQty) {
                return "Row {$rowNumber} ({$consumable->name}): quantity must be at least {$moqQty}.";
            }

            $ratio = $quantity / $moqQty;
            if (abs($ratio - round($ratio)) > 1e-6) {
                return "Row {$rowNumber} ({$consumable->name}): quantity must be in multiples of {$moqQty} (e.g. {$moqQty}, " . ($moqQty * 2) . ", " . ($moqQty * 3) . ").";
            }
        }

        return null;
    }


    public function deleteConsumablePo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBillConsumables()->count();
        if($poRelationCount > 0)
        {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'Purchase Order cannot be deleted. Purchase Order exist in other relations ( In Purchase Bill ).');
        }
        else
        {
            $poTable = pocTable::where('poid', $id)->get();

            if ($poTable) {
                foreach ($poTable as $key => $value) {
                    $poTable[$key]->delete();
                }
            }

            if ($purchaseOrder) {
                if ($purchaseOrder->delete())
                {
                    popTable::where('poid',$id)->delete();
                    ContainersAllocationDetail::where('po_consumable_id',$id)->update(['po_consumable_id'=>""]);

                    return redirect('/purchaseOrder/consumablePos')->with('success', 'Purchase Order deleted successfully.');
                } else {
                    return redirect('/purchaseOrder/consumablePos')->with('danger', 'Purchase Order was not found.');
                }
            }

        }
    }

    public function modalConsumablePo($id)
    {
        $purchaseOrder = purchaseOrderConsumable::find($id)->where('id',$id)->first();
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $poTable = pocTable::where('poid',$id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modalConsumablePo', ['purchaseOrder'=>$purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable,'fileMap1'=>$fileMap1]);
    }

        //Cartons Purchase Order functions


        public function viewCartonPo($id)
    {

        $purchaseOrder = purchaseOrderConsumable::find($id);
        if($purchaseOrder->status != 1){
            $product=consumable::all();
            $supplier=supplier::where('type','!=','Furniture')->where('do_packaging','Yes')->get();
            $poTable = popTable::where('poid', $id)->get();
                        $companyDetails = setting::first();
                        $pricingDetails = packagingPrice::get();
            if ($purchaseOrder) {
                return view('purchaseOrder/viewCartonPo', ['purchaseOrder'=>$purchaseOrder, 'product'=>$product, 'supplier'=>$supplier, 'poTable' => $poTable,'companyDetails'=>$companyDetails,'pricingDetails'=>$pricingDetails]);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }

    }

        public function createCartonPo()
    {
        $supplier=supplier::where('type','!=','Furniture')->where('do_packaging','Yes')->get();
                $po = purchaseOrderConsumable::where('type', 2)->orderBy('id','desc')->first();
                $companyDetails = setting::first();
                $pricingDetails = packagingPrice::get();
        return view('purchaseOrder/createCartonPo', ['supplier'=>$supplier,'po'=>$po,'companyDetails'=>$companyDetails,'pricingDetails'=>$pricingDetails]);
    }

    public function storeCartonPo(Request $request)
    {
            $request->validate([
                'po' => 'required|array|min:1',
                'po.*.product' => 'required',
                'po.*.quantity' => 'required|numeric|min:0',
                'po.*.box2_qty' => 'nullable|numeric|min:0',
            ]);

            $existingPo = purchaseOrderConsumable::where('pono', strtoupper($request['pono']))->first();

            if ($existingPo) {
                // If it exists, redirect back with an error message
                return back()->with('error', 'The purchase order number (PONo) already exists.');
            }
                        //echo '<pre>';print_r($_REQUEST);die;
                        $q = purchaseOrderConsumable::create(array_merge([
                    'pono'=>strtoupper($request['pono']),
                    'supplier_id'=>$request['supplier_id'],
                    'podate'=>$request['podate'],
                    'del_date'=>$request['del_date'],
                    'month'=>implode(',',$request['month']),
                    'buyer_orderno'=>strtoupper($request['buyer_orderno']),
                    'payterms'=>$request['payterms'],
                    'remarks'=>$request['remarks'],
                    'subTotal'=>$request['subtotalamount'],
                    'tgst'=>$request['tgst'],
                    'tquantity'=>$request['tquantity'],
                    'tamount'=>$request['tamount'],
                    'remqty'=>$request['tquantity'],
                    'address_option'=>111,
                    'type'=>2,
                ], SendToSupplierPo::createAttributes(111, strtoupper($request['pono']), 'purchase_order_consumables')));

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
                        'box2_qty' => $po['box2_qty'] ?? 0,
                        'remqty_box1' => $po['quantity'],
                        'remqty_box2' => $po['box2_qty'] ?? 0,
                    ]);
                }

                                $companyDetails                 = setting::first();
                                $companyDetails->ctnpo_no = $companyDetails->ctnpo_no + 1;
                                $companyDetails->save();

           return redirect('/purchaseOrder/consumablePos')->with('success', 'Order was added successfully.');
    }

    public function cartonPos()
    {
        return redirect('/purchaseOrder/consumablePos');
    }

    public function updateCartonPo(Request $request, $id)
    {
        $request->validate([
            'po' => 'required|array|min:1',
            'po.*.product' => 'required',
            'po.*.quantity' => 'required|numeric|min:0',
            'po.*.box2_qty' => 'nullable|numeric|min:0',
        ]);

        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $poProduct = popTable::where('poid', $id)->get();

        if ($purchaseOrder && $purchaseOrder->status!=1) {
             $purchaseOrder->pono=$request['pono'];
             $purchaseOrder->supplier_id=$request['supplier_id'];
             $purchaseOrder->podate=$request['podate'];
             $purchaseOrder->del_date=$request['del_date'];
             $purchaseOrder->month=implode(',',$request['month']);
             $purchaseOrder->buyer_orderno=strtoupper($request['buyer_orderno']);
             $purchaseOrder->payterms=$request['payterms'];
             $purchaseOrder->remarks=$request['remarks'];
             $purchaseOrder->subTotal=$request['subtotalamount'];
             $purchaseOrder->tgst=$request['tgst'];
             $purchaseOrder->tquantity=$request['tquantity'];
             $purchaseOrder->remqty=$request['tquantity'];
             $purchaseOrder->tamount=$request['tamount'];

             if(isset($poProduct)){
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
                                        'amount' => isset($po['amount']) ? $po['amount'] : 0,
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
                    'remqty_box1' => $po['quantity'],
                    'remqty_box2' => $po['box2_qty'],
                                        'box2_qty' => $po['box2_qty']
                                ]);
            }

            if ($purchaseOrder->save()) {
                return redirect('/purchaseOrder/consumablePos')->with('success', 'PurchaseOrder was updated successfully.');
            } else {
                return redirect('/purchaseOrder/consumablePos')->with('danger', 'Error occurred while saving PurchaseOrder.');
            }
        } else {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'PurchaseOrder can not be edited.');
        }
    }

    public function deleteCartonPo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if($poRelationCount > 0)
        {
            return redirect('/purchaseOrder/consumablePos')->with('danger', 'Purchase Order cannot be deleted. Purchase Order exist in other relations.');
        }
        else
        {
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
    $packaging = Packaging::whereHas('product')  // only packaging with valid product
        ->with('product')                        // eager load related product
        ->get();

    return response()->json(['packaging' => $packaging]);
}

    public function modalCartonPo($id)
    {
        $purchaseOrder = purchaseOrderConsumable::find($id)->where('id',$id)->first();
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $poTable = popTable::where('poid',$id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modalCartonPo', ['purchaseOrder'=>$purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable,'fileMap1'=>$fileMap1]);
    }

        public function calculate_box(Request $request){
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
                return response()->json($response);
        }

    public function samplePurchaseOrder()
    {
        $samplePurchaseOrder=samplePurchaseOrder::orderBy('id','desc')->get();
        return view('purchaseOrder/samplePurchaseOrder', ['samplePurchaseOrder'=>$samplePurchaseOrder]);
    }

    public function createSamplePo()
    {
        $supplier=supplier::where('type','!=','Consumable')->get();
        $po = purchaseOrder::where('supplier_id','!=','17')->orderBy('id','desc')->first();
        $companyDetails = setting::first();
        return view('purchaseOrder/createSamplePo', ['supplier'=>$supplier,'po'=>$po,'companyDetails'=>$companyDetails]);


    }

    public function sampleData()
    {
        $sample=sample::all();
        return response()->json(['sample'=>$sample]);
    }

     public function storeSample(Request $request)
    {

        $existingPo = samplePurchaseOrder::where('pono', strtoupper($request['pono']))->first();

    if ($existingPo) {

        return back()->with('error', 'The purchase order number (PONo) already exists.');
    }

           $q = samplePurchaseOrder::create(array_merge([
                    'pono'=>strtoupper($request['pono']),
                    'supplier_id'=>$request['supplier_id'],
                    'podate'=>$request['podate'],
                    'del_date'=>$request['del_date'],
                    'ref_supplier'=>strtoupper($request['ref_supplier']),
                    'buyer_orderno'=>strtoupper($request['buyer_orderno']),
                    'payterms'=>$request['payterms'],
                    'remarks'=>$request['remarks'],
                    'subTotal'=>$request['subtotalamount'],
                    'tgst'=>$request['tgst'],
                    'tquantity'=>$request['tquantity'],
                    'tamount'=>$request['tamount'],
                    'remqty'=>$request['tquantity']
                ], SendToSupplierPo::createAttributes(null, strtoupper($request['pono']), 'sample_purchase_order')));
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
                        'ean' =>''
                    ]);
                }
            $companyDetails = setting::first();
            $companyDetails->spo_no = $companyDetails->spo_no + 1;
            $companyDetails->save();

        if (SendToSupplierPo::isSent($q)) {
            SendToSupplierPo::notifySupplier((int) $request['supplier_id'], $q->pono);
        }

           return redirect('/samplePurchaseOrder')->with('success', 'Order was added successfully.');
    }

    public function viewSample($id)
    {

        $purchaseOrder = samplePurchaseOrder::find($id);
        //if($purchaseOrder->status != 1){
            $product=product::all();
            $sample=sample::all();
            $supplier=supplier::where('type','!=','Consumable')->get();
            $posTable = posTable::where('poid', $id)->get();
            if ($purchaseOrder) {
                return view('purchaseOrder/viewSample', ['purchaseOrder'=>$purchaseOrder, 'sample'=>$sample, 'supplier'=>$supplier, 'posTable' => $posTable]);
            } else {
                return redirect('/samplePurchaseOrder')->with('danger', 'PO was not found.');
            }
        // } else {
        //     return redirect('/samplePurchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        // }

    }

    public function updatepos(Request $request, $id)
    {
        $purchaseOrder = samplePurchaseOrder::where('id', $id)->first();
        $posProduct = posTable::where('poid', $id)->get();
        //dd($posProduct);
        //if ($purchaseOrder && $purchaseOrder->status!=1) {
        if (!$purchaseOrder) {
            return redirect('/samplePurchaseOrder')->with('danger', 'PO was not found.');
        }

             $purchaseOrder->pono=$request['pono'];
             $purchaseOrder->supplier_id=$request['supplier_id'];
             $purchaseOrder->podate=$request['podate'];
             $purchaseOrder->del_date=$request['del_date'];
             $purchaseOrder->ref_supplier=strtoupper($request['ref_supplier']);
             $purchaseOrder->buyer_orderno=strtoupper($request['buyer_orderno']);
             $purchaseOrder->payterms=$request['payterms'];
             $purchaseOrder->remarks=$request['remarks'];
             $purchaseOrder->subTotal=$request['subtotalamount'];
             $purchaseOrder->tgst=$request['tgst'];
             $purchaseOrder->tquantity=$request['tquantity'];
             $purchaseOrder->remqty=$request['tquantity'];
             $purchaseOrder->tamount=$request['tamount'];

             if(isset($posProduct)){
                foreach ($posProduct as $posProduct) {
                    $posProduct->delete();
                }
             }

             foreach ($request['po'] as $po) {
                if($po['consumed'] == ''){
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
                        'legs' => $po['legs']
                    ]);
                }

                else{
                    posTable::create([
                        'product_id' => $po['product'],
                        'sample_id' => $po['product'],
                        'ean' => '',
                        'poid' => $id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => $po['quantity']-$po['consumed'],
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'priority' => $po['priority'],
                        'delivery_point' => $po['delivery_point'],
                        'legs' => $po['legs']
                    ]);
                }

                $samplePurchaseBills = samplePurchaseBill::where('purchaseOrder_id',$id)->get();
                if(count($samplePurchaseBills)){
                    foreach ($samplePurchaseBills as $key => $samplePurchaseBill) {
                        $spbTable = spbTable::where('sample_purchasebill_id',$samplePurchaseBill->id)->where('sample_id',$po['product'])->first();
                        if(isset($spbTable->id)){
                            $spbTable->rate = $po['rate'];
                            $spbTable->amount = $po['rate'] * $spbTable->receiveqty;
                            $spbTable->save();
                        }
                        $purchaseBillId = $samplePurchaseBill->swap_id;
                        $purchaseBill = purchaseBill::where('id',$purchaseBillId)->first();
                        if(isset($purchaseBill->id)){
                            $sample = sample::where('id',$po['product'])->first();
                            $product = product::where('code',$sample->prod_code)->first();
                            if(isset($product->id)){
                                $pbTable = pbTable::where('purchasebill_id',$purchaseBill->id)->where('product_id',$product->id)->first();
                                if(isset($pbTable->id)){
                                    $pbTable->rate = $po['rate'];
                                    $pbTable->amount = $po['rate'] * $pbTable->receiveqty;
                                    $pbTable->save();
                                }
                            }

                        }
                    }
                }

            }



            if ($purchaseOrder->save()) {
                return redirect('samplePurchaseOrder')->with('success', 'SamplePurchaseOrder was updated successfully.');
            } else {
                return redirect('samplePurchaseOrder')->with('danger', 'Error occurred while saving SamplePurchaseOrder.');
            }
        //} else {
            //return redirect('/samplePurchaseOrder')->with('danger', 'SamplePurchaseOrder can not be edited.');
        //}
    }



    public function modalSample($id)
    {

        $purchaseOrder = samplePurchaseOrder::find($id)->where('id',$id)->first();
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $poTable = posTable::where('poid',$id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modalSample', ['purchaseOrder'=>$purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable,'fileMap1'=>$fileMap1]);
    }

    public function deleteSample(Request $request, $id)
    {
        $purchaseOrder = samplePurchaseOrder::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if($poRelationCount > 0)
        {
            return redirect('/purchaseOrder')->with('danger', 'Sample Purchase Order cannot be deleted. Purchase Order exist in other relations.');
        }
        else
        {
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




    //Recommended POs
    public function viewRecommendedPo($id)
    {
        $purchaseOrder = purchaseOrderRecommended::find($id);
        if($purchaseOrder->status != 1){
            $product=product::all();
            $supplier=supplier::where('type','!=','Consumable')->get();
            $poTable = porTable::where('poid', $id)->get();
            if ($purchaseOrder) {
                return view('purchaseOrder/viewRecommendedPo', ['purchaseOrder'=>$purchaseOrder, 'product'=>$product, 'supplier'=>$supplier, 'poTable' => $poTable]);
            } else {
                return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }
    }


    public function storeRecommendedPo(Request $request, $id)
    {
            $purchaseOrder = purchaseOrderRecommended::find($id);
            $q = purchaseOrder::create(array_merge([
                    'pono'=>strtoupper($request['pono']),
                    'supplier_id'=>$request['supplier_id'],
                    'podate'=>$request['podate'],
                    'del_date'=>$request['del_date'],
                    'ref_supplier'=>strtoupper($request['ref_supplier']),
                    'buyer_orderno'=>strtoupper($request['buyer_orderno']),
                    'payterms'=>$request['payterms'],
                    'remarks'=>$request['remarks'],
                    'subTotal'=>$request['subtotalamount'],
                    'tgst'=>$request['tgst'],
                    'tquantity'=>$request['tquantity'],
                    'tamount'=>$request['tamount'],
                    'remqty'=>$request['tquantity'],
                    'address_option'=>$request['address_option'],
                ], SendToSupplierPo::createAttributes((int) $request['address_option'], strtoupper($request['pono']), 'purchase_order')));

              foreach ($request['po'] as $po) {
                    poTable::create([
                        'product_id' => $po['product'],
                        'ean' => $po['EAN'],
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
                        'legs' => $po['legs']
                    ]);
                }

                $purchaseOrder->status = 1;
                $purchaseOrder->save();

                $this->incrementBopoCounterIfNeeded((string) $q->pono);

           return redirect('/purchaseOrder')->with('success', 'Order was added successfully.');
    }

    public function recommendedPurchaseOrder()
    {
        $purchaseOrder=purchaseOrderRecommended::get();
        return view('purchaseOrder/recommendedPurchaseOrder', ['purchaseOrder'=>$purchaseOrder]);
    }

    public function updateRecommendedPo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderRecommended::where('id', $id)->first();
        $poProduct = porTable::where('poid', $id)->get();

        if ($purchaseOrder && $purchaseOrder->status!=1) {
             $purchaseOrder->pono=$request['pono'];
             $purchaseOrder->supplier_id=$request['supplier_id'];
             $purchaseOrder->podate=$request['podate'];
             $purchaseOrder->del_date=$request['del_date'];
             $purchaseOrder->ref_supplier=strtoupper($request['ref_supplier']);
             $purchaseOrder->buyer_orderno=strtoupper($request['buyer_orderno']);
             $purchaseOrder->payterms=$request['payterms'];
             $purchaseOrder->remarks=$request['remarks'];
             $purchaseOrder->subTotal=$request['subtotalamount'];
             $purchaseOrder->tgst=$request['tgst'];
             $purchaseOrder->tquantity=$request['tquantity'];
             $purchaseOrder->remqty=$request['tquantity'];
             $purchaseOrder->tamount=$request['tamount'];
             $purchaseOrder->address_option=$request['address_option'];
             if($request['po_revise_date'] != ''){
                $purchaseOrder->po_revise_date = $request['po_revise_date'];
             }else{
                $purchaseOrder->po_revise_date = date('Y-m-d');
             }

             if(isset($poProduct)){
                foreach ($poProduct as $poProduct) {
                    $poProduct->delete();
                }
             }

             foreach ($request['po'] as $po) {
                if($po['consumed'] == ''){
                    porTable::create([
                        'product_id' => $po['product'],
                        'ean' => $po['EAN'],
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
                                                'description' => $po['description']
                    ]);
                }

                else{
                    porTable::create([
                        'product_id' => $po['product'],
                        'ean' => $po['EAN'],
                        'poid' => $id,
                        'quantity' => $po['quantity'],
                        'unit' => $po['unit'],
                        'remqty' => $po['quantity']-$po['consumed'],
                        'rate' => $po['rate'],
                        'amount' => $po['amount'],
                        'gstslab' => $po['gstslab'],
                        'gstamount' => $po['gstamount'],
                        'priority' => $po['priority'],
                                                'delivery_point' => $po['delivery_point'],
                                                'legs' => $po['legs'],
                                                'description' => $po['description']
                    ]);
                }
            }

            if ($purchaseOrder->save()) {
                return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('success', 'PurchaseOrder was updated successfully.');
            } else {
                return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('danger', 'Error occurred while saving PurchaseOrder.');
            }
        } else {
            return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('danger', 'PurchaseOrder can not be edited.');
        }
    }
    public function deleteRecommendedPo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderRecommended::where('id', $id)->first();
        $poRelationCount = $purchaseOrder->purchaseBill->count();
        if($poRelationCount > 0)
        {
            return redirect('/purchaseOrder')->with('danger', 'Purchase Order cannot be deleted. Purchase Order exist in other relations.');
        }
        else
        {
            $poTable = porTable::where('poid', $id)->get();

            if ($poTable) {
                foreach ($poTable as $key => $value) {
                    $poTable[$key]->delete();
                }
            }

            if ($purchaseOrder) {
                if ($purchaseOrder->delete()) {
                    return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('success', 'Purchase Order deleted successfully.');
                } else {
                    return redirect('/purchaseOrder/recommendedPurchaseOrder')->with('danger', 'Purchase Order was not found.');
                }
            }

        }
    }

    public function modalRecommendedPurchaseOrder($id)
    {
        $purchaseOrder = purchaseOrderRecommended::find($id)->where('id',$id)->first();
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $poTable = porTable::where('poid',$id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/modalRecommendedPurchaseOrder', ['purchaseOrder'=>$purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable,'fileMap1'=>$fileMap1]);
    }



   public function viewservice($id)
    {
// return $id;
        $purchaseOrder = Service::with('supplier')->find($id);
        if($purchaseOrder->status != 1){
            $product=ServiceProduct::all();
            $supplier=supplier::where('type','service')->get();

            $poTable = serviceTable::where('poid', $id)->get();
             $subServiceTable  = SubServiceTable::where('po_id',$id)->with('serviceTable')->get();

            if ($purchaseOrder) {
                return view('purchaseOrder/edit_service', ['purchaseOrder'=>$purchaseOrder, 'product'=>$product, 'supplier'=>$supplier, 'poTable' => $poTable,'subServiceTable'=>$subServiceTable]);
            } else {
                return redirect('/purchaseOrder/service')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder/service')->with('danger', 'PO is complete & can not be edited.');
        }

    }





    public function updateServicepo(Request $request, $id)

    {
        // return $request->all();
        // Retrieve the PurchaseOrder
        $purchaseOrder = Service::find($id);

        if (!$purchaseOrder || $purchaseOrder->status == 1) {
            return redirect('/purchaseOrder/service')->with('danger', 'PurchaseOrder cannot be edited.');
        }

        // Update the Purchase Order fields
        $purchaseOrder->pono = strtoupper($request['pono']);
        $purchaseOrder->supplier_id = $request['supplier_id'];
        $purchaseOrder->podate = $request['podate'];
        $purchaseOrder->del_date = $request['del_date'];
        $purchaseOrder->ref_supplier = strtoupper($request['ref_supplier']);
        $purchaseOrder->buyer_orderno = strtoupper($request['buyer_orderno']);
        $purchaseOrder->payterms = $request['payterms'];
        $purchaseOrder->remarks = $request['remarks'];
        $totalQuantity = collect($request['po'])->sum(fn ($po) => (float) ($po['quantity'] ?? 0));
        $purchaseOrder->subTotal = $request['subtotalamount'];
        $purchaseOrder->tgst = $request['tgst'];
        $purchaseOrder->tquantity = $totalQuantity;
        $purchaseOrder->remqty = $totalQuantity;
        $purchaseOrder->tamount = $request['tamount'];
        $purchaseOrder->address_option = $request['address_option'];

        // Check if PO revise date is provided
        if (!empty($request['po_revise_date'])) {
            $purchaseOrder->po_revise_date = $request['po_revise_date'];
        } else {
            $purchaseOrder->po_revise_date = date('Y-m-d');
        }

        // Handle Service Table records
        foreach ($request['po'] as $po) {
            // Check if the serviceTable record exists for this product
            $serviceTable = serviceTable::where('poid', $id)
                ->where('product_id', $po['product'])
                ->first();

            // If it exists, update it
            if ($serviceTable) {
                $serviceTable->update([
                    'quantity' => $po['quantity'],
                    'unit' => $po['unit'],
                    'remqty' => $po['quantity'] - (isset($po['consumed']) ? $po['consumed'] : 0),
                    'rate' => round((float) $po['rate'], 2),
                    'amount' => round((float) $po['amount'], 2),
                    'remaining_amount' => round((float) $po['amount'], 2),
                    'remaining_percentage' => ($po['unit'] ?? '') === 'Count' ? 100 : $serviceTable->remaining_percentage,
                    'gstslab' => $po['gstslab'],
                    'gstamount' => round((float) $po['gstamount'], 2),
                    'service_category_id' => $po['service_category_id'],
                ]);
            } else {
                // If it doesn't exist, create a new serviceTable record
                $serviceTable = serviceTable::create([
                    'product_id' => $po['product'],
                    'poid' => $id,
                    'quantity' => $po['quantity'],
                    'unit' => $po['unit'],
                    'remqty' => $po['quantity'],
                    'rate' => round((float) $po['rate'], 2),
                    'remaining_amount' => round((float) $po['amount'], 2),
                    'remaining_percentage' => ($po['unit'] ?? '') === 'Count' ? 100 : null,
                    'amount' => round((float) $po['amount'], 2),
                    'gstslab' => $po['gstslab'],
                    'gstamount' => round((float) $po['gstamount'], 2),
                    'service_category_id' => $po['service_category_id'],
                ]);
            }

            if (empty($po['sub_name']) || empty($po['sub_rate'])) {
            SubServiceTable::where('serviceTable_id', $serviceTable->id)->delete();
            continue;
        }

        $existingSubIds = [];

        foreach ($po['sub_name'] as $index => $name) {
            if (isset($po['sub_rate'][$index])) {
                $existingSubService = null;

                if (!empty($po['id'][$index])) {
                    $existingSubService = SubServiceTable::find($po['id'][$index]);
                }

                if ($existingSubService) {
                    $existingSubService->update([
                        'sub_rate' => round((float) $po['sub_rate'][$index], 2),
                        'sub_quantity' => $po['sub_quantity'][$index],
                        'sub_remqty' => $po['sub_quantity'][$index],
                        'sub_amount' => round((float) $po['sub_amount'][$index], 2),
                        'sub_gstslab' => $po['sub_gstslab'][$index],
                        'sub_gstamount' => round((float) $po['sub_gstamount'][$index], 2),
                        'sub_remaining_amount' => round((float) $po['sub_amount'][$index], 2),
                        'sub_remaining_percentage' => ($po['unit'] ?? '') === 'Count'
                            ? ($existingSubService->sub_remaining_percentage ?? 100)
                            : $existingSubService->sub_remaining_percentage,
                        'sub_name' => $name,
                    ]);

                    $existingSubIds[] = $existingSubService->id;
                } else {
                    $newSub = SubServiceTable::create([
                        'po_id' => $id,
                        'product_id' => $po['product'],
                        'serviceTable_id' => $serviceTable->id,
                        'sub_name' => $name,
                        'sub_rate' => round((float) $po['sub_rate'][$index], 2),
                        'sub_quantity' => $po['sub_quantity'][$index],
                        'sub_remqty' => $po['sub_quantity'][$index],
                        'sub_amount' => round((float) $po['sub_amount'][$index], 2),
                        'sub_gstslab' => $po['sub_gstslab'][$index],
                        'sub_gstamount' => round((float) $po['sub_gstamount'][$index], 2),
                        'sub_remaining_amount' => round((float) $po['sub_amount'][$index], 2),
                        'sub_remaining_percentage' => ($po['unit'] ?? '') === 'Count' ? 100 : 0,
                    ]);

                    $existingSubIds[] = $newSub->id;
                }
            }
        }

        SubServiceTable::where('serviceTable_id', $serviceTable->id)
            ->whereNotIn('id', $existingSubIds)
            ->delete();
    }


        // Save the PurchaseOrder after all updates
        if ($purchaseOrder->save()) {
            return redirect('/purchaseOrder/service')->with('success', 'PurchaseOrder was updated successfully.');
        } else {
            return redirect('/purchaseOrder/service')->with('danger', 'Error occurred while saving PurchaseOrder.');
        }
    }




    public function indexservice(Request $request)
    {
        $categoryId = $request->input('category_id');

        $category = serviceCategories::all();

        if ($categoryId) {
            // Get all serviceTable records for the selected category
            $serviceTable1 = serviceTable::where('service_category_id', $categoryId)
                ->orderBy('created_at', 'desc')
                ->get();

            $poIds = $serviceTable1->pluck('poid');

            $purchaseOrder = Service::where('address_option', '!=', '100')
                ->whereIn('id', $poIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }
         else {



            $purchaseOrder = Service::where('address_option', '!=', '100')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        foreach ($purchaseOrder->where('status', 0) as $pendingPo) {
            ServiceInvoiceQuantityService::syncAllForPo((int) $pendingPo->id);
        }

        if ($purchaseOrder->where('status', 0)->isNotEmpty()) {
            $poIds = $purchaseOrder->pluck('id');
            $purchaseOrder = Service::whereIn('id', $poIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('purchaseOrder/viewService', ['purchaseOrder' => $purchaseOrder, 'category' => $category]);
    }


    public function servicemodal($id)
    {


         $purchaseOrder = Service::find($id)->where('id',$id)->first();
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

          $poTable = serviceTable::where('poid',$id)->with('sub_serviceproduct')->get();
        // $subServiceTable  = SubServiceTable::where('po_id',$id)->with('serviceTable')->get();


        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('purchaseOrder/service_modal', ['purchaseOrder'=>$purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable,'fileMap1'=>$fileMap1]);
    }


    public function update_statusService(Request $request, $id){
        $purchaseOrder = Service::where('id', $id)->first();

        if (isset($purchaseOrder->id)) {
            $purchaseOrder->status = $request->status;
            $purchaseOrder->save();
            return redirect('/purchaseOrder/service')->with('success', 'Purchase Order status updated successfully.');
        }else{
            return redirect('/purchaseOrder/service')->with('danger', 'Purchase Order was not found.');
        }
    }


    public function cancelService(Request $request, $id)
    {
        $purchaseOrder = Service::where('id', $id)->first();

        // Check if the purchase order exists
        if (!$purchaseOrder) {
            return redirect('/purchaseOrder/service')->with('danger', 'Purchase Order not found.');
        }


        $poRelationCount = $purchaseOrder->servicepurchaseBill->count();
        if ($poRelationCount > 0) {
            return redirect('/purchaseOrder/service')->with('danger', 'Purchase Order cannot be canceled. It exists in other relations.');
        }


        $purchaseOrder->status = 2;

        if ($purchaseOrder->save()) {
            return redirect('/purchaseOrder/service')->with('success', 'Purchase Order canceled successfully.');
        } else {
            return redirect('/purchaseOrder/service')->with('danger', 'There was an error canceling the Purchase Order.');
        }
    }

    public function serviceproduct(){
        $product=ServiceProduct::all();
        return response()->json(['product'=>$product]);

    }


    public function storeService(Request $request)
    {
        // return $requ est->all();
        $existingOrder = Service::where('pono', strtoupper($request['pono']))->first();

        if ($existingOrder) {
            // Return an error message if the 'pono' already exists
            return redirect()->back()->with('error', 'The Purchase Order number already exists.');
        }
        $pono = 'S/' . intval(preg_replace('/[^0-9]/', '', $request->input('pono')));
        $totalQuantity = collect($request['po'])->sum(fn ($po) => (float) ($po['quantity'] ?? 0));
        $q = Service::create(array_merge([
            'pono' => $pono,
            'supplier_id' => $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'ref_supplier' => strtoupper($request['ref_supplier']),
            'buyer_orderno' => strtoupper($request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => round((float) $request['subtotalamount'], 2),
            'tgst' => round((float) $request['tgst'], 2),
            'tquantity' => $totalQuantity,
            'tamount' => round((float) $request['tamount'], 2),
            'remqty' => $totalQuantity,
            'address_option' => $request['address_option'],
        ], SendToSupplierPo::createAttributes((int) $request['address_option'], $pono, 'services')));

        foreach ($request['po'] as $po) {

            // Save data to serviceTable
            $serviceTable = serviceTable::create([
                'product_id' => $po['product'],
                'poid' => $q->id,
                'quantity' => $po['quantity'],
                'unit' => $po['unit'],
                'remqty' => $po['quantity'],
                'rate' => round((float) $po['rate'], 2),
                'remaining_amount' => round((float) $po['amount'], 2),
                'remaining_percentage' => ($po['unit'] ?? '') === 'Count' ? 100 : null,
                'amount' => round((float) $po['amount'], 2),
                'gstslab' => $po['gstslab'],
                'gstamount' => round((float) $po['gstamount'], 2),
                'service_category_id' => $po['service_category_id']
            ]);

            if (isset($po['sub_name']) && isset($po['sub_rate'])) {
                foreach ($po['sub_name'] as $index => $name) {
                    if (isset($po['sub_rate'][$index])) {
                        SubServiceTable::create([
                            'po_id' => $q->id,
                            'serviceTable_id' => $serviceTable->id,
                            'product_id' => $serviceTable->product_id,
                            'sub_name' => $name,
                            'sub_rate' => round((float) $po['sub_rate'][$index], 2),
                            'sub_quantity' => $po['sub_quantity'][$index],
                            'sub_remqty' => $po['sub_quantity'][$index],
                            'sub_amount' => round((float) $po['sub_amount'][$index], 2),
                            'sub_gstslab' => $po['sub_gstslab'][$index],
                            'sub_gstamount' => round((float) $po['sub_gstamount'][$index], 2),
                            'sub_remaining_amount' => round((float) $po['sub_amount'][$index], 2),
                            'sub_remaining_percentage' => ($po['unit'] ?? '') === 'Count' ? 100 : 0,
                        ]);
                    }
                }
            }
        }


        if (SendToSupplierPo::isSent($q)) {
            SendToSupplierPo::notifySupplier((int) $request['supplier_id'], $q->pono);
        }

        return redirect('/purchaseOrder/service')->with('success', 'Order was added successfully.');
    }


    public function autocomplete(Request $request)
    {
        $search = $request->get('term');

        $products = product::where('name', 'LIKE', "%{$search}%")
            ->orWhere('code', 'LIKE', "%{$search}%")
            ->select('id', 'name','code')
            ->get();

        $results = [];

        foreach ($products as $product) {
            $pricing = pricingTable::where('product_id', $product->id)
                ->where('buyer_id2', 3)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$pricing) {
                $pricing = pricingTable::where('product_id', $product->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            $results[] = [
                'label' => $product->code." - ".$product->name,
                'name' => $product->name,
                'value' => $product->name,
                'id' => $product->id,
                'fob_pricing' => $pricing ? $pricing->fobINCost : 0,
            ];
        }

        return response()->json($results);
    }
   public function viewConsumablePomonthend($id)
    {

        $purchaseOrder = purchaseOrderConsumable::find($id);
        if ($purchaseOrder->status != 1) {
            $product = consumable::all();
            $supplier = supplier::where('type', '!=', 'Furniture')->get();
            $poTable = pocTable::where('poid', $id)->get();
            $unitTypes = UnitType::pluck('data_type', 'name');
            foreach ($poTable as $item) {
                $item->data_type = $unitTypes[$item->unit] ?? 'int';
            }

            if ($purchaseOrder) {
                return view('purchaseOrder/viewConsumablePoMonthEnd', ['purchaseOrder' => $purchaseOrder, 'product' => $product, 'supplier' => $supplier, 'poTable' => $poTable]);
            } else {
                return redirect('/purchaseOrder')->with('danger', 'PO was not found.');
            }
        } else {
            return redirect('/purchaseOrder')->with('danger', 'PO is complete & can not be edited.');
        }
    }


      public function viewConsumablePomonthendUpdate(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderConsumable::find($id);
        if (! $purchaseOrder || (int) $purchaseOrder->status === 1) {
            return redirect('/purchaseOrder/viewConsumablePoMonthEnd')
                ->with('danger', 'PurchaseOrder can not be edited.');
        }

        $challan = Challan::where('purchase_order_id', $id)
            ->where('challan_number', $purchaseOrder->ref_supplier)
            ->first();
        $supplier = supplier::where('id', $request->input('supplier_id'))->first();

        try {
            DB::transaction(function () use ($request, $id, $purchaseOrder, $challan, $supplier) {
                $sourceInvoice = invoice::where('invoiceno', (string) $purchaseOrder->ref_supplier)->first();
                $sourceInvoiceId = $sourceInvoice ? (int) $sourceInvoice->id : 0;
                $hasStockLogInvoiceId = \Schema::hasColumn('stock_log_consumable', 'invoice_id');
                $invoiceDate = $sourceInvoice && ! empty($sourceInvoice->date)
                    ? Carbon::parse($sourceInvoice->date)->toDateString()
                    : Carbon::parse($purchaseOrder->del_date ?: $purchaseOrder->podate)->toDateString();
                $inDate = Carbon::parse($purchaseOrder->podate)->toDateString();
                $clockTime = Carbon::now()->format('H:i:s');

                // Keep old qty totals (aggregated) for net stock impact rollback from old month-end logs.
                $existingQuantities = pocTable::where('poid', $id)
                    ->get()
                    ->groupBy('consumable_id')
                    ->map(function ($rows) {
                        return (float) $rows->sum('quantity');
                    })
                    ->toArray();

                $newQuantities = [];

                if ($challan) {
                    $challan->purchase_order_id = $purchaseOrder->id;
                    $challan->supplier_id = $request->input('supplier_id');
                    $challan->subTotal = $request->input('subtotalamount');
                    $challan->tquantity = $request->input('tquantity');
                    $challan->tamount = $request->input('tamount');
                    $challan->tgst = $request->input('tgst');
                    $challan->save();
                    ChallanProduct::where('challan_id', $challan->id)->delete();
                }

                $purchaseOrder->pono = $request->input('pono');
                $purchaseOrder->supplier_id = $request->input('supplier_id');
                $purchaseOrder->podate = $request->input('podate');
                $purchaseOrder->del_date = $request->input('del_date');
                $purchaseOrder->month = is_array($request->month) ? implode(',', $request->month) : $request->month;
                $purchaseOrder->buyer_orderno = strtoupper($request->input('buyer_orderno'));
                $purchaseOrder->payterms = $request->input('payterms');
                $purchaseOrder->remarks = $request->input('remarks');
                $purchaseOrder->subTotal = $request->input('subtotalamount');
                $purchaseOrder->tgst = $request->input('tgst');
                $purchaseOrder->tquantity = $request->input('tquantity');
                $purchaseOrder->remqty = $request->input('tquantity');
                $purchaseOrder->tamount = $request->input('tamount');
                $purchaseOrder->po_revise_date = $request->input('po_revise_date') ?: date('Y-m-d');
                $purchaseOrder->save();

                pocTable::where('poid', $id)->delete();

                foreach ($request->po as $po) {
                    $consumableId = (int) ($po['consumable'] ?? 0);
                    $quantity = (float) ($po['quantity'] ?? 0);
                    $consumed = (float) ($po['consumed'] ?? 0);
                    if ($consumableId <= 0 || $quantity <= 0) {
                        continue;
                    }

                    $newQuantities[$consumableId] = ($newQuantities[$consumableId] ?? 0.0) + $quantity;

                    pocTable::create([
                        'consumable_id' => $consumableId,
                        'poid' => $id,
                        'quantity' => $quantity,
                        'unit' => $po['unit'] ?? null,
                        'remqty' => max(0, $quantity - $consumed),
                        'rate' => $po['rate'] ?? 0,
                        'amount' => $po['amount'] ?? 0,
                        'gstslab' => $po['gstslab'] ?? 0,
                        'gstamount' => $po['gstamount'] ?? 0,
                        'description' => $po['description'] ?? null,
                    ]);

                    if ($challan) {
                        ChallanProduct::create([
                            'consumable_id' => $consumableId,
                            'challan_id' => $challan->id,
                            'purchase_order_id' => $purchaseOrder->id,
                            'rate' => $po['rate'] ?? 0,
                            'gstslab' => $po['gstslab'] ?? 0,
                            'amount' => $po['amount'] ?? 0,
                            'total' => (float) ($po['amount'] ?? 0) + (float) ($po['gstamount'] ?? 0),
                            'gst' => $po['gstamount'] ?? 0,
                            'quantity' => $quantity,
                        ]);
                    }
                }

                $allConsumableIds = array_values(array_unique(array_merge(array_keys($existingQuantities), array_keys($newQuantities))));
                $targetSupplierId = (int) ($purchaseOrder->supplier_id ?? 0);
                $targetSupplierName = trim((string) ($supplier->c_name ?? ''));

                foreach ($allConsumableIds as $consumableId) {
                    $consumableId = (int) $consumableId;
                    $newQty = (float) ($newQuantities[$consumableId] ?? 0);

                    $oldIn = (float) stockLogConsumable::where('consumable_id', $consumableId)
                        ->where('voucher_no', $purchaseOrder->ref_supplier)
                        ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                            $q->where('invoice_id', $sourceInvoiceId);
                        })
                        ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                            $q->where('supplier_id', $targetSupplierId);
                            if ($targetSupplierName !== '') {
                                $q->orWhere(function ($qq) use ($targetSupplierName) {
                                    $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                                });
                            }
                        })
                        ->where('type', 1)
                        ->where('remark', 'MonthEnd PO Stock In')
                        ->sum('quantity');
                    $oldOut = (float) stockLogConsumable::where('consumable_id', $consumableId)
                        ->where('voucher_no', $purchaseOrder->ref_supplier)
                        ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                            $q->where('invoice_id', $sourceInvoiceId);
                        })
                        ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                            $q->where('supplier_id', $targetSupplierId);
                            if ($targetSupplierName !== '') {
                                $q->orWhere(function ($qq) use ($targetSupplierName) {
                                    $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                                });
                            }
                        })
                        ->where('type', 2)
                        ->where('remark', 'MonthEnd PO Stock Out')
                        ->sum('quantity');

                    $newIn = $newQty;
                    $newOut = $newQty;
                    $netDelta = ($newIn - $newOut) - ($oldIn - $oldOut);

                    $consumable = consumable::find($consumableId);
                    if ($consumable && abs($netDelta) > 0.000001) {
                        $consumable->quantity = (float) $consumable->quantity + $netDelta;
                        $consumable->save();
                    }

                    stockLogConsumable::where('consumable_id', $consumableId)
                        ->where('voucher_no', $purchaseOrder->ref_supplier)
                        ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                            $q->where('invoice_id', $sourceInvoiceId);
                        })
                        ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                            $q->where('supplier_id', $targetSupplierId);
                            if ($targetSupplierName !== '') {
                                $q->orWhere(function ($qq) use ($targetSupplierName) {
                                    $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                                });
                            }
                        })
                        ->where(function ($q) {
                            $q->where(function ($qq) {
                                $qq->where('type', 1)->where('remark', 'MonthEnd PO Stock In');
                            })->orWhere(function ($qq) {
                                $qq->where('type', 2)->where('remark', 'MonthEnd PO Stock Out');
                            });
                        })
                        ->delete();

                    if ($newQty > 0) {
                        $inAt = Carbon::parse($inDate . ' ' . $clockTime);
                        $outAt = Carbon::parse($invoiceDate . ' ' . $clockTime)->addSecond();
                        if ($outAt->lessThanOrEqualTo($inAt)) {
                            $outAt = $inAt->copy()->addSecond();
                        }

                        $inLogData = [
                            'consumable_id' => $consumableId,
                            'voucher_no' => $purchaseOrder->ref_supplier,
                            'quantity' => $newQty,
                            'opening_balance' => 0,
                            'remaining_stock' => 0,
                            'type' => 1,
                            'supplier_name' => $supplier->c_name ?? 'N/A',
                            'supplier_id' => $targetSupplierId > 0 ? $targetSupplierId : null,
                            'remark' => 'MonthEnd PO Stock In',
                            'created_at' => $inAt,
                            'updated_at' => $inAt,
                        ];
                        if ($hasStockLogInvoiceId && $sourceInvoiceId > 0) {
                            $inLogData['invoice_id'] = $sourceInvoiceId;
                        }
                        stockLogConsumable::create($inLogData);

                        $outLogData = [
                            'consumable_id' => $consumableId,
                            'voucher_no' => $purchaseOrder->ref_supplier,
                            'ref_no' => 'MonthEnd Invoice Stock Out',
                            'quantity' => $newQty,
                            'opening_balance' => 0,
                            'remaining_stock' => 0,
                            'type' => 2,
                            'supplier_name' => $supplier->c_name ?? 'N/A',
                            'supplier_id' => $targetSupplierId > 0 ? $targetSupplierId : null,
                            'remark' => 'MonthEnd PO Stock Out',
                            'created_at' => $outAt,
                            'updated_at' => $outAt,
                        ];
                        if ($hasStockLogInvoiceId && $sourceInvoiceId > 0) {
                            $outLogData['invoice_id'] = $sourceInvoiceId;
                        }
                        stockLogConsumable::create($outLogData);
                    }

                    $allLogs = stockLogConsumable::where('consumable_id', $consumableId)
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();
                    if ($allLogs->isNotEmpty()) {
                        $currentStock = (float) ($allLogs->first()->opening_balance ?? 0);
                        foreach ($allLogs as $log) {
                            $log->opening_balance = $currentStock;
                            $log->remaining_stock = (int) $log->type === 1
                                ? $currentStock + (float) $log->quantity
                                : $currentStock - (float) $log->quantity;
                            $currentStock = (float) $log->remaining_stock;
                            $log->save();
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::error('viewConsumablePomonthendUpdate failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect('/monthendpo/consumable')
                ->with('danger', 'Error occurred while saving PurchaseOrder.');
        }

        return redirect('/monthendpo/consumable')
            ->with('success', 'PurchaseOrder and Stock Logs updated successfully.');
    }

    /**
     * Delete a pending M-series month-end consumable PO: reverse {@see invoiceController::monthEndPOcreate}
     * / {@see invoiceController::hardwareMonthEndPOcreate} stock logs and remove challan/lines.
     * Only {@see purchaseOrderConsumable::$status} === 0 (pending).
     */
    public function deleteConsumableMonthEndPo(Request $request, $id)
    {
        $purchaseOrder = purchaseOrderConsumable::find($id);
        if (! $purchaseOrder) {
            return redirect('/monthendpo/consumable')->with('danger', 'Purchase Order not found.');
        }
        if ((int) ($purchaseOrder->address_option ?? 0) !== 100) {
            return redirect('/monthendpo/consumable')->with('danger', 'Only month-end consumable POs can be deleted here.');
        }
        if (! preg_match('/^M\//i', trim((string) $purchaseOrder->pono))) {
            return redirect('/monthendpo/consumable')->with('danger', 'Only M-series month-end POs can be deleted here.');
        }
        if ((int) $purchaseOrder->status !== 0) {
            return redirect('/monthendpo/consumable')->with('danger', 'Only pending month-end POs can be deleted.');
        }

        $supplier = supplier::where('id', $purchaseOrder->supplier_id)->first();
        if (! $supplier) {
            return redirect('/monthendpo/consumable')->with('danger', 'Supplier not found for this PO.');
        }

        try {
            DB::transaction(function () use ($id, $purchaseOrder, $supplier) {
                $sourceInvoice = invoice::where('invoiceno', (string) $purchaseOrder->ref_supplier)->first();
                $sourceInvoiceId = $sourceInvoice ? (int) $sourceInvoice->id : 0;
                $hasStockLogInvoiceId = \Schema::hasColumn('stock_log_consumable', 'invoice_id');
                $targetSupplierId = (int) ($purchaseOrder->supplier_id ?? 0);
                $targetSupplierName = trim((string) ($supplier->c_name ?? ''));

                $consumableIds = pocTable::where('poid', $id)
                    ->distinct()
                    ->pluck('consumable_id')
                    ->map(fn ($v) => (int) $v)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                foreach ($consumableIds as $consumableId) {
                    $oldIn = (float) stockLogConsumable::where('consumable_id', $consumableId)
                        ->where('voucher_no', $purchaseOrder->ref_supplier)
                        ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                            $q->where('invoice_id', $sourceInvoiceId);
                        })
                        ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                            $q->where('supplier_id', $targetSupplierId);
                            if ($targetSupplierName !== '') {
                                $q->orWhere(function ($qq) use ($targetSupplierName) {
                                    $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                                });
                            }
                        })
                        ->where('type', 1)
                        ->where('remark', 'MonthEnd PO Stock In')
                        ->sum('quantity');
                    $oldOut = (float) stockLogConsumable::where('consumable_id', $consumableId)
                        ->where('voucher_no', $purchaseOrder->ref_supplier)
                        ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                            $q->where('invoice_id', $sourceInvoiceId);
                        })
                        ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                            $q->where('supplier_id', $targetSupplierId);
                            if ($targetSupplierName !== '') {
                                $q->orWhere(function ($qq) use ($targetSupplierName) {
                                    $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                                });
                            }
                        })
                        ->where('type', 2)
                        ->where('remark', 'MonthEnd PO Stock Out')
                        ->sum('quantity');

                    $netDelta = (0.0 - 0.0) - ($oldIn - $oldOut);

                    $consumable = consumable::find($consumableId);
                    if ($consumable && abs($netDelta) > 0.000001) {
                        $consumable->quantity = (float) $consumable->quantity + $netDelta;
                        $consumable->save();
                    }

                    stockLogConsumable::where('consumable_id', $consumableId)
                        ->where('voucher_no', $purchaseOrder->ref_supplier)
                        ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                            $q->where('invoice_id', $sourceInvoiceId);
                        })
                        ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                            $q->where('supplier_id', $targetSupplierId);
                            if ($targetSupplierName !== '') {
                                $q->orWhere(function ($qq) use ($targetSupplierName) {
                                    $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                                });
                            }
                        })
                        ->where(function ($q) {
                            $q->where(function ($qq) {
                                $qq->where('type', 1)->where('remark', 'MonthEnd PO Stock In');
                            })->orWhere(function ($qq) {
                                $qq->where('type', 2)->where('remark', 'MonthEnd PO Stock Out');
                            });
                        })
                        ->delete();

                    $allLogs = stockLogConsumable::where('consumable_id', $consumableId)
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();
                    if ($allLogs->isNotEmpty()) {
                        $currentStock = (float) ($allLogs->first()->opening_balance ?? 0);
                        foreach ($allLogs as $log) {
                            $log->opening_balance = $currentStock;
                            $log->remaining_stock = (int) $log->type === 1
                                ? $currentStock + (float) $log->quantity
                                : $currentStock - (float) $log->quantity;
                            $currentStock = (float) $log->remaining_stock;
                            $log->save();
                        }
                    }
                }

                ChallanProduct::where('purchase_order_id', $id)->delete();
                Challan::where('purchase_order_id', $id)->delete();
                pocTable::where('poid', $id)->delete();
                popTable::where('poid', $id)->delete();
                $purchaseOrder->delete();
            });
        } catch (\Throwable $e) {
            \Log::error('deleteConsumableMonthEndPo failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect('/monthendpo/consumable')->with('danger', 'Failed to delete month-end PO: '.$e->getMessage());
        }

        return redirect('/monthendpo/consumable')->with('success', 'Month-end PO deleted successfully.');
    }

}