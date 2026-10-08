<?php

namespace App\Http\Controllers;

use App\Notification;
use App\popTable;
use App\purchaseOrderConsumable;
use App\soTable;
use App\supplierInvoice;
use App\Support\CartonProductLabel;
use App\Support\SupplierMultiPoSupport;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupplierMultiPoCartonController extends Controller
{
    public function acceptedPurchaseOrdersSingle()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('supplier')) {
            abort(403);
        }

        $purchaseOrder = purchaseOrderConsumable::where('supplier_id', $user->supplier_id)
            ->where('supplier_status', 1)
            ->where('type', 2)
            ->with(['popTable.product'])
            ->orderByDesc('created_at')
            ->get();

        return view('supplierUser.acceptedPurchaseOrdersCarton', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function acceptedPurchaseOrders()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('supplier')) {
            abort(403);
        }

        $purchaseOrder = purchaseOrderConsumable::where('supplier_id', $user->supplier_id)
            ->where('supplier_status', 1)
            ->where('type', 2)
            ->with(['popTable.product'])
            ->orderByDesc('created_at')
            ->get();

        return view('supplierUser.acceptedPurchaseOrdersCartonMulti', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function raiseInvoice(Request $request)
    {
        $request->validate(['selected_po_ids' => 'required|string']);

        if ($request->isMethod('post')) {
            return redirect()->to(
                url('/supplier-dashboard/raise-invoice/multiple-carton') . '?' . http_build_query([
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
            ->where('type', 2)
            ->where('supplier_id', $supplierId)
            ->with('supplier')
            ->get();

        if ($purchaseOrders->count() !== count($selectedPOIds)) {
            return redirect()->back()->with('error', 'One or more purchase orders were not found or are not carton POs.');
        }

        $ineligible = SupplierMultiPoSupport::ineligiblePoSummary($purchaseOrders);
        if (! empty($ineligible)) {
            $summary = collect($ineligible)
                ->map(fn ($date, $pono) => $pono . ' (eligible on ' . $date . ')')
                ->implode(', ');

            return redirect()->back()->with('danger', 'Invoice cannot be generated yet for: ' . $summary . '.');
        }

        $poTables = popTable::whereIn('poid', $selectedPOIds)
            ->with(['purchaseOrderTable', 'product'])
            ->get();

        $user = auth()->user()->id;
        $invoiceTotal = supplierInvoice::where('user_id', $user)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->whereDate('invoice_date', now()->toDateString())
            ->sum('tamount');

        $userSplier = User::where('id', $user)->with('supplier')->first();

        return view('supplierUser.raiseInvoiceMultipleCarton', [
            'purchaseOrders' => $purchaseOrders,
            'poTable' => $poTables,
            'type' => 3,
            'invoiceTotal' => $invoiceTotal,
            'userSplier' => $userSplier,
            'selectedPOIds' => $selectedPOIds,
            'supplierNames' => $purchaseOrders->pluck('supplier.c_name')->unique()->values(),
            'poNumbers' => $purchaseOrders->pluck('pono')->unique()->values(),
            'deliveryDates' => $purchaseOrders->pluck('del_date')->unique()->values(),
            'supplierRefs' => $purchaseOrders->pluck('ref_supplier')->unique()->values(),
            'ewayThreshold' => 100000,
        ]);
    }

    public function submitInvoice(Request $request)
    {
        $user = auth()->user();
        $userSplier = User::where('id', $user->id)->first();
        $selectedPOIds = SupplierMultiPoSupport::normalizeConsumablePoIds($request->input('selectedPOIds', ''));
        $supplierId = $userSplier->supplier_id;

        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $selectedPOIds)
            ->where('type', 2)
            ->where('supplier_id', $supplierId)
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
        if ($pbTotal > 100000) {
            $ewayBillNo = trim((string) $request->input('ewaybill', ''));
            if ($ewayBillNo === '') {
                return back()->withErrors([
                    'ewaybill' => 'E-Way Bill number is required when invoice total exceeds 1,00,000.',
                ])->withInput();
            }
        }

        $supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
        $eway_bill_no = strtoupper($request->input('ewaybill', ''));
        $vehicle_no = strtoupper($request->input('vehicle_no', ''));

        $errors = [];
        if ($eway_bill_no !== '') {
            if (supplierInvoice::where('supplier_id', $supplierId)->where('eway_bill_no', $eway_bill_no)
                ->where(function ($q) {
                    $q->where('status', '!=', 2)->orWhereNull('status');
                })->exists()) {
                $errors['eway_bill_no'] = 'This E-way Bill number already exists for this supplier.';
            }
        }
        if (supplierInvoice::where('supplier_id', $supplierId)->where('supplier_invoice_number', $supplier_invoice_number)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })->exists()) {
            $errors['supplier_invoice_number'] = 'This Supplier Invoice number already exists for this supplier.';
        }
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $imageFileName = $request->hasFile('eway_bill_upload')
            ? basename(Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload')))
            : 'default.jpg';

        $invoiceUser = User::where('supplier_id', $supplierId)->first();
        $existingInvoice = supplierInvoice::where('supplier_invoice_number', $supplier_invoice_number)->first();
        $internal_invoice_number = $existingInvoice ? $existingInvoice->internal_invoice_number : null;

        $invoice = supplierInvoice::create([
            'purchase_order_id' => implode(',', $selectedPOIds),
            'supplier_invoice_number' => $supplier_invoice_number,
            'eway_bill_no' => $eway_bill_no,
            'vehicle_no' => $vehicle_no,
            'eway_bill_pdf' => $imageFileName,
            'supplier_id' => $invoiceUser->supplier_id,
            'user_id' => $invoiceUser->id,
            'tamount' => $request->pbTotal,
            'purchase_order_type' => 'Carton',
            'invoice_date' => $request->invoice_date,
            'tquantity' => $request->pbQty,
            'subTotal' => $request->pbSubTotal,
            'tgst' => $request->pbGST,
            'tdsTotal' => $request->tdsTotal ?? 0,
            'roundoff' => 0,
            'internal_invoice_number' => $internal_invoice_number,
            'mulitple_po' => $purchaseOrders->pluck('pono')->implode(','),
        ]);

        foreach ($request->pb ?? [] as $pb) {
            $r1 = (float) ($pb['receiveqty_box_1'] ?? 0);
            $r2 = (float) ($pb['receiveqty_box_2'] ?? 0);
            if ($r1 <= 0 && $r2 <= 0) {
                continue;
            }

            $poid = (int) ($pb['poid'] ?? 0);
            $productId = (int) ($pb['product'] ?? 0);
            $po = $purchaseOrders->get($poid);
            if (! $po) {
                continue;
            }

            $rate1 = (float) ($pb['box1_rate'] ?? 0);
            $rate2 = (float) ($pb['box2_rate'] ?? 0);
            $amount = ($rate1 * $r1) + ($rate2 * $r2);
            $gst = (float) ($pb['gstslab'] ?? 0);
            $total = $amount + ($amount * $gst / 100);

            soTable::create([
                'product_id' => $productId,
                'supplier_invoice_id' => $invoice->id,
                'purchase_order_id' => $poid,
                'gst' => $gst,
                'amount' => $amount,
                'total' => $total,
                'quantity' => $r1,
                'quantity2' => $r2,
                'mulitple_po' => $poid,
            ]);

            $pop = popTable::where('poid', $poid)->where('product_id', $productId)->first();
            if ($pop) {
                $pop->remqty_box1 -= $r1;
                $pop->remqty_box2 -= $r2;
                $pop->save();
            }
        }

        if (! $internal_invoice_number) {
            $invoice->internal_invoice_number = 'GV-' . date('Y') . '-' . $invoice->id;
            $invoice->save();
        }

        foreach ($selectedPOIds as $id) {
            $rem = popTable::where('poid', $id)->get()->sum(fn ($row) => (float) $row->remqty_box1 + (float) $row->remqty_box2);
            purchaseOrderConsumable::where('id', $id)->update([
                'remqty' => $rem,
                'status' => $rem == 0 ? 1 : 0,
            ]);
        }

        $admins = User::whereIn('role', ['Admin', 'Factory'])->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Multi-PO carton invoice (' . $invoice->mulitple_po . ') created by supplier',
                'is_read' => 0,
            ]);
        }

        return redirect(url('/supplier-dashboard/accepted-purchase-orders-carton-multi'))
            ->with('success', 'Invoice against multiple carton POs generated successfully!');
    }

    public function editInvoice($id)
    {
        $user = auth()->user();
        $supplierInvoice = $this->findEditableMultiCartonInvoice((int) $id, $user);

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $poIds)
            ->where('type', 2)
            ->where('supplier_id', $user->supplier_id)
            ->with('supplier')
            ->get()
            ->keyBy('id');

        $supplierInvoiceProduct = soTable::where('supplier_invoice_id', $supplierInvoice->id)
            ->with('product')
            ->get();

        foreach ($supplierInvoiceProduct as $key => $line) {
            $pop = popTable::where('poid', $line->purchase_order_id)
                ->where('product_id', $line->product_id)
                ->first();
            $po = $purchaseOrders->get($line->purchase_order_id);
            $supplierInvoiceProduct[$key]->pono = optional($po)->pono;
            $supplierInvoiceProduct[$key]->popTable = $pop;
            $supplierInvoiceProduct[$key]->max_box1 = (float) ($line->quantity ?? 0) + (float) ($pop->remqty_box1 ?? 0);
            $supplierInvoiceProduct[$key]->max_box2 = (float) ($line->quantity2 ?? 0) + (float) ($pop->remqty_box2 ?? 0);
        }

        $userSplier = User::where('id', $user->id)->with('supplier')->first();
        $invoiceTotal = supplierInvoice::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->whereDate('invoice_date', now()->toDateString())
            ->sum('tamount');

        return view('supplierUser.editInvoiceMultiCarton', [
            'supplierInvoice' => $supplierInvoice,
            'invoiceTotal' => $invoiceTotal,
            'supplierInvoiceProduct' => $supplierInvoiceProduct,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $purchaseOrders->first(),
            'type' => 3,
            'userSplier' => $userSplier,
            'selectedPOIds' => $poIds,
            'poNumbers' => $purchaseOrders->pluck('pono')->unique()->values(),
            'supplierNames' => $purchaseOrders->pluck('supplier.c_name')->unique()->values(),
            'deliveryDates' => $purchaseOrders->pluck('del_date')->unique()->values(),
            'supplierRefs' => $purchaseOrders->pluck('ref_supplier')->unique()->values(),
            'ewayThreshold' => 100000,
        ]);
    }

    public function updateInvoice(Request $request, $id)
    {
        $user = auth()->user();
        $supplierInvoice = $this->findEditableMultiCartonInvoice((int) $id, $user);

        $selectedPOIds = SupplierMultiPoSupport::normalizeConsumablePoIds(
            $request->input('selectedPOIds', $supplierInvoice->purchase_order_id)
        );

        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $selectedPOIds)
            ->where('type', 2)
            ->where('supplier_id', $user->supplier_id)
            ->get()
            ->keyBy('id');

        if ($purchaseOrders->isEmpty()) {
            return back()->with('danger', 'No valid purchase orders.')->withInput();
        }

        $pbTotal = (float) ($request->pbTotal ?? 0);
        if ($pbTotal > 100000 && trim((string) $request->input('ewaybill', '')) === '') {
            return back()->withErrors([
                'ewaybill' => 'E-Way Bill number is required when invoice total exceeds 1,00,000.',
            ])->withInput();
        }

        $errors = $this->validateCartonInvoiceNumbers(
            (int) $user->supplier_id,
            strtoupper($request->input('supp_inv_no', '')),
            strtoupper($request->input('ewaybill', '')),
            (int) $id
        );
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        if ($request->hasFile('eway_bill_upload')) {
            $supplierInvoice->eway_bill_pdf = basename(
                Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload'))
            );
        }

        $this->restoreCartonInvoiceLines((int) $supplierInvoice->id);

        $supplierInvoice->supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
        $supplierInvoice->eway_bill_no = strtoupper($request->input('ewaybill', ''));
        $supplierInvoice->vehicle_no = strtoupper($request->input('vehicle_no', ''));
        $supplierInvoice->tamount = $request->pbTotal;
        $supplierInvoice->tquantity = $request->pbQty;
        $supplierInvoice->subTotal = $request->pbSubTotal;
        $supplierInvoice->tgst = $request->pbGST;
        $supplierInvoice->tdsTotal = $request->tdsTotal ?? 0;
        $supplierInvoice->roundoff = 0;
        $supplierInvoice->invoice_date = $request->invoice_date ?? $supplierInvoice->invoice_date;
        $supplierInvoice->purchase_order_id = implode(',', $selectedPOIds);
        $supplierInvoice->mulitple_po = $purchaseOrders->pluck('pono')->implode(',');
        $supplierInvoice->save();

        foreach ($request->pb ?? [] as $pb) {
            $r1 = (float) ($pb['receiveqty_box_1'] ?? 0);
            $r2 = (float) ($pb['receiveqty_box_2'] ?? 0);
            if ($r1 <= 0 && $r2 <= 0) {
                continue;
            }

            $poid = (int) ($pb['poid'] ?? 0);
            $productId = (int) ($pb['product'] ?? 0);
            $po = $purchaseOrders->get($poid);
            if (! $po) {
                continue;
            }

            $rate1 = (float) ($pb['box1_rate'] ?? 0);
            $rate2 = (float) ($pb['box2_rate'] ?? 0);
            $amount = ($rate1 * $r1) + ($rate2 * $r2);
            $gst = (float) ($pb['gstslab'] ?? 0);

            soTable::create([
                'product_id' => $productId,
                'supplier_invoice_id' => $supplierInvoice->id,
                'purchase_order_id' => $poid,
                'gst' => $gst,
                'amount' => $amount,
                'total' => $amount + ($amount * $gst / 100),
                'quantity' => $r1,
                'quantity2' => $r2,
                'mulitple_po' => $poid,
            ]);

            $pop = popTable::where('poid', $poid)->where('product_id', $productId)->first();
            if ($pop) {
                $pop->remqty_box1 -= $r1;
                $pop->remqty_box2 -= $r2;
                $pop->save();
            }
        }

        foreach ($selectedPOIds as $poid) {
            $rem = popTable::where('poid', $poid)->get()->sum(fn ($row) => (float) $row->remqty_box1 + (float) $row->remqty_box2);
            purchaseOrderConsumable::where('id', $poid)->update([
                'remqty' => $rem,
                'status' => $rem == 0 ? 1 : 0,
            ]);
        }

        return redirect(url('/supplier-dashboard/invoice-orders/multi-carton'))
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
            ->where('purchase_order_type', 'Carton')
            ->orderByDesc('created_at')
            ->get();

        return view('supplierUser.invoiceOrdersMultiCarton', [
            'supplierInvoice' => $invoiceOrders,
        ]);
    }

    public function cancelInvoice($id)
    {
        $supplierInvoice = supplierInvoice::where('id', $id)
            ->where('is_approved', 0)
            ->where('purchase_order_type', 'Carton')
            ->whereNotNull('mulitple_po')
            ->first();

        if (! $supplierInvoice) {
            return redirect(url('/supplier-dashboard/invoice-orders/multi-carton'))
                ->with('danger', 'Invoice not found or cannot be cancelled.');
        }

        $supplierInvoice->status = 2;
        $supplierInvoice->save();

        foreach (soTable::where('supplier_invoice_id', $id)->get() as $st) {
            $pop = popTable::where('poid', $st->purchase_order_id)->where('product_id', $st->product_id)->first();
            if ($pop) {
                $pop->remqty_box1 += (float) $st->quantity;
                $pop->remqty_box2 += (float) $st->quantity2;
                $pop->save();
            }
        }

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
        foreach ($poIds as $poid) {
            $rem = popTable::where('poid', $poid)->get()->sum(fn ($row) => (float) $row->remqty_box1 + (float) $row->remqty_box2);
            purchaseOrderConsumable::where('id', $poid)->update([
                'remqty' => $rem,
                'status' => $rem == 0 ? 1 : 0,
            ]);
        }

        return redirect(url('/supplier-dashboard/invoice-orders/multi-carton'))
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
            ->where('type', 2)
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when(! empty($exclude), fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderByDesc('podate')
            ->select(['id', 'pono', 'ref_supplier', 'del_date', 'tquantity', 'tamount']);

        return response()->json($query->limit(200)->get());
    }

    public function poDetail($id)
    {
        $poId = SupplierMultiPoSupport::normalizeConsumablePoId($id);
        $po = purchaseOrderConsumable::where('id', $poId)->where('type', 2)->firstOrFail();

        if (Auth::check() && Auth::user()->supplier_id && Auth::user()->supplier_id !== $po->supplier_id) {
            abort(403);
        }

        $rows = popTable::where('poid', $po->id)->with('product')->get()->map(function ($r) {
            return [
                'poid' => $r->poid,
                'product_id' => $r->product_id,
                'product_name' => optional($r->product)->name,
                'product_code' => optional($r->product)->code,
                'product_label' => CartonProductLabel::fromPopTable($r),
                'box1_height' => $r->box1_height,
                'box1_width' => $r->box1_width,
                'box1_depth' => $r->box1_depth,
                'remqty_box1' => (float) $r->remqty_box1,
                'remqty_box2' => (float) $r->remqty_box2,
                'box1_rate' => (float) $r->box1_rate,
                'box2_rate' => (float) $r->box2_rate,
                'gstslab' => (float) $r->gstslab,
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
                'remarks' => (string) ($po->remarks ?? ''),
            ],
            'rows' => $rows,
        ]);
    }

    protected function findEditableMultiCartonInvoice(int $id, $user): supplierInvoice
    {
        $invoice = supplierInvoice::where('id', $id)
            ->where('purchase_order_type', 'Carton')
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

    protected function restoreCartonInvoiceLines(int $invoiceId): void
    {
        foreach (soTable::where('supplier_invoice_id', $invoiceId)->get() as $entry) {
            $pop = popTable::where('poid', $entry->purchase_order_id)
                ->where('product_id', $entry->product_id)
                ->first();
            if ($pop) {
                $pop->remqty_box1 += (float) $entry->quantity;
                $pop->remqty_box2 += (float) $entry->quantity2;
                $pop->save();
            }
            $entry->delete();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function validateCartonInvoiceNumbers(
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
