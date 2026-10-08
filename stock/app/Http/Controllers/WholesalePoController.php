<?php

namespace App\Http\Controllers;

use App\Exports\WholesaleImportTemplateExport;
use App\Exports\WholesaleNegativeListExport;
use App\Mail\WholesaleClientReminderMail;
use App\poTable;
use App\product;
use App\purchaseOrder;
use App\supplier;
use App\supplierProduct;
use App\Support\SendToSupplierPo;
use App\Support\WholesaleExcelImporter;
use App\WholesaleAllocation;
use App\WholesaleShipment;
use App\WholesaleShipmentItem;
use App\WholesaleShipmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class WholesalePoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
    }

    protected function denyUnlessCan(string $permission)
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Please sign in again.');
        }
        if ($user->hasRole('admin') || $user->hasRole('administrator') || $user->can($permission)) {
            return;
        }
        abort(403, 'You do not have access to Wholesale PO Management. Please ask an administrator.');
    }

    public function index(Request $request)
    {
        $this->denyUnlessCan('wholesale-po-read');

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $records = WholesaleShipment::with(['items', 'uploader'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('buyer_orderno', 'like', '%' . $search . '%');
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        return view('WholesalePo.index', compact('records', 'search', 'status'));
    }

    public function create()
    {
        $this->denyUnlessCan('wholesale-po-edit');

        return view('WholesalePo.create');
    }

    public function downloadTemplate()
    {
        $this->denyUnlessCan('wholesale-po-read');

        return Excel::download(new WholesaleImportTemplateExport(), 'wholesale_sku_qty_template.xlsx');
    }

    public function store(Request $request)
    {
        $this->denyUnlessCan('wholesale-po-edit');

        $validator = Validator::make($request->all(), [
            'buyer_orderno' => 'required|string|max:191',
            'planned_date' => 'nullable|date',
            'delivery_date' => 'required|date',
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:' . (int) config('wholesale_po.excel_max_kb', 5120),
            'notes' => 'nullable|string|max:2000',
        ], [
            'buyer_orderno.required' => 'Please enter the Buyer Order Number (for example UK-45 #154).',
            'delivery_date.required' => 'Please enter the delivery date. Reminders stop on this date.',
            'excel_file.required' => 'Please upload an Excel file with SKU and Qty columns.',
            'excel_file.mimes' => 'Only Excel/CSV files are allowed (.xlsx, .xls, .csv).',
            'excel_file.max' => 'The file is too large. Maximum size is 5 MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->filled('planned_date') && $request->filled('delivery_date')
            && $request->input('delivery_date') < $request->input('planned_date')) {
            return redirect()->back()
                ->with('error', 'Delivery date cannot be before the planned date.')
                ->withInput();
        }

        $buyerOrderno = strtoupper(trim($request->input('buyer_orderno')));
        $existing = WholesaleShipment::where('buyer_orderno', $buyerOrderno)->first();
        if ($existing) {
            return redirect()
                ->route('wholesale-po.edit', $existing->id)
                ->with('error', 'This Buyer Order Number already exists. A second upload is not allowed — please use Update on the existing record instead.');
        }

        $parsed = WholesaleExcelImporter::parse($request->file('excel_file'));
        if (! empty($parsed['errors'])) {
            return redirect()->back()
                ->with('error', implode(' ', $parsed['errors']))
                ->withInput();
        }

        $firstDays = (int) config('wholesale_po.first_reminder_days', 7);

        try {
            DB::beginTransaction();

            $storedName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $request->file('excel_file')->getClientOriginalName());
            $path = $request->file('excel_file')->storeAs(
                config('wholesale_po.upload_disk_path', 'wholesale_uploads'),
                $storedName
            );

            $shipment = WholesaleShipment::create([
                'buyer_orderno' => $buyerOrderno,
                'planned_date' => $request->input('planned_date') ?: now()->toDateString(),
                'delivery_date' => $request->input('delivery_date'),
                'status' => 'draft',
                'excel_original_name' => $request->file('excel_file')->getClientOriginalName(),
                'excel_stored_path' => $path,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
                'reminder_count' => 0,
                'next_reminder_at' => now()->addDays($firstDays)->toDateString(),
                'notes' => $request->input('notes'),
            ]);

            foreach ($parsed['rows'] as $row) {
                WholesaleShipmentItem::create([
                    'wholesale_shipment_id' => $shipment->id,
                    'sku' => $row['sku'],
                    'product_id' => $row['product_id'],
                    'qty' => $row['qty'],
                    'allocated_qty' => 0,
                ]);
            }

            WholesaleShipmentLog::record(
                $shipment->id,
                $buyerOrderno,
                'upload',
                'Uploaded Excel and created wholesale shipment with ' . count($parsed['rows']) . ' SKU line(s).',
                [
                    'file' => $shipment->excel_original_name,
                    'stored_path' => $path,
                    'sku_count' => count($parsed['rows']),
                    'total_qty' => array_sum(array_column($parsed['rows'], 'qty')),
                ],
                $request
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Wholesale PO store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()->back()
                ->with('error', 'Something went wrong while saving. Nothing was stored. Please try again or contact support. Technical detail: ' . $e->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('wholesale-po.show', $shipment->id)
            ->with('success', 'Wholesale shipment created for ' . $buyerOrderno . '. You can now allocate suppliers.');
    }

    public function edit($id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::with('items')->findOrFail($id);

        return view('WholesalePo.edit', compact('shipment'));
    }

    public function update(Request $request, $id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'planned_date' => 'nullable|date',
            'delivery_date' => 'required|date',
            'excel_file' => 'nullable|file|mimes:xlsx,xls,csv|max:' . (int) config('wholesale_po.excel_max_kb', 5120),
            'notes' => 'nullable|string|max:2000',
        ], [
            'delivery_date.required' => 'Please enter the delivery date.',
            'excel_file.mimes' => 'Only Excel/CSV files are allowed (.xlsx, .xls, .csv).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Block changing buyer order number — unique key
        $hasPendingPo = $shipment->allocations()->where('status', 'po_generated')->exists();
        if ($hasPendingPo && $request->hasFile('excel_file')) {
            return redirect()->back()->with(
                'error',
                'POs have already been raised for this buyer order. You cannot replace the Excel lines now. Create a new buyer order number if quantities change, or contact support.'
            );
        }

        $parsed = null;
        if ($request->hasFile('excel_file')) {
            $parsed = WholesaleExcelImporter::parse($request->file('excel_file'));
            if (! empty($parsed['errors'])) {
                return redirect()->back()->with('error', implode(' ', $parsed['errors']))->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $shipment->planned_date = $request->input('planned_date') ?: $shipment->planned_date;
            $shipment->delivery_date = $request->input('delivery_date');
            $shipment->notes = $request->input('notes');

            if ($parsed) {
                // Soft-delete old pending allocations & items, replace items
                WholesaleAllocation::where('wholesale_shipment_id', $shipment->id)
                    ->where('status', 'pending')
                    ->delete();

                WholesaleShipmentItem::where('wholesale_shipment_id', $shipment->id)->delete();

                $storedName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $request->file('excel_file')->getClientOriginalName());
                $path = $request->file('excel_file')->storeAs(
                    config('wholesale_po.upload_disk_path', 'wholesale_uploads'),
                    $storedName
                );

                $shipment->excel_original_name = $request->file('excel_file')->getClientOriginalName();
                $shipment->excel_stored_path = $path;
                $shipment->uploaded_by = auth()->id();
                $shipment->uploaded_at = now();
                $shipment->status = 'draft';

                foreach ($parsed['rows'] as $row) {
                    WholesaleShipmentItem::create([
                        'wholesale_shipment_id' => $shipment->id,
                        'sku' => $row['sku'],
                        'product_id' => $row['product_id'],
                        'qty' => $row['qty'],
                        'allocated_qty' => 0,
                    ]);
                }

                WholesaleShipmentLog::record(
                    $shipment->id,
                    $shipment->buyer_orderno,
                    'update_upload',
                    'Updated Excel file and replaced SKU lines (' . count($parsed['rows']) . ' lines).',
                    [
                        'file' => $shipment->excel_original_name,
                        'stored_path' => $path,
                        'sku_count' => count($parsed['rows']),
                    ],
                    $request
                );
            } else {
                WholesaleShipmentLog::record(
                    $shipment->id,
                    $shipment->buyer_orderno,
                    'update',
                    'Updated shipment dates/notes (no new Excel).',
                    [],
                    $request
                );
            }

            $shipment->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Wholesale PO update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Update failed. No changes were saved. Technical detail: ' . $e->getMessage())
                ->withInput();
        }

        return redirect()->route('wholesale-po.show', $shipment->id)
            ->with('success', 'Shipment updated successfully.');
    }

    public function show($id)
    {
        $this->denyUnlessCan('wholesale-po-read');
        $shipment = WholesaleShipment::with([
            'items.product',
            'allocations.supplier',
            'allocations.purchaseOrder',
            'uploader',
            'logs',
        ])->findOrFail($id);

        return view('WholesalePo.show', compact('shipment'));
    }

    public function logs($id)
    {
        $this->denyUnlessCan('wholesale-po-read');
        $shipment = WholesaleShipment::findOrFail($id);
        $logs = WholesaleShipmentLog::where('wholesale_shipment_id', $id)->orderByDesc('id')->paginate(50);

        return view('WholesalePo.logs', compact('shipment', 'logs'));
    }

    public function downloadUploadedFile($id)
    {
        $this->denyUnlessCan('wholesale-po-read');
        $shipment = WholesaleShipment::findOrFail($id);
        if (! $shipment->excel_stored_path || ! Storage::exists($shipment->excel_stored_path)) {
            return redirect()->back()->with('error', 'The uploaded Excel file is no longer available on the server.');
        }

        WholesaleShipmentLog::record($shipment->id, $shipment->buyer_orderno, 'download_excel', 'Downloaded original uploaded Excel.');

        return Storage::download($shipment->excel_stored_path, $shipment->excel_original_name ?: 'wholesale.xlsx');
    }

    public function allocateForm($id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::with(['items.product', 'items.allocations.supplier'])->findOrFail($id);

        $suppliers = supplier::orderBy('c_name')
            ->whereRaw('LOWER(TRIM(type)) IN (?, ?)', ['furniture', 'both'])
            ->get(['id', 'c_name']);
        if ($suppliers->isEmpty()) {
            $suppliers = supplier::orderBy('c_name')->get(['id', 'c_name']);
        }

        $skuBlocks = [];
        foreach ($shipment->items as $item) {
            $rates = supplierProduct::where('product_id', $item->product_id)
                ->with('supplier')
                ->get()
                ->keyBy('supplier_id');

            $skuBlocks[] = [
                'item' => $item,
                'rates' => $rates,
                'existing' => $item->allocations()->where('status', 'pending')->get(),
            ];
        }

        return view('WholesalePo.allocate', compact('shipment', 'suppliers', 'skuBlocks'));
    }

    public function saveAllocations(Request $request, $id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::with('items')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'alloc' => 'required|array',
            'alloc.*.item_id' => 'required|integer',
            'alloc.*.supplier_id' => 'required|integer',
            'alloc.*.qty' => 'required|integer|min:1',
        ], [
            'alloc.required' => 'Please add at least one supplier allocation line.',
            'alloc.*.qty.min' => 'Allocated quantity must be at least 1.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $itemsById = $shipment->items->keyBy('id');
        $sumByItem = [];

        foreach ($request->input('alloc', []) as $row) {
            $itemId = (int) $row['item_id'];
            if (! $itemsById->has($itemId)) {
                return redirect()->back()->with('error', 'One of the lines does not belong to this shipment. Please refresh and try again.');
            }
            $sumByItem[$itemId] = ($sumByItem[$itemId] ?? 0) + (int) $row['qty'];
        }

        foreach ($sumByItem as $itemId => $sumQty) {
            $item = $itemsById[$itemId];
            $alreadyPoQty = (int) WholesaleAllocation::where('wholesale_shipment_item_id', $itemId)
                ->where('status', 'po_generated')
                ->sum('asked_quantity');
            if ($sumQty + $alreadyPoQty > (int) $item->qty) {
                return redirect()->back()->with(
                    'error',
                    "SKU {$item->sku}: you tried to allocate {$sumQty} but only " . max(0, (int) $item->qty - $alreadyPoQty) . ' is still available (total asked ' . $item->qty . '). Please reduce quantities.'
                );
            }
        }

        try {
            DB::beginTransaction();

            WholesaleAllocation::where('wholesale_shipment_id', $shipment->id)
                ->where('status', 'pending')
                ->delete();

            foreach ($request->input('alloc', []) as $row) {
                $item = $itemsById[(int) $row['item_id']];
                $supplierId = (int) $row['supplier_id'];
                $qty = (int) $row['qty'];

                $sp = supplierProduct::where('supplier_id', $supplierId)
                    ->where('product_id', $item->product_id)
                    ->first();
                $rate = $sp ? (float) $sp->rate : 0;

                WholesaleAllocation::create([
                    'wholesale_shipment_id' => $shipment->id,
                    'wholesale_shipment_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_sku' => $item->sku,
                    'supplier_id' => $supplierId,
                    'asked_quantity' => $qty,
                    'rate' => $rate,
                    'status' => 'pending',
                ]);
            }

            foreach ($shipment->items as $item) {
                $item->allocated_qty = (int) WholesaleAllocation::where('wholesale_shipment_item_id', $item->id)
                    ->sum('asked_quantity');
                $item->save();
            }

            $shipment->status = 'allocating';
            $shipment->save();

            WholesaleShipmentLog::record(
                $shipment->id,
                $shipment->buyer_orderno,
                'allocate',
                'Saved supplier allocations (' . count($request->input('alloc')) . ' line(s)).',
                [],
                $request
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Wholesale allocate failed', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Could not save allocations. Nothing was changed. Technical detail: ' . $e->getMessage());
        }

        return redirect()->route('wholesale-po.show', $shipment->id)
            ->with('success', 'Supplier allocations saved. You can raise purchase orders next.');
    }

    public function poGenerationForm($id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::with(['allocations' => function ($q) {
            $q->where('status', 'pending')->with(['supplier', 'product']);
        }])->findOrFail($id);

        $grouped = $shipment->allocations->groupBy('supplier_id');

        return view('WholesalePo.po_generation', compact('shipment', 'grouped'));
    }

    public function generatePo(Request $request, $id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer',
            'delivery_date' => 'required|date',
        ], [
            'selected.required' => 'Please select at least one supplier group to raise a PO.',
            'delivery_date.required' => 'Please choose the delivery date for the PO(s).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $allocationIds = array_map('intval', $request->input('selected', []));
        $allocations = WholesaleAllocation::with('product')
            ->where('wholesale_shipment_id', $shipment->id)
            ->where('status', 'pending')
            ->whereIn('id', $allocationIds)
            ->get();

        if ($allocations->isEmpty()) {
            return redirect()->back()->with('error', 'No pending allocation lines were found for your selection. They may already have POs.');
        }

        $bySupplier = $allocations->groupBy('supplier_id');
        $createdPonos = [];

        try {
            DB::beginTransaction();

            foreach ($bySupplier as $supplierId => $lines) {
                $lastPo = purchaseOrder::where('supplier_id', '!=', '17')
                    ->where('address_option', '!=', 100)
                    ->where('podate', '>', '2021-03-31')
                    ->where('pono', 'not like', 'BO/%')
                    ->orderBy('id', 'desc')
                    ->first();
                $pono = $lastPo ? ((int) $lastPo->pono + 1) : 1;

                $subTotal = 0;
                $totalGst = 0;
                $totalQty = 0;
                $poItems = [];

                foreach ($lines as $line) {
                    $product = $line->product ?: product::find($line->product_id);
                    if (! $product) {
                        throw new \RuntimeException('Product missing for SKU ' . $line->product_sku);
                    }
                    $rate = (float) ($line->rate ?? 0);
                    if ($rate <= 0) {
                        $sp = supplierProduct::where('supplier_id', $supplierId)
                            ->where('product_id', $line->product_id)
                            ->first();
                        $rate = $sp ? (float) $sp->rate : 0;
                    }
                    if ($rate <= 0) {
                        throw new \RuntimeException(
                            'No price found for SKU ' . $line->product_sku . ' at the selected supplier. Please set the supplier price first.'
                        );
                    }

                    $amount = $line->asked_quantity * $rate;
                    $gst = round(($amount * (float) $product->gstslab) / 100, 2);

                    $poItems[] = [
                        'product_id' => $line->product_id,
                        'description' => 'Wholesale — ' . $shipment->buyer_orderno,
                        'ean' => $product->EAN,
                        'quantity' => $line->asked_quantity,
                        'unit' => $product->unit ?? 'No.',
                        'remqty' => $line->asked_quantity,
                        'rate' => $rate,
                        'amount' => $amount,
                        'gstslab' => $product->gstslab,
                        'gstamount' => $gst,
                        'priority' => '1',
                        'delivery_point' => 'Unit 1',
                        'legs' => '1',
                    ];

                    $subTotal += $amount;
                    $totalGst += $gst;
                    $totalQty += $line->asked_quantity;
                }

                $poData = array_merge([
                    'pono' => $pono,
                    'supplier_id' => $supplierId,
                    'podate' => $shipment->planned_date ? $shipment->planned_date->format('Y-m-d') : now()->toDateString(),
                    'del_date' => $request->input('delivery_date'),
                    'ref_supplier' => $shipment->buyer_orderno,
                    'buyer_orderno' => $shipment->buyer_orderno,
                    'payterms' => '30-45 Days',
                    'remarks' => "1. Wood must be seasoned & chemically treated.\n2. Timber MUST be sourced from regulated & legal plantations only.",
                    'tgst' => $totalGst,
                    'tquantity' => $totalQty,
                    'tamount' => $subTotal + $totalGst,
                    'subTotal' => $subTotal,
                    'remqty' => $totalQty,
                    'address_option' => 1,
                    'is_wholesale' => 1,
                    'wholesale_shipment_id' => $shipment->id,
                    'created_via' => 'WHOLESALE',
                ], SendToSupplierPo::createAttributes(1, (string) $pono, 'purchase_order'));

                $purchaseOrder = purchaseOrder::create($poData);
                foreach ($poItems as $item) {
                    $item['poid'] = $purchaseOrder->id;
                    poTable::create($item);
                }

                WholesaleAllocation::whereIn('id', $lines->pluck('id')->all())
                    ->update([
                        'status' => 'po_generated',
                        'purchase_order_id' => $purchaseOrder->id,
                    ]);

                foreach ($lines as $line) {
                    $matched = collect($poItems)->firstWhere('product_id', $line->product_id);
                    if ($matched) {
                        $line->rate = $matched['rate'];
                        $line->status = 'po_generated';
                        $line->purchase_order_id = $purchaseOrder->id;
                        $line->save();
                    }
                }

                $createdPonos[] = $pono;
            }

            $pendingLeft = WholesaleAllocation::where('wholesale_shipment_id', $shipment->id)
                ->where('status', 'pending')
                ->exists();
            $shipment->status = $pendingLeft ? 'partial' : 'po_raised';
            $shipment->save();

            WholesaleShipmentLog::record(
                $shipment->id,
                $shipment->buyer_orderno,
                'generate_po',
                'Raised PO(s): ' . implode(', ', $createdPonos),
                ['ponos' => $createdPonos],
                $request
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Wholesale generatePo failed', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->with(
                'error',
                'Purchase orders were not created. Nothing was saved. ' . $e->getMessage()
            );
        }

        return redirect()->route('wholesale-po.show', $shipment->id)
            ->with('success', 'Purchase order(s) created: ' . implode(', ', $createdPonos));
    }

    public function negativeList($id)
    {
        $this->denyUnlessCan('wholesale-po-read');
        $shipment = WholesaleShipment::findOrFail($id);

        WholesaleShipmentLog::record($shipment->id, $shipment->buyer_orderno, 'negative_list', 'Downloaded negative item list.');

        $filename = 'NegativeList-' . preg_replace('/[^A-Za-z0-9#-]+/', '_', $shipment->buyer_orderno) . '-' . date('dmy') . '.xlsx';

        return Excel::download(new WholesaleNegativeListExport($id), $filename);
    }

    public function sendReminder(Request $request, $id)
    {
        $this->denyUnlessCan('wholesale-po-remind');
        $shipment = WholesaleShipment::findOrFail($id);

        try {
            $this->dispatchReminder($shipment, true);
        } catch (\Throwable $e) {
            \Log::error('Wholesale manual reminder failed', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Reminder email could not be sent. ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Reminder email sent to Harsh and Anu Jain.');
    }

    public function markClosed(Request $request, $id)
    {
        $this->denyUnlessCan('wholesale-po-edit');
        $shipment = WholesaleShipment::findOrFail($id);
        $shipment->status = 'closed';
        $shipment->next_reminder_at = null;
        $shipment->save();

        WholesaleShipmentLog::record($shipment->id, $shipment->buyer_orderno, 'close', 'Shipment marked closed. Reminders stopped.', [], $request);

        return redirect()->back()->with('success', 'Shipment marked as closed. Automatic reminders will stop.');
    }

    /**
     * Build remaining lines and email client recipients.
     */
    public function dispatchReminder(WholesaleShipment $shipment, bool $manual = false): void
    {
        $shipment->load(['allocations.supplier', 'allocations.product', 'allocations.purchaseOrder.poTable']);

        $lines = [];
        foreach ($shipment->allocations->where('status', 'po_generated') as $alloc) {
            $po = $alloc->purchaseOrder;
            $poLine = $po ? $po->poTable->firstWhere('product_id', $alloc->product_id) : null;
            $rem = $poLine ? (int) $poLine->remqty : (int) $alloc->asked_quantity;
            if ($rem <= 0) {
                continue;
            }
            $lines[] = [
                'sku' => $alloc->product_sku,
                'product' => $alloc->product->name ?? '',
                'supplier' => $alloc->supplier->c_name ?? '',
                'asked' => (int) $alloc->asked_quantity,
                'remaining' => $rem,
            ];
        }

        // If no PO yet, remind about unallocated / full asked qty
        if (empty($lines)) {
            foreach ($shipment->items as $item) {
                $lines[] = [
                    'sku' => $item->sku,
                    'product' => optional($item->product)->name ?? '',
                    'supplier' => '— (PO not raised / not allocated)',
                    'asked' => (int) $item->qty,
                    'remaining' => (int) $item->qty - (int) $item->allocated_qty,
                ];
            }
        }

        $recipients = config('wholesale_po.reminder_recipients', []);
        if (empty($recipients)) {
            throw new \RuntimeException('Reminder email addresses are not configured.');
        }

        $payload = [
            'buyer_orderno' => $shipment->buyer_orderno,
            'delivery_date' => $shipment->delivery_date ? $shipment->delivery_date->format('d M Y') : '—',
            'lines' => $lines,
            'recipients' => $recipients,
            'view_url' => url('/wholesale-po/view/' . $shipment->id),
        ];

        Mail::send(new WholesaleClientReminderMail($payload));

        $repeatDays = (int) config('wholesale_po.repeat_reminder_days', 3);
        $shipment->last_reminder_at = now();
        $shipment->reminder_count = (int) $shipment->reminder_count + 1;

        $next = now()->addDays($repeatDays)->toDateString();
        if ($shipment->delivery_date && $next > $shipment->delivery_date->format('Y-m-d')) {
            $shipment->next_reminder_at = null;
        } else {
            $shipment->next_reminder_at = $next;
        }
        $shipment->save();

        WholesaleShipmentLog::record(
            $shipment->id,
            $shipment->buyer_orderno,
            $manual ? 'reminder_manual' : 'reminder_auto',
            ($manual ? 'Manual' : 'Automatic') . ' client reminder sent (#' . $shipment->reminder_count . ').',
            ['recipients' => array_column($recipients, 'email'), 'line_count' => count($lines)]
        );
    }
}
