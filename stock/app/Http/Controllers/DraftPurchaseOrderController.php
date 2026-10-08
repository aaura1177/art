<?php

namespace App\Http\Controllers;

use App\DraftConsumablePurchaseOrder;
use App\DraftPoLine;
use App\DraftPurchaseOrder;
use App\Notification;
use App\product;
use App\purchaseOrder;
use App\setting;
use App\supplier;
use App\Support\SendToSupplierPo;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DraftPurchaseOrderController extends Controller
{
    /** Furniture + Both suppliers only. */
    private function furnitureSuppliers()
    {
        return supplier::query()
            ->whereIn('type', ['Furniture', 'Both'])
            ->orderBy('c_name')
            ->get();
    }

    private function peekNextDraftPono(): string
    {
        $settings = setting::first();
        $next = (int) ($settings->draft_po_no ?? 0) + 1;

        while (DraftPurchaseOrder::where('draft_pono', 'DRAFT/' . $next)->exists()) {
            $next++;
        }

        return 'DRAFT/' . $next;
    }

    private function allocateNextDraftPono(): string
    {
        $pono = $this->peekNextDraftPono();
        $settings = setting::first();
        if ($settings && Schema::hasColumn('settings', 'draft_po_no')) {
            $settings->draft_po_no = (int) str_replace('DRAFT/', '', $pono);
            $settings->save();
        }

        return $pono;
    }

    public function index()
    {
        $drafts = DraftPurchaseOrder::with('supplier')
            ->orderByDesc('id')
            ->get();

        return view('draftPurchaseOrder.index', ['drafts' => $drafts]);
    }

    public function create()
    {
        return view('draftPurchaseOrder.form', [
            'supplier' => $this->furnitureSuppliers(),
            'nextDraftPono' => $this->peekNextDraftPono(),
            'draft' => null,
            'lines' => collect(),
        ]);
    }

    /**
     * Monthly open draft-PO amounts for a supplier (drafts only; not real POs).
     * Used by furniture + consumable draft create/update confirm popup.
     */
    public function supplierMonthlyDraftAmounts(Request $request)
    {
        $supplierId = (int) $request->input('supplier_id');
        $podate = $request->input('podate');
        $poKind = (string) $request->input('po_kind', 'furniture');
        $thisAmount = (float) $request->input('amount', 0);
        $excludeFurnitureId = $request->input('exclude_furniture_draft_id')
            ? (int) $request->input('exclude_furniture_draft_id')
            : null;
        $excludeConsumableId = $request->input('exclude_consumable_draft_id')
            ? (int) $request->input('exclude_consumable_draft_id')
            : null;

        if ($supplierId < 1 || empty($podate)) {
            return response()->json(['message' => 'Supplier and PO date are required.'], 422);
        }

        $supplier = supplier::find($supplierId);
        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $podateCarbon = Carbon::parse($podate);
        $dateFrom = $podateCarbon->copy()->startOfMonth()->toDateString();
        $dateTo = $podateCarbon->copy()->endOfMonth()->toDateString();

        $furnitureUsed = (float) DraftPurchaseOrder::where('supplier_id', $supplierId)
            ->whereBetween('podate', [$dateFrom, $dateTo])
            ->where('status', DraftPurchaseOrder::STATUS_OPEN)
            ->when($excludeFurnitureId, function ($q) use ($excludeFurnitureId) {
                $q->where('id', '!=', $excludeFurnitureId);
            })
            ->sum('subTotal');

        $consumableUsed = (float) DraftConsumablePurchaseOrder::where('supplier_id', $supplierId)
            ->whereBetween('podate', [$dateFrom, $dateTo])
            ->where('status', DraftConsumablePurchaseOrder::STATUS_OPEN)
            ->when($excludeConsumableId, function ($q) use ($excludeConsumableId) {
                $q->where('id', '!=', $excludeConsumableId);
            })
            ->sum('subTotal');

        if ($poKind === 'furniture') {
            $furnitureUsed += $thisAmount;
        } elseif ($poKind === 'consumable') {
            $consumableUsed += $thisAmount;
        }

        $supplierType = (string) ($supplier->type ?? '');
        $showBoth = $supplierType === 'Both';
        $showFurniture = $showBoth || $poKind === 'furniture';
        $showConsumable = $showBoth || $poKind === 'consumable';

        return response()->json([
            'supplier_id' => $supplierId,
            'supplier_name' => $supplier->c_name,
            'supplier_type' => $supplierType,
            'po_kind' => $poKind,
            'this_po' => round($thisAmount, 2),
            'furniture_amount' => round($furnitureUsed, 2),
            'consumable_amount' => round($consumableUsed, 2),
            'total_amount' => round($furnitureUsed + $consumableUsed, 2),
            'show_both' => $showBoth,
            'show_furniture' => $showFurniture,
            'show_consumable' => $showConsumable,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'month_label' => $podateCarbon->format('M Y'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'podate' => 'required|date',
            'del_date' => 'required|date',
            'po' => 'required|array|min:1',
            'po.*.product' => 'required',
            'po.*.quantity' => 'required|numeric|min:1',
        ]);

        $draft = DraftPurchaseOrder::create([
            'draft_pono' => $this->allocateNextDraftPono(),
            'supplier_id' => (int) $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'ref_supplier' => strtoupper((string) $request['ref_supplier']),
            'buyer_orderno' => strtoupper((string) $request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'totaldiscount' => $request['totaldiscount'] ?? 0,
            'address_option' => $request['address_option'],
            'status' => DraftPurchaseOrder::STATUS_OPEN,
            'send_to_supplier_status' => 0,
        ]);

        $this->syncLines($draft, $request['po']);

        return redirect('/draftPurchaseOrder')->with('success', 'Draft PO created successfully.');
    }

    public function edit(int $id)
    {
        $draft = DraftPurchaseOrder::with(['lines.product'])->findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftPurchaseOrder')->with('danger', 'Completed draft PO cannot be edited.');
        }

        return view('draftPurchaseOrder.form', [
            'supplier' => $this->furnitureSuppliers(),
            'draft' => $draft,
            'lines' => $draft->lines,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $draft = DraftPurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftPurchaseOrder')->with('danger', 'Completed draft PO cannot be edited.');
        }

        $request->validate([
            'supplier_id' => 'required|integer',
            'podate' => 'required|date',
            'del_date' => 'required|date',
            'po' => 'required|array|min:1',
            'po.*.product' => 'required',
            'po.*.quantity' => 'required|numeric|min:1',
        ]);

        $draft->update([
            'supplier_id' => (int) $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'ref_supplier' => strtoupper((string) $request['ref_supplier']),
            'buyer_orderno' => strtoupper((string) $request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'totaldiscount' => $request['totaldiscount'] ?? 0,
            'address_option' => $request['address_option'],
        ]);

        DraftPoLine::where('draft_purchase_order_id', $draft->id)->delete();
        $this->syncLines($draft, $request['po']);

        return redirect('/draftPurchaseOrder')->with('success', 'Draft PO updated successfully.');
    }

    public function sendToSupplier(int $id)
    {
        $draft = DraftPurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect()->back()->with('danger', 'Completed draft PO cannot be sent.');
        }
        if ($draft->isSentToSupplier()) {
            return redirect()->back()->with('info', 'Draft PO is already sent to supplier.');
        }

        $draft->send_to_supplier_status = 1;
        $draft->save();

        $user = User::where('supplier_id', $draft->supplier_id)->first();
        if ($user) {
            Notification::create([
                'user_id' => $user->id,
                'notification' => 'Received new Draft PO - ' . $draft->draft_pono,
                'is_read' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Draft PO sent to supplier.');
    }

    public function convert(int $id)
    {
        $draft = DraftPurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftPurchaseOrder')->with('danger', 'This draft is already converted.');
        }

        return redirect('/purchaseOrder/create?from_draft=' . $draft->id);
    }

    public function modal(int $id)
    {
        return view('draftPurchaseOrder.modal', $this->buildModalViewData($id));
    }

    public function destroy(int $id)
    {
        $draft = DraftPurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftPurchaseOrder')->with('danger', 'Only open draft POs can be deleted.');
        }

        DraftPoLine::where('draft_purchase_order_id', $draft->id)->delete();
        $draft->delete();

        return redirect('/draftPurchaseOrder')->with('success', 'Draft PO deleted.');
    }

    /** Supplier list — furniture / both only. */
    public function supplierIndex()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('supplier')) {
            abort(403);
        }

        $supplierModel = supplier::find($user->supplier_id);
        if (!$supplierModel || !in_array($supplierModel->type, ['Furniture', 'Both'], true)) {
            return view('supplierUser.draftPurchaseOrders', ['drafts' => collect()]);
        }

        $drafts = DraftPurchaseOrder::where('supplier_id', $user->supplier_id)
            ->where('send_to_supplier_status', 1)
            ->orderByDesc('id')
            ->get();

        return view('supplierUser.draftPurchaseOrders', ['drafts' => $drafts]);
    }

    /** Supplier view/print — open drafts only. */
    public function supplierModal(int $id)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('supplier')) {
            abort(403);
        }

        $draft = DraftPurchaseOrder::with(['supplier', 'lines.product'])->findOrFail($id);
        if ((int) $draft->supplier_id !== (int) $user->supplier_id) {
            abort(403);
        }
        if (!$draft->isSentToSupplier()) {
            return redirect('/supplier-dashboard/draft-purchase-orders')->with('danger', 'Draft PO not available.');
        }
        if (!$draft->isOpen()) {
            return redirect('/supplier-dashboard/draft-purchase-orders')->with('danger', 'This draft PO is complete and cannot be viewed.');
        }

        return view('draftPurchaseOrder.modal', $this->buildModalViewData($id, $draft));
    }

    /**
     * @param  array<int, array<string, mixed>>  $poLines
     */
    private function syncLines(DraftPurchaseOrder $draft, array $poLines): void
    {
        foreach ($poLines as $po) {
            $remainingDiscount = 0;
            if (($po['discount_type'] ?? '') === 'Amount') {
                $remainingDiscount = (float) ($po['discount'] ?? 0);
            } else {
                $remainingDiscount = ((float) ($po['amount'] ?? 0) * (float) ($po['discount'] ?? 0)) / 100;
            }

            DraftPoLine::create([
                'draft_purchase_order_id' => $draft->id,
                'product_id' => (int) $po['product'],
                'EAN' => $po['EAN'] ?? '',
                'quantity' => (int) $po['quantity'],
                'unit' => $po['unit'] ?? 'No.',
                'rate' => $po['rate'] ?? 0,
                'amount' => $po['amount'] ?? 0,
                'gstslab' => $po['gstslab'] ?? 0,
                'gstamount' => $po['gstamount'] ?? 0,
                'priority' => $po['priority'] ?? null,
                'delivery_point' => $po['delivery_point'] ?? null,
                'legs' => $po['legs'] ?? null,
                'discount' => $po['discount'] ?? 0,
                'discount_type' => $po['discount_type'] ?? 'Per',
                'remaining_discount' => $remainingDiscount,
                'description' => $po['description'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildModalViewData(int $id, ?DraftPurchaseOrder $draft = null): array
    {
        $draft = $draft ?? DraftPurchaseOrder::with(['supplier', 'lines.product'])->findOrFail($id);
        $print = isset($_REQUEST['print']) && $_REQUEST['print'] == 1 ? 1 : 0;

        return [
            'draft' => $draft,
            'print' => $print,
            'companyDetails' => setting::first(),
            'fileMap' => $this->productFileMap(),
            'fileMap1' => $this->stockFileMap(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function productFileMap(): array
    {
        $fileMap = [];
        try {
            foreach (Storage::disk('s3')->files('stock/product') as $file) {
                $filename = basename($file);
                $fileMap[$filename] = Storage::disk('s3')->url($file);
            }
        } catch (\Throwable $e) {
            // S3 optional for modal
        }

        return $fileMap;
    }

    /**
     * @return array<string, string>
     */
    private function stockFileMap(): array
    {
        $fileMap1 = [];
        try {
            foreach (Storage::disk('s3')->files('stock') as $file) {
                $filename = basename($file);
                $fileMap1[$filename] = Storage::disk('s3')->url($file);
            }
        } catch (\Throwable $e) {
            // S3 optional for modal
        }

        return $fileMap1;
    }

    /**
     * Mark draft complete after main PO created (called from purchaseOrderController).
     */
    public static function markConverted(int $draftId, int $purchaseOrderId): void
    {
        if (!Schema::hasTable('draft_purchase_orders')) {
            return;
        }

        $draft = DraftPurchaseOrder::find($draftId);
        if (!$draft || !$draft->isOpen()) {
            return;
        }

        $draft->status = DraftPurchaseOrder::STATUS_COMPLETE;
        $draft->converted_purchase_order_id = $purchaseOrderId;
        $draft->save();
    }
}
