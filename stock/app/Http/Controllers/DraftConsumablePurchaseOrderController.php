<?php

namespace App\Http\Controllers;

use App\consumable;
use App\DraftConsumablePoLine;
use App\DraftConsumablePurchaseOrder;
use App\Notification;
use App\purchaseOrderConsumable;
use App\setting;
use App\supplier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DraftConsumablePurchaseOrderController extends Controller
{
    /** Non-furniture suppliers (Consumable, Both, etc.). */
    private function consumableSuppliers()
    {
        return supplier::query()
            ->where('type', '!=', 'Furniture')
            ->orderBy('c_name')
            ->get();
    }

    private function peekNextDraftPono(): string
    {
        $settings = setting::first();
        $next = (int) ($settings->consumable_draft_po_no ?? 0) + 1;

        while (DraftConsumablePurchaseOrder::where('draft_pono', 'CDRAFT/' . $next)->exists()) {
            $next++;
        }

        return 'CDRAFT/' . $next;
    }

    private function allocateNextDraftPono(): string
    {
        $pono = $this->peekNextDraftPono();
        $settings = setting::first();
        if ($settings && Schema::hasColumn('settings', 'consumable_draft_po_no')) {
            $settings->consumable_draft_po_no = (int) str_replace('CDRAFT/', '', $pono);
            $settings->save();
        }

        return $pono;
    }

    public function index()
    {
        $drafts = DraftConsumablePurchaseOrder::with('supplier')
            ->orderByDesc('id')
            ->get();

        return view('draftConsumablePurchaseOrder.index', ['drafts' => $drafts]);
    }

    public function create()
    {
        return view('draftConsumablePurchaseOrder.form', [
            'supplier' => $this->consumableSuppliers(),
            'nextDraftPono' => $this->peekNextDraftPono(),
            'draft' => null,
            'lines' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'podate' => 'required|date',
            'del_date' => 'required|date',
            'month' => 'required|array|min:1',
            'po' => 'required|array|min:1',
            'po.*.consumable' => 'required',
            'po.*.quantity' => 'required|numeric|min:0.01',
            'po.*.unit' => 'required',
        ]);

        foreach ($request['po'] as $po) {
            if (!isset($po['unit']) || $po['unit'] === '' || $po['unit'] === null) {
                return back()->with('error', 'Unit is required for all products.')->withInput();
            }
        }

        $moqErr = $this->validateConsumablePoMoqRows($request->input('po', []));
        if ($moqErr) {
            return back()->with('error', $moqErr)->withInput();
        }

        $draft = DraftConsumablePurchaseOrder::create([
            'draft_pono' => $this->allocateNextDraftPono(),
            'supplier_id' => (int) $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'month' => implode(',', $request['month']),
            'buyer_orderno' => strtoupper((string) $request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'address_option' => $request['address_option'],
            'status' => DraftConsumablePurchaseOrder::STATUS_OPEN,
            'send_to_supplier_status' => 0,
        ]);

        $this->syncLines($draft, $request['po']);

        return redirect('/draftConsumablePurchaseOrder')->with('success', 'Consumable draft PO created successfully.');
    }

    public function edit(int $id)
    {
        $draft = DraftConsumablePurchaseOrder::with(['lines.consumable'])->findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftConsumablePurchaseOrder')->with('danger', 'Completed draft PO cannot be edited.');
        }

        return view('draftConsumablePurchaseOrder.form', [
            'supplier' => $this->consumableSuppliers(),
            'draft' => $draft,
            'lines' => $draft->lines,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $draft = DraftConsumablePurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftConsumablePurchaseOrder')->with('danger', 'Completed draft PO cannot be edited.');
        }

        $request->validate([
            'supplier_id' => 'required|integer',
            'podate' => 'required|date',
            'del_date' => 'required|date',
            'month' => 'required|array|min:1',
            'po' => 'required|array|min:1',
            'po.*.consumable' => 'required',
            'po.*.quantity' => 'required|numeric|min:0.01',
            'po.*.unit' => 'required',
        ]);

        foreach ($request['po'] as $po) {
            if (!isset($po['unit']) || $po['unit'] === '' || $po['unit'] === null) {
                return back()->with('error', 'Unit is required for all products.')->withInput();
            }
        }

        $moqErr = $this->validateConsumablePoMoqRows($request->input('po', []));
        if ($moqErr) {
            return back()->with('error', $moqErr)->withInput();
        }

        $draft->update([
            'supplier_id' => (int) $request['supplier_id'],
            'podate' => $request['podate'],
            'del_date' => $request['del_date'],
            'month' => implode(',', $request['month']),
            'buyer_orderno' => strtoupper((string) $request['buyer_orderno']),
            'payterms' => $request['payterms'],
            'remarks' => $request['remarks'],
            'subTotal' => $request['subtotalamount'],
            'tgst' => $request['tgst'],
            'tquantity' => $request['tquantity'],
            'tamount' => $request['tamount'],
            'address_option' => $request['address_option'],
        ]);

        DraftConsumablePoLine::where('draft_consumable_purchase_order_id', $draft->id)->delete();
        $this->syncLines($draft, $request['po']);

        return redirect('/draftConsumablePurchaseOrder')->with('success', 'Consumable draft PO updated successfully.');
    }

    public function sendToSupplier(int $id)
    {
        $draft = DraftConsumablePurchaseOrder::findOrFail($id);
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
                'notification' => 'Received new Consumable Draft PO - ' . $draft->draft_pono,
                'is_read' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Consumable draft PO sent to supplier.');
    }

    public function convert(int $id)
    {
        $draft = DraftConsumablePurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftConsumablePurchaseOrder')->with('danger', 'This draft is already converted.');
        }

        return redirect('/purchaseOrder/createConsumablePo?from_draft=' . $draft->id);
    }

    public function modal(int $id)
    {
        return view('draftConsumablePurchaseOrder.modal', $this->buildModalViewData($id));
    }

    public function destroy(int $id)
    {
        $draft = DraftConsumablePurchaseOrder::findOrFail($id);
        if (!$draft->isOpen()) {
            return redirect('/draftConsumablePurchaseOrder')->with('danger', 'Only open draft POs can be deleted.');
        }

        DraftConsumablePoLine::where('draft_consumable_purchase_order_id', $draft->id)->delete();
        $draft->delete();

        return redirect('/draftConsumablePurchaseOrder')->with('success', 'Consumable draft PO deleted.');
    }

    /** Supplier list — non-furniture suppliers only. */
    public function supplierIndex()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('supplier')) {
            abort(403);
        }

        $supplierModel = supplier::find($user->supplier_id);
        if (!$supplierModel || $supplierModel->type === 'Furniture') {
            return view('supplierUser.draftConsumablePurchaseOrders', ['drafts' => collect()]);
        }

        $drafts = DraftConsumablePurchaseOrder::where('supplier_id', $user->supplier_id)
            ->where('send_to_supplier_status', 1)
            ->orderByDesc('id')
            ->get();

        return view('supplierUser.draftConsumablePurchaseOrders', ['drafts' => $drafts]);
    }

    /** Supplier view/print — open drafts only. */
    public function supplierModal(int $id)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('supplier')) {
            abort(403);
        }

        $draft = DraftConsumablePurchaseOrder::with(['supplier', 'lines.consumable'])->findOrFail($id);
        if ((int) $draft->supplier_id !== (int) $user->supplier_id) {
            abort(403);
        }
        if (!$draft->isSentToSupplier()) {
            return redirect('/supplier-dashboard/draft-consumable-purchase-orders')->with('danger', 'Draft PO not available.');
        }
        if (!$draft->isOpen()) {
            return redirect('/supplier-dashboard/draft-consumable-purchase-orders')->with('danger', 'This draft PO is complete and cannot be viewed.');
        }

        return view('draftConsumablePurchaseOrder.modal', $this->buildModalViewData($id, $draft));
    }

    /**
     * @param  array<int, array<string, mixed>>  $poLines
     */
    private function syncLines(DraftConsumablePurchaseOrder $draft, array $poLines): void
    {
        foreach ($poLines as $po) {
            DraftConsumablePoLine::create([
                'draft_consumable_purchase_order_id' => $draft->id,
                'consumable_id' => (int) $po['consumable'],
                'quantity' => $po['quantity'],
                'unit' => $po['unit'] ?? '',
                'rate' => $po['rate'] ?? 0,
                'amount' => $po['amount'] ?? 0,
                'gstslab' => $po['gstslab'] ?? 0,
                'gstamount' => $po['gstamount'] ?? 0,
                'description' => $po['description'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildModalViewData(int $id, ?DraftConsumablePurchaseOrder $draft = null): array
    {
        $draft = $draft ?? DraftConsumablePurchaseOrder::with(['supplier', 'lines.consumable'])->findOrFail($id);
        $print = isset($_REQUEST['print']) && $_REQUEST['print'] == 1 ? 1 : 0;

        return [
            'draft' => $draft,
            'print' => $print,
            'companyDetails' => setting::first(),
            'fileMap1' => $this->stockFileMap(),
        ];
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
     * Mark draft complete after main consumable PO created (called from purchaseOrderController).
     */
    public static function markConverted(int $draftId, int $purchaseOrderId): void
    {
        if (!Schema::hasTable('draft_consumable_purchase_orders')) {
            return;
        }

        $draft = DraftConsumablePurchaseOrder::find($draftId);
        if (!$draft || !$draft->isOpen()) {
            return;
        }

        $draft->status = DraftConsumablePurchaseOrder::STATUS_COMPLETE;
        $draft->converted_purchase_order_id = $purchaseOrderId;
        $draft->save();
    }

    /**
     * @param array<int|string, mixed> $rows
     */
    private function validateConsumablePoMoqRows(array $rows): ?string
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

            $consumableModel = consumable::select('id', 'name', 'is_moq', 'moq_qty')->find($consumableId);
            if (!$consumableModel) {
                return "Invalid consumable selected at row {$rowNumber}.";
            }

            $isMoq = (int) ($consumableModel->is_moq ?? 0) === 1;
            if (!$isMoq) {
                continue;
            }

            $moqQty = is_numeric($consumableModel->moq_qty) ? (float) $consumableModel->moq_qty : 0.0;
            if ($moqQty <= 0) {
                return "Row {$rowNumber} ({$consumableModel->name}): MOQ is enabled but MOQ Qty is missing/invalid on consumable master.";
            }

            if ($quantity + 1e-9 < $moqQty) {
                return "Row {$rowNumber} ({$consumableModel->name}): quantity must be at least {$moqQty}.";
            }

            $ratio = $quantity / $moqQty;
            if (abs($ratio - round($ratio)) > 1e-6) {
                return "Row {$rowNumber} ({$consumableModel->name}): quantity must be in multiples of {$moqQty} (e.g. {$moqQty}, " . ($moqQty * 2) . ', ' . ($moqQty * 3) . ').';
            }
        }

        return null;
    }
}
