<?php

namespace App\Http\Controllers;

use App\Notification;
use App\pocTable;
use App\purchaseOrderConsumable;
use App\soTable;
use App\supplierInvoice;
use App\Support\ConsumableMonthEndInvoiceSupport;
use App\Support\SupplierMultiPoSupport;
use App\UnitType;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupplierMultiPoConsumableController extends Controller
{
    public function acceptedPurchaseOrders()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('supplier')) {
            abort(403);
        }

        $purchaseOrder = purchaseOrderConsumable::where('supplier_id', $user->supplier_id)
            ->where('supplier_status', 1)
            ->where('type', 1)
            ->with(['poTable.consumable', 'poTable.product'])
            ->orderByDesc('created_at')
            ->get();

        return view('supplierUser.acceptedPurchaseOrdersConsumableMulti', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function raiseInvoice(Request $request)
    {
        $request->validate(['selected_po_ids' => 'required|string']);

        if ($request->isMethod('post')) {
            return redirect()->to(
                url('/supplier-dashboard/raise-invoice/multiple-consumable') . '?' . http_build_query([
                    'selected_po_ids' => $request->input('selected_po_ids'),
                ])
            );
        }

        $selectedPOIds = SupplierMultiPoSupport::normalizeConsumablePoIds($request->input('selected_po_ids'));
        if (empty($selectedPOIds)) {
            return redirect()->back()->with('error', 'No valid Purchase Order selected.');
        }

        $supplierId = auth()->user()->supplier_id;
        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $selectedPOIds)
            ->where('type', 1)
            ->where('supplier_id', $supplierId)
            ->with('supplier')
            ->get();

        if ($purchaseOrders->count() !== count($selectedPOIds)) {
            return redirect()->back()->with('error', 'One or more purchase orders were not found or are not consumable POs.');
        }

        $ineligible = SupplierMultiPoSupport::ineligiblePoSummary($purchaseOrders);
        if (! empty($ineligible)) {
            $summary = collect($ineligible)
                ->map(fn ($date, $pono) => $pono . ' (eligible on ' . $date . ')')
                ->implode(', ');

            return redirect()->back()->with('danger', 'Invoice cannot be generated yet for: ' . $summary . '.');
        }

        $poTables = pocTable::whereIn('poid', $selectedPOIds)
            ->with(['purchaseOrderTable', 'consumable.unitType', 'product'])
            ->get();
        $poTables = $this->attachConsumableLineMeta($poTables, $purchaseOrders);

        $user = auth()->user()->id;
        $invoiceTotal = supplierInvoice::where('user_id', $user)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->whereDate('invoice_date', now()->toDateString())
            ->sum('tamount');

        $userSplier = User::where('id', $user)->with('supplier')->first();
        $ewayThreshold = SupplierMultiPoSupport::consumableEwayThreshold($purchaseOrders);

        return view('supplierUser.raiseInvoiceMultipleConsumable', [
            'purchaseOrders' => $purchaseOrders,
            'poTable' => $poTables,
            'type' => 2,
            'invoiceTotal' => $invoiceTotal,
            'userSplier' => $userSplier,
            'selectedPOIds' => $selectedPOIds,
            'supplierNames' => $purchaseOrders->pluck('supplier.c_name')->unique()->values(),
            'poNumbers' => $purchaseOrders->pluck('pono')->unique()->values(),
            'deliveryDates' => $purchaseOrders->pluck('del_date')->unique()->values(),
            'supplierRefs' => $purchaseOrders->pluck('ref_supplier')->unique()->values(),
            'ewayThreshold' => $ewayThreshold,
            'addressOptionByPo' => $purchaseOrders->pluck('address_option', 'id'),
        ]);
    }

    public function submitInvoice(Request $request)
    {
        $user = auth()->user();
        $userSplier = User::where('id', $user->id)->first();
        $selectedPOIds = SupplierMultiPoSupport::normalizeConsumablePoIds($request->input('selectedPOIds', ''));
        $supplierId = $userSplier->supplier_id;

        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $selectedPOIds)
            ->where('type', 1)
            ->where('supplier_id', $supplierId)
            ->with('supplier')
            ->get()
            ->keyBy('id');

        if ($purchaseOrders->isEmpty()) {
            return back()->with('danger', 'No valid purchase orders.')->withInput();
        }

        $ineligible = SupplierMultiPoSupport::ineligiblePoSummary($purchaseOrders);
        if (! empty($ineligible)) {
            $summary = collect($ineligible)
                ->map(fn ($date, $pono) => $pono . ' (eligible on ' . $date . ')')
                ->implode(', ');

            return back()->with('danger', 'Invoice cannot be generated yet for: ' . $summary . '.')->withInput();
        }

        $pbTotal = (float) ($request->pbTotal ?? 0);
        if (SupplierMultiPoSupport::requiresConsumableEway($purchaseOrders, $pbTotal)) {
            $ewayBillNo = trim((string) $request->input('ewaybill', ''));
            if ($ewayBillNo === '') {
                $threshold = SupplierMultiPoSupport::consumableEwayThreshold($purchaseOrders);

                return back()->withErrors([
                    'ewaybill' => 'E-Way Bill number is required when invoice total exceeds ' . number_format($threshold, 0, '.', ',') . '.',
                ])->withInput();
            }
        }

        $supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
        $eway_bill_no = strtoupper($request->input('ewaybill', ''));
        $vehicle_no = strtoupper($request->input('vehicle_no', ''));
        $imageFileName = $request->hasFile('eway_bill_upload')
            ? basename(Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload')))
            : 'default.jpg';

        $errors = [];
        if ($eway_bill_no !== '') {
            $existsEway = supplierInvoice::where('supplier_id', $supplierId)
                ->where('eway_bill_no', $eway_bill_no)
                ->where(function ($q) {
                    $q->where('status', '!=', 2)->orWhereNull('status');
                })
                ->exists();
            if ($existsEway) {
                $errors['eway_bill_no'] = 'This E-way Bill number already exists for this supplier.';
            }
        }

        $existsInv = supplierInvoice::where('supplier_id', $supplierId)
            ->where('supplier_invoice_number', $supplier_invoice_number)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->exists();
        if ($existsInv) {
            $errors['supplier_invoice_number'] = 'This Supplier Invoice number already exists for this supplier.';
        }
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $invoiceUser = User::where('supplier_id', $supplierId)->first();
        $invoiceUserId = $invoiceUser->id;
        foreach ($purchaseOrders as $po) {
            if (ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)) {
                $invoiceUserId = $user->id;
                break;
            }
        }

        $existingInvoice = supplierInvoice::where('supplier_invoice_number', $supplier_invoice_number)->first();
        $internal_invoice_number = $existingInvoice ? $existingInvoice->internal_invoice_number : null;

        $monthEndLineError = $this->validateMonthEndInvoiceLines($request->pb ?? [], $purchaseOrders);
        if ($monthEndLineError !== null) {
            return back()->with('danger', $monthEndLineError)->withInput();
        }

        $monthEndHeaderTotals = ConsumableMonthEndInvoiceSupport::headerTotalsFromSubmittedPbRowsMulti(
            $request->pb ?? [],
            $purchaseOrders
        );

        $invoice = supplierInvoice::create([
            'purchase_order_id' => implode(',', $selectedPOIds),
            'supplier_invoice_number' => $supplier_invoice_number,
            'eway_bill_no' => $eway_bill_no,
            'vehicle_no' => $vehicle_no,
            'eway_bill_pdf' => $imageFileName,
            'supplier_id' => $invoiceUser->supplier_id,
            'user_id' => $invoiceUserId,
            'tamount' => $monthEndHeaderTotals['tamount'] ?? $request->pbTotal,
            'purchase_order_type' => 'Consumable',
            'invoice_date' => $request->invoice_date,
            'tquantity' => $monthEndHeaderTotals['tquantity'] ?? $request->pbQty,
            'subTotal' => $monthEndHeaderTotals['subTotal'] ?? $request->pbSubTotal,
            'tgst' => $monthEndHeaderTotals['tgst'] ?? $request->pbGST,
            'tdsTotal' => $request->tdsTotal ?? 0,
            'roundoff' => $monthEndHeaderTotals !== null ? 0 : ($request->roundoff ?? 0),
            'internal_invoice_number' => $internal_invoice_number,
            'mulitple_po' => $purchaseOrders->pluck('pono')->implode(','),
        ]);

        foreach ($request->pb ?? [] as $pb) {
            $receiveQty = (float) ($pb['receiveqty'] ?? 0);
            if ($receiveQty <= 0) {
                continue;
            }

            $poid = (int) ($pb['poid'] ?? 0);
            $productId = (int) ($pb['product'] ?? 0);
            $po = $purchaseOrders->get($poid);
            if (! $po) {
                continue;
            }

            $gst = $pb['gstslab'] ?? 0;
            $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb);
            $pocLine = ConsumableMonthEndInvoiceSupport::resolvePocLine($po, $productId, $pocLineId);
            $lineAmount = ConsumableMonthEndInvoiceSupport::soLineAmountFromRequest($po, $pb, $pocLine);
            $total = ConsumableMonthEndInvoiceSupport::soLineTotalFromRequest($po, $pb, $pocLine);

            $soAttrs = [
                'product_id' => $productId,
                'supplier_invoice_id' => $invoice->id,
                'purchase_order_id' => $poid,
                'gst' => $gst,
                'amount' => $lineAmount,
                'total' => $total,
                'quantity' => $receiveQty,
                'mulitple_po' => $poid,
            ];

            if ($pocLineId !== null && ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()) {
                $soAttrs['poc_table_id'] = $pocLineId;
            }

            $p = soTable::create($soAttrs);
            if ((int) ($request->typeUnit ?? 2) === 2 && ! empty($pb['unit'])) {
                $p->unit = $pb['unit'];
                $p->save();
            }

            $poProduct = $pocLine ?? ConsumableMonthEndInvoiceSupport::resolvePocLine($po, $productId, $pocLineId);
            if (! $poProduct) {
                return back()->with('danger', 'Purchase order line not found for one of the products.')->withInput();
            }
            $poProduct->remqty -= $receiveQty;
            $poProduct->save();
        }

        if (! $internal_invoice_number) {
            $invoice->internal_invoice_number = 'GV-' . date('Y') . '-' . $invoice->id;
            $invoice->save();
        }

        ConsumableMonthEndInvoiceSupport::applyMonthEndHeaderToInvoice(
            $invoice,
            $request->pb ?? [],
            $purchaseOrders
        );

        foreach ($selectedPOIds as $id) {
            $rem = (float) pocTable::where('poid', $id)->sum('remqty');
            purchaseOrderConsumable::where('id', $id)->update([
                'remqty' => $rem,
                'status' => $rem == 0 ? 1 : 0,
            ]);
        }

        $supplierName = optional(User::where('id', $user->id)->with('supplier')->first()->supplier)->c_name ?? 'supplier';
        $admins = User::whereIn('role', ['Admin', 'Factory'])->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Multi-PO consumable invoice (' . $invoice->mulitple_po . ') created by ' . $supplierName,
                'is_read' => 0,
            ]);
        }

        return redirect(url('/supplier-dashboard/accepted-purchase-orders-consumable-multi'))
            ->with('success', 'Invoice against multiple consumable POs generated successfully!');
    }

    public function editInvoice($id)
    {
        $user = auth()->user();
        $supplierInvoice = $this->findEditableMultiConsumableInvoice((int) $id, $user);

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $poIds)
            ->where('type', 1)
            ->where('supplier_id', $user->supplier_id)
            ->with('supplier')
            ->get()
            ->keyBy('id');

        $poTable = pocTable::whereIn('poid', $poIds)
            ->with(['purchaseOrderTable', 'consumable.unitType', 'product'])
            ->get();
        $poTable = $this->attachConsumableLineMeta($poTable, $purchaseOrders);
        $poTable = $this->attachInvoiceQtyToPocLines($poTable, (int) $supplierInvoice->id, $purchaseOrders);

        $userSplier = User::where('id', $user->id)->with('supplier')->first();
        $ewayThreshold = SupplierMultiPoSupport::consumableEwayThreshold($purchaseOrders);
        $invoiceTotal = supplierInvoice::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->whereDate('invoice_date', now()->toDateString())
            ->sum('tamount');

        return view('supplierUser.editInvoiceMultiConsumable', [
            'supplierInvoice' => $supplierInvoice,
            'invoiceTotal' => $invoiceTotal,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $purchaseOrders->first(),
            'poTable' => $poTable,
            'type' => 2,
            'userSplier' => $userSplier,
            'selectedPOIds' => $poIds,
            'supplierNames' => $purchaseOrders->pluck('supplier.c_name')->unique()->values(),
            'poNumbers' => $purchaseOrders->pluck('pono')->unique()->values(),
            'deliveryDates' => $purchaseOrders->pluck('del_date')->unique()->values(),
            'supplierRefs' => $purchaseOrders->pluck('ref_supplier')->unique()->values(),
            'ewayThreshold' => $ewayThreshold,
            'addressOptionByPo' => $purchaseOrders->pluck('address_option', 'id'),
        ]);
    }

    public function updateInvoice(Request $request, $id)
    {
        $user = auth()->user();
        $supplierInvoice = $this->findEditableMultiConsumableInvoice((int) $id, $user);

        $selectedPOIds = SupplierMultiPoSupport::normalizeConsumablePoIds(
            $request->input('selectedPOIds', $supplierInvoice->purchase_order_id)
        );

        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $selectedPOIds)
            ->where('type', 1)
            ->where('supplier_id', $user->supplier_id)
            ->with('supplier')
            ->get()
            ->keyBy('id');

        if ($purchaseOrders->isEmpty()) {
            return back()->with('danger', 'No valid purchase orders.')->withInput();
        }

        $ineligible = SupplierMultiPoSupport::ineligiblePoSummary($purchaseOrders);
        if (! empty($ineligible)) {
            $summary = collect($ineligible)
                ->map(fn ($date, $pono) => $pono . ' (eligible on ' . $date . ')')
                ->implode(', ');

            return back()->with('danger', 'Invoice cannot be updated yet for: ' . $summary . '.')->withInput();
        }

        $pbTotal = (float) ($request->pbTotal ?? 0);
        if (SupplierMultiPoSupport::requiresConsumableEway($purchaseOrders, $pbTotal)) {
            $ewayBillNo = trim((string) $request->input('ewaybill', ''));
            if ($ewayBillNo === '') {
                $threshold = SupplierMultiPoSupport::consumableEwayThreshold($purchaseOrders);

                return back()->withErrors([
                    'ewaybill' => 'E-Way Bill number is required when invoice total exceeds ' . number_format($threshold, 0, '.', ',') . '.',
                ])->withInput();
            }
        }

        $supplierId = $user->supplier_id;
        $supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
        $eway_bill_no = strtoupper($request->input('ewaybill', ''));

        $errors = $this->validateInvoiceNumbers($supplierId, $supplier_invoice_number, $eway_bill_no, (int) $id);
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        if ($request->hasFile('eway_bill_upload')) {
            $supplierInvoice->eway_bill_pdf = basename(
                Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload'))
            );
        }

        $this->restoreConsumableInvoiceLines((int) $supplierInvoice->id);

        $monthEndLineError = $this->validateMonthEndInvoiceLines($request->pb ?? [], $purchaseOrders);
        if ($monthEndLineError !== null) {
            return back()->with('danger', $monthEndLineError)->withInput();
        }

        $supplierInvoice->supplier_invoice_number = $supplier_invoice_number;
        $supplierInvoice->eway_bill_no = $eway_bill_no;
        $supplierInvoice->vehicle_no = strtoupper($request->input('vehicle_no', ''));
        $supplierInvoice->tamount = $request->pbTotal;
        $supplierInvoice->tquantity = $request->pbQty;
        $supplierInvoice->subTotal = $request->pbSubTotal;
        $supplierInvoice->tgst = $request->pbGST;
        $supplierInvoice->tdsTotal = $request->tdsTotal ?? 0;
        $supplierInvoice->roundoff = $request->roundoff ?? 0;
        $supplierInvoice->invoice_date = $request->invoice_date ?? $supplierInvoice->invoice_date;
        $supplierInvoice->purchase_order_id = implode(',', $selectedPOIds);
        $supplierInvoice->mulitple_po = $purchaseOrders->pluck('pono')->implode(',');
        $supplierInvoice->save();

        foreach ($request->pb ?? [] as $pb) {
            $receiveQty = (float) ($pb['receiveqty'] ?? 0);
            if ($receiveQty <= 0) {
                continue;
            }

            $poid = (int) ($pb['poid'] ?? 0);
            $productId = (int) ($pb['product'] ?? 0);
            $po = $purchaseOrders->get($poid);
            if (! $po) {
                continue;
            }

            $gst = $pb['gstslab'] ?? 0;
            $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb);
            $pocLine = ConsumableMonthEndInvoiceSupport::resolvePocLine($po, $productId, $pocLineId);
            $lineAmount = ConsumableMonthEndInvoiceSupport::soLineAmountFromRequest($po, $pb, $pocLine);
            $total = ConsumableMonthEndInvoiceSupport::soLineTotalFromRequest($po, $pb, $pocLine);

            $soAttrs = [
                'product_id' => $productId,
                'supplier_invoice_id' => $supplierInvoice->id,
                'purchase_order_id' => $poid,
                'gst' => $gst,
                'amount' => $lineAmount,
                'total' => $total,
                'quantity' => $receiveQty,
                'mulitple_po' => $poid,
            ];

            if ($pocLineId !== null && ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()) {
                $soAttrs['poc_table_id'] = $pocLineId;
            }

            $p = soTable::create($soAttrs);
            if ((int) ($request->typeUnit ?? 2) === 2 && ! empty($pb['unit'])) {
                $p->unit = $pb['unit'];
                $p->save();
            }

            $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine($po, $productId, $pocLineId);
            if (! $poProduct) {
                return back()->with('danger', 'Purchase order line not found for one of the products.')->withInput();
            }
            $poProduct->remqty -= $receiveQty;
            $poProduct->save();
        }

        ConsumableMonthEndInvoiceSupport::applyMonthEndHeaderToInvoice(
            $supplierInvoice,
            $request->pb ?? [],
            $purchaseOrders
        );

        foreach ($selectedPOIds as $poid) {
            $rem = (float) pocTable::where('poid', $poid)->sum('remqty');
            purchaseOrderConsumable::where('id', $poid)->update([
                'remqty' => $rem,
                'status' => $rem == 0 ? 1 : 0,
            ]);
        }

        return redirect(url('/supplier-dashboard/invoice-orders/multi-consumable'))
            ->with('success', 'Invoice updated successfully!');
    }

    public function invoiceOrders()
    {
        $user = auth()->user();
        if (! $user->hasRole('supplier')) {
            abort(403);
        }

        $invoiceOrders = supplierInvoice::where('user_id', $user->id)
            ->whereNotNull('mulitple_po')
            ->where('purchase_order_type', 'Consumable')
            ->orderByDesc('created_at')
            ->get();

        return view('supplierUser.invoiceOrdersMultiConsumable', [
            'supplierInvoice' => $invoiceOrders,
        ]);
    }

    public function cancelInvoice($id)
    {
        $supplierInvoice = supplierInvoice::where('id', $id)
            ->where('is_approved', 0)
            ->where('purchase_order_type', 'Consumable')
            ->whereNotNull('mulitple_po')
            ->first();

        if (! $supplierInvoice) {
            return redirect(url('/supplier-dashboard/invoice-orders/multi-consumable'))
                ->with('danger', 'Invoice not found or cannot be cancelled.');
        }

        $supplierInvoice->status = 2;
        $supplierInvoice->save();

        $lines = soTable::where('supplier_invoice_id', $id)->get();
        foreach ($lines as $st) {
            $po = purchaseOrderConsumable::find($st->purchase_order_id);
            if (! $po || (int) $po->type !== 1) {
                continue;
            }
            $pocLineId = ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()
                ? (int) ($st->poc_table_id ?? 0)
                : null;
            $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                $po,
                (int) $st->product_id,
                $pocLineId > 0 ? $pocLineId : null
            );
            if ($poProduct) {
                $poProduct->remqty += (float) $st->quantity;
                $poProduct->save();
            }
        }

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
        foreach ($poIds as $poid) {
            $rem = (float) pocTable::where('poid', $poid)->sum('remqty');
            purchaseOrderConsumable::where('id', $poid)->update([
                'remqty' => $rem,
                'status' => $rem == 0 ? 1 : 0,
            ]);
        }

        return redirect(url('/supplier-dashboard/invoice-orders/multi-consumable'))
            ->with('success', 'Invoice has been cancelled!');
    }

    public function eligiblePOs(Request $request)
    {
        $exclude = collect(explode(',', (string) $request->query('exclude')))
            ->filter()
            ->map(fn ($id) => SupplierMultiPoSupport::normalizeConsumablePoId($id))
            ->all();

        $supplierId = optional(Auth::user())->supplier_id;

        $query = purchaseOrderConsumable::query()
            ->where('supplier_status', 1)
            ->where('type', 1)
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when(! empty($exclude), fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderByDesc('podate')
            ->select(['id', 'pono', 'ref_supplier', 'del_date', 'tquantity', 'tamount']);

        return response()->json($query->limit(200)->get());
    }

    public function poDetail($id)
    {
        $poId = SupplierMultiPoSupport::normalizeConsumablePoId($id);
        $po = purchaseOrderConsumable::where('id', $poId)->where('type', 1)->firstOrFail();

        if (Auth::check() && Auth::user()->supplier_id && Auth::user()->supplier_id !== $po->supplier_id) {
            abort(403);
        }

        $unitTypes = UnitType::all()->keyBy('name');
        $rows = pocTable::where('poid', $po->id)
            ->with(['consumable.unitType', 'product'])
            ->get()
            ->map(function ($r) use ($unitTypes, $po) {
                $unitName = $r->unit ?? '';
                $dataType = $unitTypes[$unitName]->data_type ?? 'float';

                return [
                    'poid' => $r->poid,
                    'pono' => $po->pono,
                    'poc_table_id' => $r->id,
                    'product_id' => $r->consumable_id ?? $r->product_id,
                    'product_name' => optional($r->consumable)->name ?? optional($r->product)->name,
                    'product_code' => optional($r->product)->code,
                    'description' => $r->description,
                    'remqty' => (float) $r->remqty,
                    'quantity' => (float) $r->quantity,
                    'amount' => (float) $r->amount,
                    'gstamount' => (float) $r->gstamount,
                    'rate' => (float) $r->rate,
                    'gstslab' => (float) $r->gstslab,
                    'unit' => optional($r->consumable->unitType)->name ?? $unitName,
                    'unit_label' => $unitName,
                    'data_type' => $dataType,
                    'address_option' => (int) ($po->address_option ?? 0),
                ];
            })->values();

        return response()->json([
            'po' => [
                'id' => $po->id,
                'pono' => $po->pono,
                'ref_supplier' => $po->ref_supplier,
                'del_date' => $po->del_date,
                'subTotal' => (float) $po->subTotal,
                'tquantity' => (float) $po->tquantity,
                'tamount' => (float) $po->tamount,
                'address_option' => (int) ($po->address_option ?? 0),
                'remarks' => (string) ($po->remarks ?? ''),
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * Same per-line metadata as single-PO consumable raise (unit data_type for int/float qty).
     */
    protected function attachConsumableLineMeta($poTables, $purchaseOrders)
    {
        $unitTypes = UnitType::all()->keyBy('name');
        $addressByPo = $purchaseOrders->pluck('address_option', 'id');

        foreach ($poTables as $key => $row) {
            $unitName = $row->unit ?? '';
            $poTables[$key]->data_type = isset($unitTypes[$unitName])
                ? $unitTypes[$unitName]->data_type
                : 'unknown';
            $poTables[$key]->po_address_option = (int) ($addressByPo[$row->poid] ?? 0);
        }

        return $poTables;
    }

    /**
     * M-series month-end POs need poc_table_id on each invoiced line when the column exists.
     */
    protected function validateMonthEndInvoiceLines(array $lines, $purchaseOrders): ?string
    {
        if (! ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()) {
            return null;
        }

        foreach ($lines as $pb) {
            $receiveQty = (float) ($pb['receiveqty'] ?? 0);
            if ($receiveQty <= 0) {
                continue;
            }

            $poid = (int) ($pb['poid'] ?? 0);
            $po = $purchaseOrders->get($poid);
            if (! $po || ! ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)) {
                continue;
            }

            if (ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb) === null) {
                return 'Each M-series line must be raised separately. Refresh the page and enter quantity on one furniture line at a time.';
            }
        }

        return null;
    }

    protected function findEditableMultiConsumableInvoice(int $id, $user): supplierInvoice
    {
        $invoice = supplierInvoice::where('id', $id)
            ->where('purchase_order_type', 'Consumable')
            ->whereNotNull('mulitple_po')
            ->where('is_approved', 0)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->first();

        if (! $invoice) {
            abort(404, 'Invoice not found or cannot be edited.');
        }

        if ($user->supplier_id && (int) $invoice->supplier_id !== (int) $user->supplier_id) {
            abort(403);
        }

        return $invoice;
    }

    protected function attachInvoiceQtyToPocLines($poTables, int $invoiceId, $purchaseOrders)
    {
        $hasPocColumn = ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId();

        foreach ($poTables as $key => $row) {
            $po = $purchaseOrders->get($row->poid);
            $productId = (int) ($row->consumable_id ?? $row->product_id);
            $sip = null;

            if ($po && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po) && $hasPocColumn) {
                $sip = soTable::query()
                    ->where('supplier_invoice_id', $invoiceId)
                    ->where('purchase_order_id', (int) $row->poid)
                    ->where('poc_table_id', (int) $row->id)
                    ->first();
            } else {
                $sip = ConsumableMonthEndInvoiceSupport::resolveSoLine(
                    $invoiceId,
                    (int) $row->poid,
                    $productId,
                    null,
                    $po
                );
            }

            $invQty = $sip ? (float) $sip->quantity : 0.0;
            $poTables[$key]->invoice_qty = $invQty;
            $poTables[$key]->max_receive = (float) $row->remqty + $invQty;
            $poTables[$key]->prefill_amount = $sip ? (float) $sip->amount : 0.0;
        }

        return $poTables;
    }

    protected function restoreConsumableInvoiceLines(int $invoiceId): void
    {
        foreach (soTable::where('supplier_invoice_id', $invoiceId)->get() as $entry) {
            $po = purchaseOrderConsumable::find($entry->purchase_order_id);
            if (! $po || (int) $po->type !== 1) {
                $entry->delete();
                continue;
            }

            $pocLineId = ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()
                ? (int) ($entry->poc_table_id ?? 0)
                : null;
            $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                $po,
                (int) $entry->product_id,
                $pocLineId > 0 ? $pocLineId : null
            );
            if ($poProduct) {
                $poProduct->remqty += (float) $entry->quantity;
                $poProduct->save();
            }
            $entry->delete();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function validateInvoiceNumbers(
        int $supplierId,
        string $supplierInvoiceNumber,
        string $ewayBillNo,
        int $excludeId
    ): array {
        $errors = [];
        if ($ewayBillNo !== '') {
            if (supplierInvoice::where('supplier_id', $supplierId)
                ->where('eway_bill_no', $ewayBillNo)
                ->where('id', '!=', $excludeId)
                ->where(function ($q) {
                    $q->where('status', '!=', 2)->orWhereNull('status');
                })
                ->exists()) {
                $errors['eway_bill_no'] = 'This E-way Bill number already exists for this supplier.';
            }
        }
        if (supplierInvoice::where('supplier_id', $supplierId)
            ->where('supplier_invoice_number', $supplierInvoiceNumber)
            ->where('id', '!=', $excludeId)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->exists()) {
            $errors['supplier_invoice_number'] = 'This Supplier Invoice number already exists for this supplier.';
        }

        return $errors;
    }
}
