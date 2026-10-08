<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Challan;
use App\BatchProduct;
use App\Batch;

use App\StockoutRequestBackup;
use App\StockoutRequestProduct;

use App\ChallanProduct;
use App\purchaseOrderConsumable;
use App\pocTable;
use App\ProductCarton;
use App\StockLogCarton;
use App\StockOutTableCarton;
use App\packaging;
use App\User;
use App\stockoutTableConsumable;
use App\stockLogConsumable;
use App\consumable;
use App\supplierInvoice;
use App\ManualStockoutPendingConsumable;
use App\ManualStockoutPendingCarton;
use App\ErpHistory;
use App\ErpProduct;


use App\UnitType;
use App\WfConsumable;
use App\invoice;
use App\invoiceuk;

use App\invoiceTableUk;
use App\invoiceus;
use App\invoiceTableUs;
use App\invoiceeu;
use App\invoiceTableEu;
use App\buyer;
use App\Helpers\InvoicePricing;
use App\setting;
use App\settinguk;
//use App\InvoiceDischarge;
use App\settingus;
use App\settingeu;
use App\invoiceTable;
use App\packingSheet;
use App\psTable;
use App\stockLog;
use App\productLocations;
use App\contractorBill;
use App\product;
use App\contractor;
use App\finishRate;
use App\pricingTable;
use App\hardwares;
use App\hardwareSuppliers;
use App\smallhardwares;
use App\smallhardwareSupplier;
use App\stockout;
use App\stockoutTable;
use App\certificate;
use App\productSwapping;
use App\pbTable;
use App\purchaseBill;
use App\upholstreyContractor;
use App\upholstreyBill;
use App\supplier;
use App\smhsupplier;
use App\smallhardware;
use App\cornerBill;
use App\packingList;
use App\packingListProduct;
use App\Exports\ContractorBillFinishingDryRunExport;
use App\Exports\InvoiceExport;
use App\Exports\InvoiceExportSales;
use App\Exports\InvoiceExportSalesRegister;
use App\Mail\CustomInvoiceMail;
use App\ErpSheet;
use App\Services\ContractorBillFinishingDryRunBuilder;
use App\Support\ConsumableMonthEndInvoiceSupport;
use App\Support\SendToSupplierPo;
use App\Imports\ErpProductAddImportApi;
use App\Imports\ErpProductRevertImportApi;
use App\Imports\ErpProductLessImportApi;
use App\Imports\ErpRevertProductAddImportApi;


use App\Exports\EachInvoiceExport;
use App\Exports\exportsalesBill;
use App\invexport;
use App\smallHardwareProducts;
use App\CreditNote;
use App\CreditNoteProduct;
use Maatwebsite\Excel\Facades\Excel;
use Image;
use Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Collection;
use App\purchaseOrder;
use App\poTable;
use App\Port;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\invoicecalifornia;
use App\settingcanada;
use App\invoicecanada;
use App\invoiceTableCalifornia;
use App\invoiceTableCanada;
use App\settingcalifornia;
use App\ContainersAllocation;
use DB;
use Illuminate\Support\Str;


class invoiceController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['auth','2fa']);
    }

    // public function exportcsv()
    // {
    //     return Excel::download(new invoice(), 'invoice.csv');

    // }



    /**
     * Batch -> approved supplier_invoice(s) with that batch_no ->
     * sum supplier_invoice_products.ref_quantity for product_id -
     * sum product_swapping.ref_quantity where swap.reference_number matches SI.reference_number
     * (deduped by swap id, multiple swap rows included).
     */
    protected function refSipAndSwapForProductOnBatch(int $productId, string $batchNo): array
    {
        $batchNo = trim((string) $batchNo);
        if ($batchNo === '' || !$productId) {
            return ['total' => 0, 'supplier_invoice_numbers' => ''];
        }

        $sis = supplierInvoice::where('batch_no', $batchNo)
            ->where('is_approved', 1)
            ->orderBy('id')
            ->get();

        $sipSum = 0;
        $invNos = [];
        foreach ($sis as $si) {
            $sipSum += (int) DB::table('supplier_invoice_products')
                ->where('supplier_invoice_id', $si->id)
                ->where('product_id', $productId)
                ->sum(DB::raw('COALESCE(ref_quantity, 0)'));
            if (!empty($si->supplier_invoice_number)) {
                $invNos[] = (string) $si->supplier_invoice_number;
            }
        }
        // Additional ref pool from legacy supplier reference tables for this batch/product.
        $srpRows = DB::table('supplier_referance_product as srp')
            ->join('supplier_referacne_number as srn', 'srn.id', '=', 'srp.supplier_referacne_number_id')
            ->leftJoin('supplier_invoices as si', function ($join) {
                $join->on('si.reference_number', '=', 'srn.refreance_number')
                    ->where('si.is_approved', 1);
            })
            ->where('srn.batch_no', $batchNo)
            ->where('srp.product_id', $productId)
            ->where('srp.ref_quanitty', '>', 0)
            ->select(
                DB::raw('COALESCE(si.supplier_invoice_number, srn.refreance_number) AS supplier_invoice_number'),
                DB::raw('SUM(COALESCE(srp.ref_quanitty, 0)) AS ref_qty')
            )
            ->groupBy('si.supplier_invoice_number', 'srn.refreance_number')
            ->get();
        foreach ($srpRows as $row) {
            $sipSum += (int) ($row->ref_qty ?? 0);
            $label = trim((string) ($row->supplier_invoice_number ?? ''));
            if ($label !== '') {
                $invNos[] = $label;
            }
        }

        $swapSum = 0;
        $swapIdsUsed = [];
        foreach ($sis as $si) {
            if (empty($si->reference_number)) {
                continue;
            }
            $swaps = productSwapping::where('product_id', $productId)
                ->where('reference_number', $si->reference_number)
                ->where('ref_quantity', '>', 0)
                ->get();
            foreach ($swaps as $sw) {
                if (isset($swapIdsUsed[$sw->id])) {
                    continue;
                }
                $swapIdsUsed[$sw->id] = true;
                $swapSum += (int) $sw->ref_quantity;
            }
        }

        $netTotal = $sipSum - $swapSum;
        if ($netTotal < 0) {
            $netTotal = 0;
        }

        return [
            'total'                    => $netTotal,
            'supplier_invoice_numbers' => implode(', ', array_unique($invNos)),
        ];
    }

    /**
     * Per supplier_invoice on each selected batch: batch_no, supplier_invoice_id, available ref
     * (SIP - swap attributed to that invoice; swap deduped same as refSipAndSwapForProductOnBatch).
     */
    protected function refSourceSnapshotLinesForProductOnBatch(int $productId, string $batchNo): array
    {
        $batchNo = trim((string) $batchNo);
        if ($batchNo === '' || !$productId) {
            return [];
        }

        $sis = supplierInvoice::where('batch_no', $batchNo)
            ->where('is_approved', 1)
            ->orderBy('id')
            ->get();

        if ($sis->isEmpty()) {
            return [];
        }

        $perSi = [];
        $siLabels = [];
        foreach ($sis as $si) {
            $sip = (int) DB::table('supplier_invoice_products')
                ->where('supplier_invoice_id', $si->id)
                ->where('product_id', $productId)
                ->sum(DB::raw('COALESCE(ref_quantity, 0)'));
            $perSi[$si->id] = ['sip' => $sip, 'swap_deduct' => 0];
            $siLabels[$si->id] = (string) ($si->supplier_invoice_number ?? '');
        }
        // Merge legacy supplier reference table qty into per-invoice pool.
        $srpRows = DB::table('supplier_referance_product as srp')
            ->join('supplier_referacne_number as srn', 'srn.id', '=', 'srp.supplier_referacne_number_id')
            ->join('supplier_invoices as si', function ($join) {
                $join->on('si.reference_number', '=', 'srn.refreance_number')
                    ->where('si.is_approved', 1);
            })
            ->where('srn.batch_no', $batchNo)
            ->where('srp.product_id', $productId)
            ->where('srp.ref_quanitty', '>', 0)
            ->select(
                'si.id as supplier_invoice_id',
                'si.supplier_invoice_number',
                DB::raw('SUM(COALESCE(srp.ref_quanitty, 0)) AS ref_qty')
            )
            ->groupBy('si.id', 'si.supplier_invoice_number')
            ->get();
        foreach ($srpRows as $row) {
            $sid = (int) ($row->supplier_invoice_id ?? 0);
            if ($sid <= 0) {
                continue;
            }
            if (! isset($perSi[$sid])) {
                $perSi[$sid] = ['sip' => 0, 'swap_deduct' => 0];
            }
            $perSi[$sid]['sip'] += (int) ($row->ref_qty ?? 0);
            $siLabels[$sid] = (string) ($row->supplier_invoice_number ?? ($siLabels[$sid] ?? ''));
        }

        $swapIdsUsed = [];
        foreach ($sis as $si) {
            if (empty($si->reference_number)) {
                continue;
            }
            $swaps = productSwapping::where('product_id', $productId)
                ->where('reference_number', $si->reference_number)
                ->where('ref_quantity', '>', 0)
                ->get();
            foreach ($swaps as $sw) {
                if (isset($swapIdsUsed[$sw->id])) {
                    continue;
                }
                $swapIdsUsed[$sw->id] = true;
                $perSi[$si->id]['swap_deduct'] += (int) $sw->ref_quantity;
            }
        }

        $lines = [];
        foreach ($perSi as $sid => $vals) {
            $total = (int) $vals['sip'] - (int) $vals['swap_deduct'];
            if ($total < 0) {
                $total = 0;
            }
            if ($total <= 0) {
                continue;
            }
            $lines[] = [
                'batch_no' => $batchNo,
                'supplier_invoice_id' => (int) $sid,
                'supplier_invoice_number' => (string) ($siLabels[$sid] ?? ''),
                'available_ref_qty' => $total,
            ];
        }

        return $lines;
    }

    /** Stock log / swap ref text when unique_referencenumber.is_old = 1 (no supplier invoice). */
    private const REF_LOG_NO_SUPPLIER_INVOICE = 'no supplierinvoice';

    /**
     * packinglistproducts.qtybox is double(8,2) in DB; the form may send labels like "1 Pc/Box".
     */
    protected function normalizePackingListQtyBox(mixed $qtybox): float
    {
        if ($qtybox === null || $qtybox === '') {
            return 0.0;
        }
        if (is_numeric($qtybox)) {
            return round((float) $qtybox, 2);
        }
        $s = trim((string) $qtybox);
        if ($s === '') {
            return 0.0;
        }
        if (preg_match('/-?[0-9]+(?:\.[0-9]+)?/', $s, $m)) {
            return round((float) $m[0], 2);
        }

        return 0.0;
    }

    /**
     * packing_list_product.batch_no may be a JSON string or an array (e.g. model cast).
     *
     * @return array<int|string, mixed>
     */
    protected function batchNoListFromPlpColumn(mixed $batchNo): array
    {
        if (is_array($batchNo)) {
            return $batchNo;
        }
        if (is_string($batchNo)) {
            $decoded = json_decode($batchNo, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Packing flow: available ref from unique_referencenumber + unique_referencenumber_product
     * (sum of remaining_qty per approved supplier invoice on this batch + product).
     * Rows with is_old = 1 have no supplier invoice link but still consume remqty / remaining_qty.
     */
    protected function uniqueRefSnapshotLinesForProductOnBatch(int $productId, string $batchNo): array
    {
        $batchNo = trim((string) $batchNo);
        if ($batchNo === '' || $productId <= 0) {
            return [];
        }

        $batch = Batch::where('batch_no', $batchNo)->first();
        if (! $batch) {
            return [];
        }

        $rows = DB::table('unique_referencenumber as ur')
            ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
            ->leftJoin('supplier_invoices as si', 'si.id', '=', 'ur.supplier_invoice_id')
            ->where('ur.batch_id', (int) $batch->id)
            ->where('urp.product_id', $productId)
            ->where('urp.remaining_qty', '>', 0)
            ->where(function ($q) {
                $q->where('ur.is_old', 1)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('ur.supplier_invoice_id')
                            ->where('ur.supplier_invoice_id', '>', 0)
                            ->whereNotNull('si.id')
                            ->where('si.is_approved', 1);
                    });
            })
            ->select(
                'ur.id as unique_referencenumber_id',
                'ur.is_old',
                'ur.supplier_invoice_id',
                'si.id as si_join_id',
                'si.supplier_invoice_number',
                DB::raw('SUM(COALESCE(urp.remaining_qty, 0)) AS available_ref_qty')
            )
            ->groupBy(
                'ur.id',
                'ur.is_old',
                'ur.supplier_invoice_id',
                'si.id',
                'si.supplier_invoice_number'
            )
            ->orderBy('ur.id')
            ->get();

        $lines = [];
        foreach ($rows as $r) {
            $urId = (int) ($r->unique_referencenumber_id ?? 0);
            $qty = (int) ($r->available_ref_qty ?? 0);
            if ($urId <= 0 || $qty <= 0) {
                continue;
            }
            $isOld = (int) ($r->is_old ?? 0) === 1;
            if ($isOld) {
                $lines[] = [
                    'batch_no' => $batchNo,
                    'unique_referencenumber_id' => $urId,
                    'supplier_invoice_id' => 0,
                    'supplier_invoice_number' => 'No supplier invoice',
                    'available_ref_qty' => $qty,
                    'is_no_supplier_invoice' => true,
                ];

                continue;
            }
            $lines[] = [
                'batch_no' => $batchNo,
                'unique_referencenumber_id' => $urId,
                'supplier_invoice_id' => (int) ($r->supplier_invoice_id ?? 0),
                'supplier_invoice_number' => (string) ($r->supplier_invoice_number ?? ''),
                'available_ref_qty' => $qty,
                'is_no_supplier_invoice' => false,
            ];
        }

        return $lines;
    }

    /**
     * Flat list of ref lines for all selected batches (one row per supplier invoice with ref > 0).
     */
    protected function buildRefSourceSnapshot(int $productId, $batchNos): array
    {
        $batchNos = array_values(array_unique(array_filter(is_array($batchNos) ? $batchNos : (array) $batchNos)));
        $snapshot = [];
        foreach ($batchNos as $bn) {
            $bn = trim((string) $bn);
            if ($bn === '') {
                continue;
            }
            foreach ($this->uniqueRefSnapshotLinesForProductOnBatch($productId, $bn) as $line) {
                $snapshot[] = $line;
            }
        }

        return $snapshot;
    }

    /**
     * Swapping-only ref source: strictly from supplier_referance_product table.
     */
    protected function refSourceSnapshotLinesForSwappingOnBatch(int $productId, string $batchNo): array
    {
        $batchNo = trim((string) $batchNo);
        if ($batchNo === '' || !$productId) {
            return [];
        }

        $rows = DB::table('supplier_referance_product as srp')
            ->join('supplier_referacne_number as srn', 'srn.id', '=', 'srp.supplier_referacne_number_id')
            ->join('supplier_invoices as si', function ($join) {
                $join->on('si.reference_number', '=', 'srn.refreance_number')
                    ->where('si.is_approved', 1);
            })
            ->where('srn.batch_no', $batchNo)
            ->where('srp.product_id', $productId)
            ->where('srp.ref_quanitty', '>', 0)
            ->select(
                'si.id as supplier_invoice_id',
                'si.supplier_invoice_number',
                DB::raw('SUM(COALESCE(srp.ref_quanitty, 0)) AS available_ref_qty')
            )
            ->groupBy('si.id', 'si.supplier_invoice_number')
            ->orderBy('si.id')
            ->get();

        $lines = [];
        foreach ($rows as $r) {
            $sid = (int) ($r->supplier_invoice_id ?? 0);
            $qty = (int) ($r->available_ref_qty ?? 0);
            if ($sid <= 0 || $qty <= 0) {
                continue;
            }
            $lines[] = [
                'batch_no' => $batchNo,
                'supplier_invoice_id' => $sid,
                'supplier_invoice_number' => (string) ($r->supplier_invoice_number ?? ''),
                'available_ref_qty' => $qty,
            ];
        }

        return $lines;
    }

    /**
     * Keep only batch_no + unique_referencenumber_id for DB / form JSON (no quantities).
     * Legacy rows may still have supplier_invoice_id only.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function thinRefSourceSnapshotRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $bn = trim((string) ($row['batch_no'] ?? ''));
            if ($bn === '') {
                continue;
            }
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($urId > 0) {
                $out[] = [
                    'batch_no' => $bn,
                    'unique_referencenumber_id' => $urId,
                ];

                continue;
            }
            $sid = (int) ($row['supplier_invoice_id'] ?? 0);
            if ($sid > 0) {
                $out[] = [
                    'batch_no' => $bn,
                    'supplier_invoice_id' => $sid,
                ];
            }
        }

        return $out;
    }

    /**
     * Canonical key for comparing batch multi-select values on packing create/update.
     */
    protected function packingBatchSetKey($batchNos): string
    {
        $a = array_values(array_unique(array_filter(array_map('strval', is_array($batchNos) ? $batchNos : []))));
        sort($a);

        return implode("\0", $a);
    }

    /**
     * Reorder posted batch_no[] using UI selection order (JSON from hidden batch_selection_order_json).
     * Falls back to request order when no order hint is sent.
     *
     * @param  array<int, string>  $postedBatchNos
     * @return array<int, string>
     */
    protected function applyPackingBatchOrder(array $postedBatchNos, string $orderJson): array
    {
        $posted = array_values(array_unique(array_filter(array_map('strval', is_array($postedBatchNos) ? $postedBatchNos : []))));
        if ($posted === []) {
            return [];
        }

        $orderJson = trim($orderJson);
        if ($orderJson === '' || $orderJson === '[]') {
            return $posted;
        }

        $order = json_decode($orderJson, true);
        if (! is_array($order)) {
            return $posted;
        }

        $want = [];
        foreach ($order as $bn) {
            $bn = trim((string) $bn);
            if ($bn === '') {
                continue;
            }
            if (in_array($bn, $posted, true) && ! in_array($bn, $want, true)) {
                $want[] = $bn;
            }
        }
        foreach ($posted as $bn) {
            if (! in_array($bn, $want, true)) {
                $want[] = $bn;
            }
        }

        return $want;
    }

    /**
     * @param  array<int, array<string, mixed>>  $invRows
     * @return array<int, array<string, mixed>>
     */
    protected function normalizePackingInvBatchOrders(array $invRows): array
    {
        foreach ($invRows as $idx => $inv) {
            $orderJson = isset($inv['batch_selection_order_json']) ? (string) $inv['batch_selection_order_json'] : '';
            $invRows[$idx]['batch_no'] = $this->applyPackingBatchOrder($inv['batch_no'] ?? [], $orderJson);
        }

        return $invRows;
    }

    /**
     * When editing a packing list, rows loaded from DB may post an empty ref_selection_json (no snapshot in DB
     * or JS cleared it). Reuse the stored ref_source_snapshot from the matching existing line so validation passes.
     *
     * @param  array<int, array<string, mixed>>  $invRows
     * @return array<int, array<string, mixed>>
     */
    protected function mergePackingInvRefFromExistingRows(array $invRows, $existingPackingLines): array
    {
        // FIFO per (product, batch-set) so duplicate lines map to distinct saved rows, not the same snapshot twice.
        $queues = [];
        foreach ($existingPackingLines as $plp) {
            $pid = (int) $plp->product_id;
            $plBatches = $this->batchNoListFromPlpColumn($plp->batch_no);
            $bk = $this->packingBatchSetKey($plBatches);
            if ($pid <= 0 || $bk === '') {
                continue;
            }
            $qKey = $pid . "\0" . $bk;
            if (! isset($queues[$qKey])) {
                $queues[$qKey] = [];
            }
            $queues[$qKey][] = $plp;
        }

        foreach ($invRows as $idx => $inv) {
            $raw = isset($inv['ref_selection_json']) ? trim((string) $inv['ref_selection_json']) : '';
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $isEmpty = $raw === '' || $raw === '[]' || ! is_array($decoded) || $decoded === [];
            if (! $isEmpty) {
                continue;
            }

            $productId = (int) ($inv['product_id'] ?? 0);
            $batchNos = $inv['batch_no'] ?? [];
            if ($productId <= 0 || $this->packingBatchSetKey($batchNos) === '') {
                continue;
            }

            $wantKey = $productId . "\0" . $this->packingBatchSetKey($batchNos);
            if (! isset($queues[$wantKey]) || $queues[$wantKey] === []) {
                continue;
            }

            $plp = array_shift($queues[$wantKey]);
            $snap = $plp->ref_source_snapshot;
            if (! is_array($snap)) {
                $snap = [];
            }
            if ($snap === []) {
                $snap = $this->buildRefSourceSnapshot($productId, $batchNos);
            }
            $thin = $this->thinRefSourceSnapshotRows($snap);
            if ($thin !== []) {
                $invRows[$idx]['ref_selection_json'] = json_encode($thin);
            }
        }

        return $invRows;
    }

    /**
     * Decrement remaining_qty on unique_referencenumber_product for one supplier invoice + batch + product.
     * This keeps packing "available ref" (URNP.remaining_qty) consistent with stock OUT.
     */
    protected function deductUniqueRefRemainingQtyForProductOnBatch(
        int $supplierInvoiceId,
        int $productId,
        int $batchId,
        int $maxDeduct
    ): int
    {
        if ($maxDeduct <= 0 || $supplierInvoiceId <= 0 || $productId <= 0 || $batchId <= 0) {
            return 0;
        }

        $totalD = 0;
        $remaining = $maxDeduct;
        while ($remaining > 0) {
            // Lock one row at a time to avoid race conditions.
            $row = DB::table('unique_referencenumber as ur')
                ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
                ->where('ur.batch_id', $batchId)
                ->where('ur.supplier_invoice_id', $supplierInvoiceId)
                ->where('urp.product_id', $productId)
                ->where('urp.remaining_qty', '>', 0)
                ->orderBy('ur.id')
                ->orderBy('urp.id')
                ->select('ur.id as ur_id', 'ur.remqty as ur_remqty', 'urp.id as urp_id', 'urp.remaining_qty')
                ->lockForUpdate()
                ->first();

            if (! $row) {
                break;
            }

            $urpRq = (int) ($row->remaining_qty ?? 0);
            if ($urpRq <= 0) {
                break;
            }
            $urRem = (int) ($row->ur_remqty ?? 0);
            $d = min($remaining, $urpRq);
            if ($urRem > 0) {
                $d = min($d, $urRem);
            }
            if ($d <= 0) {
                break;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $row->urp_id)
                ->update([
                    'remaining_qty' => $urpRq - $d,
                    'updated_at' => now(),
                ]);

            // Also decrement parent unique_referencenumber.remqty to keep summary in sync.
            DB::table('unique_referencenumber')
                ->where('id', (int) $row->ur_id)
                ->update([
                    'remqty' => max(0, $urRem - $d),
                    'updated_at' => now(),
                ]);

            $totalD += $d;
            $remaining -= $d;
        }

        return $totalD;
    }

    /**
     * Same as deductUniqueRefRemainingQtyForProductOnBatch, but also returns
     * per-unique-reference allocation so swap-in can add back to exact rows used by OUT.
     *
     * @return array{deducted:int, by_ur:array<int,int>}
     */
    protected function deductUniqueRefRemainingQtyForProductOnBatchWithTrace(
        int $supplierInvoiceId,
        int $productId,
        int $batchId,
        int $maxDeduct
    ): array {
        if ($maxDeduct <= 0 || $supplierInvoiceId <= 0 || $productId <= 0 || $batchId <= 0) {
            return ['deducted' => 0, 'by_ur' => []];
        }

        $totalD = 0;
        $remaining = $maxDeduct;
        $byUr = [];
        while ($remaining > 0) {
            $row = DB::table('unique_referencenumber as ur')
                ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
                ->where('ur.batch_id', $batchId)
                ->where('ur.supplier_invoice_id', $supplierInvoiceId)
                ->where('urp.product_id', $productId)
                ->where('urp.remaining_qty', '>', 0)
                ->orderBy('ur.id')
                ->orderBy('urp.id')
                ->select('ur.id as ur_id', 'ur.remqty as ur_remqty', 'urp.id as urp_id', 'urp.remaining_qty')
                ->lockForUpdate()
                ->first();

            if (! $row) {
                break;
            }

            $urpRq = (int) ($row->remaining_qty ?? 0);
            if ($urpRq <= 0) {
                break;
            }
            $urRem = (int) ($row->ur_remqty ?? 0);
            $d = min($remaining, $urpRq);
            if ($urRem > 0) {
                $d = min($d, $urRem);
            }
            if ($d <= 0) {
                break;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $row->urp_id)
                ->update([
                    'remaining_qty' => $urpRq - $d,
                    'updated_at' => now(),
                ]);

            DB::table('unique_referencenumber')
                ->where('id', (int) $row->ur_id)
                ->update([
                    'remqty' => max(0, $urRem - $d),
                    'updated_at' => now(),
                ]);

            $urId = (int) $row->ur_id;
            $byUr[$urId] = (int) ($byUr[$urId] ?? 0) + $d;
            $totalD += $d;
            $remaining -= $d;
        }

        return ['deducted' => $totalD, 'by_ur' => $byUr];
    }

    /**
     * Decrement remaining_qty for one unique_referencenumber row + product (by ur.id), same caps as SI-based deduct.
     * Multiple unique_referencenumber_product rows for the same UR + product are consumed in urp.id order until
     * $maxDeduct is satisfied or no stock remains (matches summed snapshot availability).
     */
    protected function deductUniqueRefRemainingQtyForUrId(
        int $urId,
        int $productId,
        int $batchId,
        int $maxDeduct
    ): int {
        if ($maxDeduct <= 0 || $urId <= 0 || $productId <= 0 || $batchId <= 0) {
            return 0;
        }

        $remaining = $maxDeduct;
        $totalDeducted = 0;

        while ($remaining > 0) {
            $row = DB::table('unique_referencenumber as ur')
                ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
                ->where('ur.id', $urId)
                ->where('ur.batch_id', $batchId)
                ->where('urp.product_id', $productId)
                ->where('urp.remaining_qty', '>', 0)
                ->orderBy('urp.id')
                ->select('ur.id as ur_id', 'ur.remqty as ur_remqty', 'urp.id as urp_id', 'urp.remaining_qty')
                ->lockForUpdate()
                ->first();

            if (! $row) {
                break;
            }

            $urpRq = (int) ($row->remaining_qty ?? 0);
            if ($urpRq <= 0) {
                break;
            }
            $urRem = (int) ($row->ur_remqty ?? 0);
            $d = min($remaining, $urpRq);
            if ($urRem > 0) {
                $d = min($d, $urRem);
            }
            if ($d <= 0) {
                break;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $row->urp_id)
                ->update([
                    'remaining_qty' => $urpRq - $d,
                    'updated_at' => now(),
                ]);

            DB::table('unique_referencenumber')
                ->where('id', (int) $row->ur_id)
                ->update([
                    'remqty' => max(0, $urRem - $d),
                    'updated_at' => now(),
                ]);

            $totalDeducted += $d;
            $remaining -= $d;
        }

        return $totalDeducted;
    }

    /**
     * Sum available ref qty for is_old (no supplier invoice) lines on batch+product — for stock log (used/total).
     */
    protected function totalNoSupplierInvoiceRefQtyForProductOnBatch(int $productId, string $batchNo): int
    {
        $total = 0;
        foreach ($this->uniqueRefSnapshotLinesForProductOnBatch($productId, $batchNo) as $line) {
            if (! empty($line['is_no_supplier_invoice'])) {
                $total += (int) ($line['available_ref_qty'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * @param  array<int, array<string, mixed>>  $refSnapshot
     * @return array<int, array<string, mixed>>
     */
    protected function refSnapshotRowsForBatch(array $refSnapshot, string $batchNo): array
    {
        $batchNo = trim((string) $batchNo);
        $out = [];
        foreach ($refSnapshot as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (trim((string) ($row['batch_no'] ?? '')) === $batchNo) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * After SI-based deduct: consume is_old (no supplier invoice) pool using selected snapshot UR ids in order.
     *
     * @param  array<int, array<string, mixed>>  $refSnapshotLinesForBatch
     * @return int Total qty deducted from is_old refs
     */
    protected function deductNoSupplierInvoiceRefsFromSnapshot(
        array $refSnapshotLinesForBatch,
        int $productId,
        int $batchId,
        int $remainingDeduct
    ): int {
        $usedNoSi = 0;
        if ($remainingDeduct <= 0 || $refSnapshotLinesForBatch === []) {
            return 0;
        }

        foreach ($refSnapshotLinesForBatch as $snapRow) {
            if ($remainingDeduct <= 0) {
                break;
            }
            $urId = (int) ($snapRow['unique_referencenumber_id'] ?? 0);
            if ($urId <= 0) {
                continue;
            }
            $urRow = DB::table('unique_referencenumber')->where('id', $urId)->first();
            if (! $urRow || (int) ($urRow->batch_id ?? 0) !== $batchId) {
                continue;
            }
            if ((int) ($urRow->is_old ?? 0) !== 1) {
                continue;
            }

            $d = $this->deductUniqueRefRemainingQtyForUrId($urId, $productId, $batchId, $remainingDeduct);
            if ($d > 0) {
                $usedNoSi += $d;
                $remainingDeduct -= $d;
            }
        }

        return $usedNoSi;
    }

    /**
     * Credit note / return flow: add back reference qty into unique reference pool using snapshot lines.
     *
     * Snapshot lines are expected in packingListProduct.ref_source_snapshot like:
     *   [{batch_no: "...", unique_referencenumber_id: 123}, ...]
     *
     * Allocation rule requested: use "last" refs first (DESC by unique_referencenumber_id).
     * Caps: unique_referencenumber_product.remaining_qty must not exceed originalqty;
     *       unique_referencenumber.remqty must not exceed original_qty.
     */
    /**
     * @return array{added:int, lines:array<int,array{batch_no:string,unique_referencenumber_id:int,qty:int}>}
     */
    protected function addBackUniqueRefRemainingQtyFromSnapshot(
        int $productId,
        string $batchNo,
        array $refSourceSnapshot,
        int $qtyToAddBack
    ): array {
        $productId = (int) $productId;
        $batchNo = trim((string) $batchNo);
        $qtyToAddBack = (int) $qtyToAddBack;
        if ($productId <= 0 || $batchNo === '' || $qtyToAddBack <= 0) {
            return ['added' => 0, 'lines' => []];
        }
        if (! is_array($refSourceSnapshot) || $refSourceSnapshot === []) {
            return ['added' => 0, 'lines' => []];
        }

        $lines = [];
        foreach ($refSourceSnapshot as $row) {
            $bn = trim((string) ($row['batch_no'] ?? ''));
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($bn === $batchNo && $urId > 0) {
                $lines[] = $urId;
            }
        }
        if ($lines === []) {
            return ['added' => 0, 'lines' => []];
        }

        rsort($lines, SORT_NUMERIC); // last ref first
        $lines = array_values(array_unique($lines));

        $added = 0;
        $remaining = $qtyToAddBack;
        $auditLines = [];

        foreach ($lines as $urId) {
            if ($remaining <= 0) {
                break;
            }

            $ur = DB::table('unique_referencenumber')->where('id', (int) $urId)->first();
            if (! $ur) {
                continue;
            }

            $urp = DB::table('unique_referencenumber_product')
                ->where('unique_referencenumber_id', (int) $urId)
                ->where('product_id', $productId)
                ->first();
            if (! $urp) {
                continue;
            }

            $origP = (int) ($urp->originalqty ?? 0);
            $remP  = (int) ($urp->remaining_qty ?? 0);
            $capP  = max(0, $origP - $remP);
            if ($capP <= 0) {
                continue;
            }

            $origH = (int) ($ur->original_qty ?? 0);
            $remH  = (int) ($ur->remqty ?? 0);
            $capH  = max(0, $origH - $remH);

            $toAdd = min($remaining, $capP);
            if ($capH > 0) {
                $toAdd = min($toAdd, $capH);
            }
            if ($toAdd <= 0) {
                continue;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $urp->id)
                ->update([
                    'remaining_qty' => $remP + $toAdd,
                    'updated_at' => now(),
                ]);
            DB::table('unique_referencenumber')
                ->where('id', (int) $urId)
                ->update([
                    'remqty' => $remH + $toAdd,
                    'updated_at' => now(),
                ]);

            $added += $toAdd;
            $remaining -= $toAdd;
            $auditLines[] = [
                'batch_no' => $batchNo,
                'unique_referencenumber_id' => (int) $urId,
                'qty' => (int) $toAdd,
            ];
        }

        return ['added' => $added, 'lines' => $auditLines];
    }

    /**
     * Helper: total current remaining_qty across snapshot lines for a batch+product,
     * BEFORE any add-back happens (used for stock log "used/total" display).
     */
    protected function totalUniqueRefRemainingQtyFromSnapshotBeforeAddBack(
        int $productId,
        string $batchNo,
        array $refSourceSnapshot
    ): int {
        $productId = (int) $productId;
        $batchNo = trim((string) $batchNo);
        if ($productId <= 0 || $batchNo === '' || ! is_array($refSourceSnapshot) || $refSourceSnapshot === []) {
            return 0;
        }

        $urIds = [];
        foreach ($refSourceSnapshot as $row) {
            $bn = trim((string) ($row['batch_no'] ?? ''));
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($bn === $batchNo && $urId > 0) {
                $urIds[$urId] = true;
            }
        }
        if ($urIds === []) {
            return 0;
        }

        return (int) DB::table('unique_referencenumber_product')
            ->whereIn('unique_referencenumber_id', array_keys($urIds))
            ->where('product_id', $productId)
            ->sum(DB::raw('COALESCE(remaining_qty, 0)'));
    }

    protected function supplierInvoiceProductRowExists(int $supplierInvoiceId, int $productId): bool
    {
        if ($supplierInvoiceId <= 0 || $productId <= 0) {
            return false;
        }

        return soTable::where('supplier_invoice_id', $supplierInvoiceId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Decrement legacy supplier reference pool from supplier_referance_product.ref_quanitty
     * for one supplier invoice reference number + batch + product.
     */
    protected function deductSupplierReferenceRefForInvoiceReference(
        supplierInvoice $supInv,
        int $productId,
        string $batchNo,
        int $maxDeduct
    ): int {
        if ($maxDeduct <= 0 || $productId <= 0) {
            return 0;
        }
        $refNum = trim((string) ($supInv->reference_number ?? ''));
        $batchNo = trim((string) $batchNo);
        if ($refNum === '' || $batchNo === '') {
            return 0;
        }

        $totalD = 0;
        $remaining = $maxDeduct;

        $srpRows = DB::table('supplier_referance_product as srp')
            ->join('supplier_referacne_number as srn', 'srn.id', '=', 'srp.supplier_referacne_number_id')
            ->where('srn.refreance_number', $refNum)
            ->where('srn.batch_no', $batchNo)
            ->where('srp.product_id', $productId)
            ->where('srp.ref_quanitty', '>', 0)
            ->orderBy('srp.id')
            ->select('srp.id', 'srp.ref_quanitty')
            ->get();

        foreach ($srpRows as $row) {
            if ($remaining <= 0) {
                break;
            }
            $rq = (int) ($row->ref_quanitty ?? 0);
            if ($rq <= 0) {
                continue;
            }
            $d = min($remaining, $rq);
            DB::table('supplier_referance_product')
                ->where('id', (int) $row->id)
                ->update([
                    'ref_quanitty' => $rq - $d,
                    'updated_at' => now(),
                ]);
            $totalD += $d;
            $remaining -= $d;
        }

        return $totalD;
    }

    /**
     * Create / increment swap-in ref pool in unique reference tables for batch + reference + product.
     */
    protected function createUniqueRefForSwapIn(string $batchNo, string $referenceNumber, int $productId, int $qty): void
    {
        $batchNo = trim((string) $batchNo);
        $referenceNumber = trim((string) $referenceNumber);
        $qty = (int) $qty;
        if ($batchNo === '' || $referenceNumber === '' || $productId <= 0 || $qty <= 0) {
            return;
        }

        $batch = Batch::where('batch_no', $batchNo)->first();
        if (! $batch) {
            return;
        }

        $urRow = null;
        if (preg_match('/^UR:(\d+)$/', $referenceNumber, $m)) {
            $urRow = DB::table('unique_referencenumber')
                ->where('id', (int) $m[1])
                ->where('batch_id', (int) $batch->id)
                ->first();
        }
        if (! $urRow) {
            $urRow = DB::table('unique_referencenumber')
                ->where('batch_id', (int) $batch->id)
                ->where('ref_no', $referenceNumber)
                ->orderBy('id')
                ->first();
        }
        if (! $urRow) {
            // Fallback: sometimes allocation key carries supplier_invoices.reference_number.
            // Resolve supplier invoice first, then map to unique_referencenumber by supplier_invoice_id.
            $si = supplierInvoice::where('batch_no', $batchNo)
                ->where('reference_number', $referenceNumber)
                ->where('is_approved', 1)
                ->orderBy('id')
                ->first();
            if ($si) {
                $urRow = DB::table('unique_referencenumber')
                    ->where('batch_id', (int) $batch->id)
                    ->where('supplier_invoice_id', (int) $si->id)
                    ->orderBy('id')
                    ->first();
            }
        }
        if (! $urRow) {
            // If still not found, create a new unique reference header in same batch.
            // This keeps swap-IN resilient when reference pool row does not exist yet.
            $siForNew = supplierInvoice::where('batch_no', $batchNo)
                ->where('reference_number', $referenceNumber)
                ->where('is_approved', 1)
                ->orderBy('id')
                ->first();

            $newUrId = DB::table('unique_referencenumber')->insertGetId([
                'batch_id' => (int) $batch->id,
                'supplier_invoice_id' => $siForNew ? (int) $siForNew->id : null,
                'ref_no' => $referenceNumber,
                'original_qty' => 0,
                'remqty' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $urRow = DB::table('unique_referencenumber')->where('id', (int) $newUrId)->first();
            if (! $urRow) {
                return;
            }
        }

        $urId = (int) $urRow->id;
        $existing = DB::table('unique_referencenumber_product')
            ->where('unique_referencenumber_id', $urId)
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            DB::table('unique_referencenumber_product')
                ->where('id', (int) $existing->id)
                ->update([
                    'remaining_qty' => (int) ($existing->remaining_qty ?? 0) + $qty,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('unique_referencenumber_product')->insert([
                'unique_referencenumber_id' => $urId,
                'product_id' => $productId,
                'originalqty' => 0,
                'remaining_qty' => $qty,
                'remark' => 'Swap IN',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('unique_referencenumber')
            ->where('id', $urId)
            ->update([
                'remqty' => (int) ($urRow->remqty ?? 0) + $qty,
                'updated_at' => now(),
            ]);
    }

    protected function resolveUniqueRefRowForBatchAndReference(string $batchNo, string $referenceNumber)
    {
        $batchNo = trim((string) $batchNo);
        $referenceNumber = trim((string) $referenceNumber);
        if ($batchNo === '' || $referenceNumber === '') {
            return null;
        }

        $batch = Batch::where('batch_no', $batchNo)->first();
        if (! $batch) {
            return null;
        }

        $urRow = null;
        if (preg_match('/^UR:(\d+)$/', $referenceNumber, $m)) {
            $urRow = DB::table('unique_referencenumber')
                ->where('id', (int) $m[1])
                ->where('batch_id', (int) $batch->id)
                ->first();
        }
        if (! $urRow) {
            $urRow = DB::table('unique_referencenumber')
                ->where('batch_id', (int) $batch->id)
                ->where('ref_no', $referenceNumber)
                ->orderBy('id')
                ->first();
        }
        if (! $urRow) {
            $si = supplierInvoice::where('batch_no', $batchNo)
                ->where('reference_number', $referenceNumber)
                ->where('is_approved', 1)
                ->orderBy('id')
                ->first();
            if ($si) {
                $urRow = DB::table('unique_referencenumber')
                    ->where('batch_id', (int) $batch->id)
                    ->where('supplier_invoice_id', (int) $si->id)
                    ->orderBy('id')
                    ->first();
            }
        }

        return $urRow;
    }

    protected function openQtySumForInProductOnBatchReference(string $batchNo, string $referenceNumber, int $productId): int
    {
        $urRow = $this->resolveUniqueRefRowForBatchAndReference($batchNo, $referenceNumber);
        if (! $urRow || $productId <= 0) {
            return 0;
        }

        return (int) DB::table('unique_referencenumber_product')
            ->where('unique_referencenumber_id', (int) $urRow->id)
            ->where('product_id', (int) $productId)
            ->sum(DB::raw('COALESCE(remaining_qty, 0)'));
    }

    protected function supplierInvoiceLabelForBatchReference(string $batchNo, string $referenceNumber): string
    {
        $urRow = $this->resolveUniqueRefRowForBatchAndReference($batchNo, $referenceNumber);
        if ($urRow && ! empty($urRow->supplier_invoice_id)) {
            $si = supplierInvoice::find((int) $urRow->supplier_invoice_id);
            if ($si && ! empty($si->supplier_invoice_number)) {
                return (string) $si->supplier_invoice_number;
            }
        }

        if (preg_match('/^UR:(\d+)$/', $referenceNumber, $m)) {
            return 'UR:'.$m[1];
        }

        return $referenceNumber;
    }

    /**
     * Create swap-in ref row in supplier_referance_product for batch + reference + product.
     */
    protected function createSupplierReferenceRefForSwapIn(string $batchNo, string $referenceNumber, int $productId, int $qty): void
    {
        $batchNo = trim((string) $batchNo);
        $referenceNumber = trim((string) $referenceNumber);
        $qty = (int) $qty;
        if ($batchNo === '' || $referenceNumber === '' || $productId <= 0 || $qty <= 0) {
            return;
        }

        // Legacy tables are optional in some environments; skip without failing swap.
        if (!\Schema::hasTable('supplier_referacne_number') || !\Schema::hasTable('supplier_referance_product')) {
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

        DB::table('supplier_referance_product')->insert([
            'supplier_referacne_number_id' => $referenceId,
            'product_id' => $productId,
            'ref_quanitty' => $qty,
            'remark' => 'Swap IN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Match supplier_invoices.reference_number to product_swapping and decrement ref_quantity (deduped by swap id).
     *
     * @param  array<int, bool>  $swapIdsUsedOut
     */
    protected function deductSwapRefForInvoiceReference(
        supplierInvoice $supInv,
        int $productId,
        int $maxDeduct,
        array &$swapIdsUsedOut
    ): int {
        if ($maxDeduct <= 0 || $productId <= 0) {
            return 0;
        }
        $refNum = trim((string) ($supInv->reference_number ?? ''));
        if ($refNum === '') {
            return 0;
        }

        $totalD = 0;
        $remaining = $maxDeduct;
        $swaps = productSwapping::where('product_id', $productId)
            ->where('reference_number', $refNum)
            ->where('ref_quantity', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($swaps as $sw) {
            if ($remaining <= 0) {
                break;
            }
            if (isset($swapIdsUsedOut[$sw->id])) {
                continue;
            }
            $swapIdsUsedOut[$sw->id] = true;
            $rq = (int) $sw->ref_quantity;
            $d = min($remaining, $rq);
            $sw->ref_quantity = $rq - $d;
            $sw->save();
            $totalD += $d;
            $remaining -= $d;
        }

        return $totalD;
    }

    /**
     * Ensure supplier_invoices.reference_number is set (same pattern as supplier invoice approve).
     */
    protected function ensureSupplierInvoiceReferenceNumberForSwap(supplierInvoice $supplierInvoice): void
    {
        if (! $supplierInvoice || ! empty($supplierInvoice->reference_number)) {
            return;
        }
        do {
            $referenceNumber = 'SI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (supplierInvoice::where('reference_number', $referenceNumber)->exists());

        $supplierInvoice->reference_number = $referenceNumber;
    }

    /**
     * Deduct OUT product ref for swapping: primarily from user-selected unique_referencenumber ids (snapshot),
     * via unique_referencenumber_product.remaining_qty — same mechanism as legacy is_old rows, for all UR types.
     * Swap-in ref is NOT written to SIP here — storeswapping creates new product_swapping rows (product_id = IN).
     *
     * @param  array<string, int>  $swapInQtyByRefNumber  OUT param: "batch_no\0reference_number" => qty
     * @param  array<int, bool>  $swapIdsUsedOut  persists across batch slices in one swap line
     */
    protected function deductSwapOutRefForBatchSlice(
        string $batchNo,
        int $productOutId,
        int $productInId,
        int $deductQty,
        array $preferredSiIds,
        array &$swapInQtyByRefNumber,
        array &$swapIdsUsedOut,
        array &$swapOutRefParts,
        array $refSnapshotLinesForBatch = []
    ): void {
        $remainingDeduct = $deductQty;

        // Swapping flow available ref source: unique_referencenumber + unique_referencenumber_product.
        $totalAvailRef = 0;
        foreach ($this->uniqueRefSnapshotLinesForProductOnBatch($productOutId, $batchNo) as $line) {
            $totalAvailRef += (int) ($line['available_ref_qty'] ?? 0);
        }
        if ($totalAvailRef <= 0) {
            return;
        }

        $batch = Batch::where('batch_no', $batchNo)->first();
        if (! $batch) {
            throw new \RuntimeException('Batch not found while deducting reference quantity: '.$batchNo);
        }

        $availableRefQtyNoSi = $this->totalNoSupplierInvoiceRefQtyForProductOnBatch($productOutId, $batchNo);

        // Denominators for stock-log strings (available ref per supplier invoice before this slice).
        $availableRefQtyBySupplierInvoiceId = [];
        $refSourceLines = $this->uniqueRefSnapshotLinesForProductOnBatch(
            $productOutId,
            $batchNo
        );
        foreach ($refSourceLines as $line) {
            $sidKey = (int) ($line['supplier_invoice_id'] ?? 0);
            if ($sidKey > 0) {
                $availableRefQtyBySupplierInvoiceId[$sidKey] = (int) ($availableRefQtyBySupplierInvoiceId[$sidKey] ?? 0)
                    + (int) ($line['available_ref_qty'] ?? 0);
            }
        }

        // Prefer user snapshot order: deduct by unique_referencenumber id (no supplier-invoice iteration required).
        if ($refSnapshotLinesForBatch !== []) {
            $usedRefQtyBySupplierInvoiceId = [];
            $usedNoSiQty = 0;

            foreach ($refSnapshotLinesForBatch as $snapRow) {
                if ($remainingDeduct <= 0) {
                    break;
                }
                $urId = (int) ($snapRow['unique_referencenumber_id'] ?? 0);
                if ($urId <= 0) {
                    continue;
                }
                $urRow = DB::table('unique_referencenumber')->where('id', $urId)->first();
                if (! $urRow || (int) ($urRow->batch_id ?? 0) !== (int) $batch->id) {
                    continue;
                }

                $dUr = $this->deductUniqueRefRemainingQtyForUrId(
                    $urId,
                    $productOutId,
                    (int) $batch->id,
                    $remainingDeduct
                );
                if ($dUr <= 0) {
                    continue;
                }

                $remainingDeduct -= $dUr;
                $allocKey = $batchNo."\0".'UR:'.(int) $urId;
                $swapInQtyByRefNumber[$allocKey] = ($swapInQtyByRefNumber[$allocKey] ?? 0) + $dUr;

                $sidUr = (int) ($urRow->supplier_invoice_id ?? 0);
                if ($sidUr > 0) {
                    $usedRefQtyBySupplierInvoiceId[$sidUr] = (int) ($usedRefQtyBySupplierInvoiceId[$sidUr] ?? 0) + $dUr;
                } else {
                    $usedNoSiQty += $dUr;
                }
            }

            if ($remainingDeduct > 0) {
                throw new \RuntimeException(
                    'Ref deduction incomplete for batch '.$batchNo.': '.$remainingDeduct.' qty left in unique reference pool. Re-open Available ref quantity and select valid lines.'
                );
            }

            foreach ($usedRefQtyBySupplierInvoiceId as $sid => $used) {
                if ($used <= 0) {
                    continue;
                }
                $total = (int) ($availableRefQtyBySupplierInvoiceId[$sid] ?? 0);
                if ($total <= 0) {
                    $total = $used;
                }
                $supInv = supplierInvoice::find((int) $sid);
                $swapOutRefParts[] = ($supInv->supplier_invoice_number ?? ('SI#'.$sid)).'('.$used.'/'.$total.')';
            }

            if ($usedNoSiQty > 0) {
                $totalNoSi = $availableRefQtyNoSi > 0 ? $availableRefQtyNoSi : $usedNoSiQty;
                $swapOutRefParts[] = self::REF_LOG_NO_SUPPLIER_INVOICE.'('.$usedNoSiQty.'/'.$totalNoSi.')';
            }

            return;
        }

        // No snapshot rows: legacy supplier-invoice-ordered deduction only (rare if validation passed).
        $sis = supplierInvoice::where('batch_no', $batchNo)
            ->where('is_approved', 1)
            ->orderBy('id')
            ->get();

        $orderedInvoices = [];
        $seenInv = [];
        foreach ($preferredSiIds as $sid) {
            $supInv = $sis->firstWhere('id', (int) $sid);
            if ($supInv) {
                $orderedInvoices[] = $supInv;
                $seenInv[(int) $supInv->id] = true;
            }
        }
        foreach ($sis as $supInv) {
            if (! isset($seenInv[(int) $supInv->id])) {
                $orderedInvoices[] = $supInv;
                $seenInv[(int) $supInv->id] = true;
            }
        }

        $usedRefQtyBySupplierInvoiceId = [];

        foreach ($orderedInvoices as $supInv) {
            if ($remainingDeduct <= 0) {
                break;
            }
            $sid = (int) $supInv->id;

            $dTrace = $this->deductUniqueRefRemainingQtyForProductOnBatchWithTrace(
                $sid,
                $productOutId,
                (int) $batch->id,
                $remainingDeduct
            );
            $dUniqueRef = (int) ($dTrace['deducted'] ?? 0);
            if ($dUniqueRef > 0) {
                $byUr = is_array($dTrace['by_ur'] ?? null) ? $dTrace['by_ur'] : [];
                foreach ($byUr as $urId => $qUr) {
                    $qUr = (int) $qUr;
                    if ($qUr <= 0) {
                        continue;
                    }
                    $allocKey = $batchNo."\0".'UR:'.(int) $urId;
                    $swapInQtyByRefNumber[$allocKey] = ($swapInQtyByRefNumber[$allocKey] ?? 0) + $qUr;
                }
                if ($byUr === []) {
                    $rn = trim((string) ($supInv->reference_number ?? ''));
                    if ($rn !== '') {
                        $allocKey = $batchNo."\0".$rn;
                        $swapInQtyByRefNumber[$allocKey] = ($swapInQtyByRefNumber[$allocKey] ?? 0) + $dUniqueRef;
                    }
                }
                $usedRefQtyBySupplierInvoiceId[$sid] = ($usedRefQtyBySupplierInvoiceId[$sid] ?? 0) + $dUniqueRef;
                $remainingDeduct -= $dUniqueRef;
            }
        }

        if ($remainingDeduct > 0) {
            throw new \RuntimeException(
                'Ref deduction incomplete for batch '.$batchNo.': '.$remainingDeduct.' qty left in unique reference pool. Re-open Available ref quantity and select valid lines.'
            );
        }

        foreach ($orderedInvoices as $supInv) {
            $sid = (int) $supInv->id;
            $used = (int) ($usedRefQtyBySupplierInvoiceId[$sid] ?? 0);
            if ($used <= 0) {
                continue;
            }
            $total = (int) ($availableRefQtyBySupplierInvoiceId[$sid] ?? 0);
            if ($total <= 0) {
                $total = $used;
            }
            $swapOutRefParts[] = ($supInv->supplier_invoice_number ?? '').'('.$used.'/'.$total.')';
        }
    }

    /**
     * @return array<string, array<int, int>> batch_no => ordered unique supplier_invoice_id
     */
    protected function supplierInvoiceIdsByBatchFromRefSnapshot($refSnapshot): array
    {
        if (! is_array($refSnapshot) || $refSnapshot === []) {
            return [];
        }

        $byBatch = [];
        foreach ($refSnapshot as $row) {
            $bn = trim((string) ($row['batch_no'] ?? ''));
            if ($bn === '') {
                continue;
            }

            $sid = 0;
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($urId > 0) {
                $ur = DB::table('unique_referencenumber')->where('id', $urId)->first();
                $sid = $ur ? (int) ($ur->supplier_invoice_id ?? 0) : 0;
            }
            if ($sid <= 0) {
                $sid = (int) ($row['supplier_invoice_id'] ?? 0);
            }
            if ($sid <= 0) {
                continue;
            }
            if (! isset($byBatch[$bn])) {
                $byBatch[$bn] = [];
            }
            $byBatch[$bn][] = $sid;
        }

        foreach ($byBatch as $bn => $ids) {
            $seen = [];
            $uniq = [];
            foreach ($ids as $sid) {
                if (isset($seen[$sid])) {
                    continue;
                }
                $seen[$sid] = true;
                $uniq[] = $sid;
            }
            $byBatch[$bn] = $uniq;
        }

        return $byBatch;
    }

    public function batchDetails(Request $request)
    {
        $batchNos = array_values(array_unique(array_filter((array) $request->batch_no)));
        $productId = (int) $request->product_id;

        if (empty($batchNos) || !$productId) {
            return response()->json([]);
        }

        $rows = [];
        foreach ($batchNos as $bn) {
            $bn = trim((string) $bn);
            if ($bn === '') {
                continue;
            }
            foreach ($this->uniqueRefSnapshotLinesForProductOnBatch($productId, $bn) as $line) {
                $rows[] = [
                    'batch_no' => $line['batch_no'],
                    'unique_referencenumber_id' => (int) ($line['unique_referencenumber_id'] ?? 0),
                    'supplier_invoice_id' => (int) ($line['supplier_invoice_id'] ?? 0),
                    'supplier_invoice_number' => $line['supplier_invoice_number'] ?? '',
                    'available_qty' => $line['available_ref_qty'],
                    'is_no_supplier_invoice' => ! empty($line['is_no_supplier_invoice']),
                ];
            }
        }

        return response()->json($rows);
    }

    /**
     * Swapping modal source: same as packing list (unique_referencenumber + unique_referencenumber_product).
     */
    public function swappingBatchDetails(Request $request)
    {
        $batchNos = array_values(array_unique(array_filter((array) $request->batch_no)));
        $productId = (int) $request->product_id;

        if (empty($batchNos) || !$productId) {
            return response()->json([]);
        }

        $rows = [];
        foreach ($batchNos as $bn) {
            $bn = trim((string) $bn);
            if ($bn === '') {
                continue;
            }
            foreach ($this->uniqueRefSnapshotLinesForProductOnBatch($productId, $bn) as $line) {
                $rows[] = [
                    'batch_no' => $line['batch_no'],
                    'unique_referencenumber_id' => (int) ($line['unique_referencenumber_id'] ?? 0),
                    'supplier_invoice_id' => (int) ($line['supplier_invoice_id'] ?? 0),
                    'supplier_invoice_number' => $line['supplier_invoice_number'] ?? '',
                    'available_qty' => $line['available_ref_qty'],
                    'is_no_supplier_invoice' => ! empty($line['is_no_supplier_invoice']),
                ];
            }
        }

        return response()->json($rows);
    }

    /**
     * Packing line: sum of ref from popup-selected supplier invoices must be >= packing quantity.
     *
     * @return array{ok:bool,message:?string,snapshot:array<int,array<string,mixed>>}
     */
    protected function validatePackingRefSelection(int $productId, int $quantity, $batchNos, $refSelectionJson): array
    {
        $batchNos = array_values(array_unique(array_filter(is_array($batchNos) ? $batchNos : (array) $batchNos)));
        $batchNos = array_map('strval', $batchNos);

        if (!$productId || $batchNos === []) {
            return [
                'ok' => false,
                'message' => 'Product and at least one batch are required.',
                'snapshot' => [],
            ];
        }

        // If there is no available ref pool (no approved SIP + no swap ref for these batches),
        // then ref_selection_json is not applicable; allow the operation to proceed.
        $totalAvailRef = $this->totalRefQtyForPackingLine($productId, $batchNos);
        if ($totalAvailRef <= 0) {
            return ['ok' => true, 'message' => null, 'snapshot' => []];
        }

        $raw = is_string($refSelectionJson) ? trim($refSelectionJson) : '';
        if ($raw === '') {
            return [
                'ok' => false,
                'message' => 'Open “Available ref quantity”, select supplier invoice line(s), and click Apply for each product row.',
                'snapshot' => [],
            ];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || $decoded === []) {
            return [
                'ok' => false,
                'message' => 'Invalid ref selection. Use Apply in the Available ref quantity popup.',
                'snapshot' => [],
            ];
        }

        $normalized = [];
        $seenKeys = [];
        $batchesFromLines = [];

        foreach ($decoded as $row) {
            $bn = trim((string) ($row['batch_no'] ?? ''));
            if ($bn === '') {
                continue;
            }

            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            $supplierInvoiceId = (int) ($row['supplier_invoice_id'] ?? 0);

            if ($urId > 0) {
                $dedupeKey = $urId . "\0" . $bn;
                if (isset($seenKeys[$dedupeKey])) {
                    return [
                        'ok' => false,
                        'message' => 'Duplicate reference line in ref selection.',
                        'snapshot' => [],
                    ];
                }
                $seenKeys[$dedupeKey] = true;

                $expectedLines = $this->uniqueRefSnapshotLinesForProductOnBatch($productId, $bn);
                $matchedRef = null;
                foreach ($expectedLines as $el) {
                    if ((int) ($el['unique_referencenumber_id'] ?? 0) === $urId) {
                        $matchedRef = (int) $el['available_ref_qty'];
                        break;
                    }
                }
                if ($matchedRef === null) {
                    return [
                        'ok' => false,
                        'message' => "Unique reference #{$urId} is not valid for this product on batch {$bn}.",
                        'snapshot' => [],
                    ];
                }

                $normalized[] = [
                    'batch_no' => $bn,
                    'unique_referencenumber_id' => $urId,
                    'available_ref_qty' => $matchedRef,
                ];
                $batchesFromLines[] = $bn;

                continue;
            }

            // Legacy packing rows: thinRefSourceSnapshotRows() kept supplier_invoice_id only (no unique_referencenumber_id).
            if ($supplierInvoiceId > 0) {
                $expectedLines = $this->uniqueRefSnapshotLinesForProductOnBatch($productId, $bn);
                $candidates = [];
                foreach ($expectedLines as $el) {
                    if ((int) ($el['supplier_invoice_id'] ?? 0) !== $supplierInvoiceId) {
                        continue;
                    }
                    $candUr = (int) ($el['unique_referencenumber_id'] ?? 0);
                    if ($candUr <= 0) {
                        continue;
                    }
                    $candidates[] = $el;
                }
                if ($candidates === []) {
                    return [
                        'ok' => false,
                        'message' => "Supplier invoice #{$supplierInvoiceId} is not valid for this product on batch {$bn}. Open Available ref quantity and click Apply.",
                        'snapshot' => [],
                    ];
                }
                foreach ($candidates as $el) {
                    $candUr = (int) ($el['unique_referencenumber_id'] ?? 0);
                    $dedupeKey = $candUr . "\0" . $bn;
                    if (isset($seenKeys[$dedupeKey])) {
                        continue;
                    }
                    $seenKeys[$dedupeKey] = true;
                    $normalized[] = [
                        'batch_no' => $bn,
                        'unique_referencenumber_id' => $candUr,
                        'available_ref_qty' => (int) ($el['available_ref_qty'] ?? 0),
                    ];
                    $batchesFromLines[] = $bn;
                }
            }
        }

        if ($normalized === []) {
            return [
                'ok' => false,
                'message' => 'No valid reference lines in ref selection. Open Available ref quantity, select lines, and click Apply.',
                'snapshot' => [],
            ];
        }

        $batchesFromLines = array_values(array_unique($batchesFromLines));
        sort($batchesFromLines);
        $sortedForm = $batchNos;
        sort($sortedForm);
        if ($batchesFromLines !== $sortedForm) {
            return [
                'ok' => false,
                'message' => 'Batches in the form must match the supplier invoices selected in the popup (click Apply after selecting).',
                'snapshot' => [],
            ];
        }

        $sumRef = 0;
        foreach ($normalized as $n) {
            $sumRef += $n['available_ref_qty'];
        }

        if ($quantity > $sumRef) {
            return [
                'ok' => false,
                'message' => "Packing quantity ({$quantity}) is greater than total ref from selected supplier invoices ({$sumRef}). Select more invoice line(s) or reduce quantity.",
                'snapshot' => [],
            ];
        }

        $snapshotForDb = [];
        foreach ($normalized as $n) {
            $snapshotForDb[] = [
                'batch_no' => $n['batch_no'],
                'unique_referencenumber_id' => (int) $n['unique_referencenumber_id'],
            ];
        }

        return ['ok' => true, 'message' => null, 'snapshot' => $snapshotForDb];
    }

    /**
     * Swapping ref validation: use unique_referencenumber + unique_referencenumber_product (same as packing).
     *
     * @return array{ok:bool,message:?string,snapshot:array<int,array<string,mixed>>}
     */
    protected function validateSwappingRefSelection(int $productId, int $quantity, $batchNos, $refSelectionJson): array
    {
        $batchNos = array_values(array_unique(array_filter(is_array($batchNos) ? $batchNos : (array) $batchNos)));
        $batchNos = array_map('strval', $batchNos);

        if (!$productId || $batchNos === []) {
            return ['ok' => false, 'message' => 'Product and at least one batch are required.', 'snapshot' => []];
        }

        $totalAvailRef = $this->totalRefQtyForPackingLine($productId, $batchNos);
        if ($totalAvailRef <= 0) {
            return ['ok' => true, 'message' => null, 'snapshot' => []];
        }

        $raw = is_string($refSelectionJson) ? trim($refSelectionJson) : '';
        if ($raw === '') {
            return [
                'ok' => false,
                'message' => 'Open "Available ref quantity", select supplier invoice line(s), and click Apply for each product row.',
                'snapshot' => [],
            ];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || $decoded === []) {
            return ['ok' => false, 'message' => 'Invalid ref selection. Use Apply in the Available ref quantity popup.', 'snapshot' => []];
        }

        $normalized = [];
        $seenKeys = [];
        $batchesFromLines = [];

        foreach ($decoded as $row) {
            $bn = trim((string) ($row['batch_no'] ?? ''));
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($bn === '' || $urId <= 0) {
                continue;
            }

            $dedupeKey = $urId . "\0" . $bn;
            if (isset($seenKeys[$dedupeKey])) {
                // If UI sends duplicate selected rows, ignore duplicates and continue.
                continue;
            }
            $seenKeys[$dedupeKey] = true;

            $expectedLines = $this->uniqueRefSnapshotLinesForProductOnBatch($productId, $bn);
            $matchedRef = null;
            foreach ($expectedLines as $el) {
                if ((int) ($el['unique_referencenumber_id'] ?? 0) === $urId) {
                    $matchedRef = (int) $el['available_ref_qty'];
                    break;
                }
            }
            if ($matchedRef === null) {
                return [
                    'ok' => false,
                    'message' => "Unique reference #{$urId} is not valid for this product on batch {$bn}.",
                    'snapshot' => [],
                ];
            }

            $normalized[] = [
                'batch_no' => $bn,
                'unique_referencenumber_id' => $urId,
                'available_ref_qty' => $matchedRef,
            ];
            $batchesFromLines[] = $bn;
        }

        if ($normalized === []) {
            return [
                'ok' => false,
                'message' => 'No valid reference lines in ref selection. Open Available ref quantity, select lines, and click Apply.',
                'snapshot' => [],
            ];
        }

        $batchesFromLines = array_values(array_unique($batchesFromLines));
        sort($batchesFromLines);
        $sortedForm = $batchNos;
        sort($sortedForm);
        if ($batchesFromLines !== $sortedForm) {
            return [
                'ok' => false,
                'message' => 'Batches in the form must match the supplier invoices selected in the popup (click Apply after selecting).',
                'snapshot' => [],
            ];
        }

        $sumRef = 0;
        foreach ($normalized as $n) {
            $sumRef += (int) $n['available_ref_qty'];
        }
        if ($quantity > $sumRef) {
            return [
                'ok' => false,
                'message' => "Swapping quantity ({$quantity}) is greater than total ref from selected lines ({$sumRef}). Select more line(s) or reduce quantity.",
                'snapshot' => [],
            ];
        }

        $snapshotForDb = [];
        foreach ($normalized as $n) {
            $snapshotForDb[] = [
                'batch_no' => $n['batch_no'],
                'unique_referencenumber_id' => (int) $n['unique_referencenumber_id'],
            ];
        }

        return ['ok' => true, 'message' => null, 'snapshot' => $snapshotForDb];
    }

    /**
     * Total packable ref across selected batches: sum of unique_referencenumber_product.remaining_qty
     * (packing list / Available ref quantity — not SIP + legacy + swap).
     */
    protected function totalRefQtyForPackingLine(int $productId, $batchNos): int
    {
        $batchNos = array_values(array_unique(array_filter(is_array($batchNos) ? $batchNos : (array) $batchNos)));
        if (empty($batchNos) || !$productId) {
            return 0;
        }

        $total = 0;
        foreach ($batchNos as $bn) {
            foreach ($this->uniqueRefSnapshotLinesForProductOnBatch($productId, trim((string) $bn)) as $line) {
                $total += (int) ($line['available_ref_qty'] ?? 0);
            }
        }

        return $total;
    }

    public function getSupplierInvoices(Request $request)
    {
        $product_id = $request->product_id;
        $batch_no   = $request->batch_no;

        $invoices = DB::table('supplier_invoice_products as sip')
            ->join('supplier_invoices as si', 'si.id', '=', 'sip.supplier_invoice_id')
            ->where('sip.product_id', $product_id)
            ->where('si.batch_no', $batch_no)
            ->where('si.is_approved', 1)
            ->where('sip.ref_quantity', '>', 0)
            ->select('si.id as supplier_invoice_id', 'si.supplier_invoice_number', DB::raw('sip.ref_quantity as available_qty'), 'si.batch_no')
            ->get();

        return response()->json($invoices);
    }


    // public function stockedit($id){
    //       $invoice = invoice::where('id', $id)->first();
    //                 $StockoutRequestBackup = StockoutRequestBackup::where('invoice_id',$id)->first();
    //       $StockoutRequestProduct = StockoutRequestProduct::where('backup_id',$StockoutRequestBackup->id)->with('product')->get();
    //     return view('stockout/edit',compact('invoice','StockoutRequestBackup','StockoutRequestProduct'));
    // }


    public function stockedit($id)
    {
        // Get invoice
        $invoice = invoice::find($id);
        if (!$invoice) {
            return redirect('/stockout')->with('error', 'Invoice not found.');
        }

        // Get existing backup
        $backup = StockoutRequestBackup::where('invoice_id', $id)->first();

        // Get products of this backup with their product details
        $products = collect();
        if ($backup) {
            $products = StockoutRequestProduct::where('backup_id', $backup->id)
                ->with('product')
                ->get();
        }

        // Build consumable errors grouped by product
        $customConsumableErrors = [];

        foreach ($products as $prod) {
            $product = $prod->product;
            if (!$product) continue;

            $errorsPerProduct = [];

            // Get all consumables for this product
            $wfConsumables = WfConsumable::where('product_id', $product->id)
                ->with('consumable')
                ->get();

            foreach ($wfConsumables as $wf) {
                $requiredQty = $wf->qty * $prod->receiveqty;
                $consumable = $wf->consumable;
                if (!$consumable) continue;

                $availableQty = $consumable->quantity ?? 0;

                // Only add if shortage
                if ($requiredQty > $availableQty) {
                    $errorsPerProduct[] = [
                        'consumable_name' => $consumable->name,
                        'required_qty' => $requiredQty,
                        'available_qty' => $availableQty,
                        'main_quantity' => $availableQty
                    ];
                }
            }

            if (!empty($errorsPerProduct)) {
                $customConsumableErrors[$product->id] = [
                    'product_code' => $product->code ?? $product->id,
                    'product_name' => $product->name,
                    'consumables' => $errorsPerProduct
                ];
            }
        }

        return view('stockout/edit', compact(
            'invoice',
            'backup',
            'products',
            'customConsumableErrors'
        ));
    }





    public function exportcsv(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];

        return (new InvoiceExport($fsd, $fed))->download('invoice.xlsx');
    }
    public function saleRegister_csv(Request $request)
    {
        $data = $request->all();

        $fesd = $data['fersd'];
        $feed = $data['ferd'];

        return (new InvoiceExportSalesRegister($fesd, $feed))->download('sale-register.xlsx');
    }

    public function exportSalesSheet(Request $request)
    {
        $fesd = $request['fesd'];
        $feed = $request['feed'];

        return (new InvoiceExportSales($fesd, $feed))->download('invoice.xlsx');
    }

    public function exportEachCSV($id)
    {

        return (new EachInvoiceExport($id))->download('invoiceTable.xlsx');
    }

    public function storeInvoiceInwardFileApi($id, $shipmentNumber)
    {

        $filePath = 'invoices/invoiceTable #' . $shipmentNumber . '.xlsx';
        Excel::store(new EachInvoiceExport($id), $filePath, 'public');

        return true;
    }

    public function exportEachCSVApi($id, $site)
    {

        $filePath = 'invoices/invoiceTable #' . $id . '.xlsx';

        $fullPath = storage_path('app/public/' . $filePath);

        $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array)
            {
                return $array;
            }
        }, $fullPath);

        $invoiceFileName = "invoiceTable #" . $id . ".xlsx";

        ErpSheet::create(['name' => "$invoiceFileName", 'date' => date('Y-m-d'), 'type' => 'add', 'site_access' => "$site"]);

        Excel::import(new ErpProductAddImportApi("$site", 'auto pushed via CRON'), $fullPath);

        ErpSheet::where('name', $invoiceFileName)->where('site_access', "$site")->update(['reverted' => 0]);
    }

    public function revertCsvApi($id, $site)
    {
        $filePath = 'invoices/invoiceTable #' . $id . '.xlsx';

        $fullPath = storage_path('app/public/' . $filePath);

        // Read the Excel file into an array
        $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array)
            {
                return $array;
            }
        }, $fullPath);

        $invoiceFileName = "invoiceTable #" . $id . ".xlsx";

        ErpSheet::create(['name' => "$invoiceFileName", 'date' => date('Y-m-d'), 'type' => 'less', 'site_access' => "$site"]);
        Excel::import(new ErpProductRevertImportApi("$site", 'reverted'), $fullPath);

        ErpSheet::where('name', $invoiceFileName)->where('site_access', "$site")->update(['reverted' => 1]);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    public function updateErpSkuApi($location, $sku, $quantity, $reason, $remarks)
    {

        $product = ErpProduct::where('sku', $sku)
                    ->where('site_access', $location)
                    ->first();
        
        $diff_quant = $quantity - $product->quantity;
        $type = 'add';

        $product->quantity = $quantity;

        $product->save();



        if ($diff_quant > 0) {
            $history = new ErpHistory;
            $history->sheet_id = 0;
            $history->site_access = $location;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $reason;
            $history->remark = urldecode($remarks);
            $history->stock = $product->quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant =  $product->warehouse_quantity - $quantity;
        $data = ['id' => $product->id, 'quantity' => $product->quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
        die;
    }

    public function processOutboundDropshipCsvApi($filename, $site)
    {

        $filename = urldecode($filename);

        $externalFileUrl = "https://www.artisanfurniture.net/wp-content/uploads/order_csv/" . $filename . ".csv";

        if( $site === "us" || $site === "california" ) {

            $externalFileUrl = "https://www.artisanfurniture.us/wp-content/uploads/order_csv/" . $filename . ".csv";

            if (str_contains(strtolower($filename), 'wayfair')) {
                $externalFileUrl = "https://www.artisanerp.net/public_html/storage/" . $filename . ".csv";
            }
        }


        if( $site === "eu" ) {

            $externalFileUrl = "https://www.artisanfurniture.eu/wp-content/uploads/order_csv/" . $filename . ".csv";
        }

        $response = Http::get($externalFileUrl);

        if ($response->successful()) {
            // Store the content temporarily
            $tempFilePath = storage_path('app/temp_file.csv');
            file_put_contents($tempFilePath, $response->body());
            

            // Read the CSV content
            $data = Excel::toArray(new class implements ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            }, $tempFilePath);

            $filename = $filename . ".csv";

            $erpSheetType = 0;

            if (stripos($filename, 'wayfair') !== false) {
               $erpSheetType = 1;
            }

            $ddx = ErpSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less', 'site_access' => "$site", 'erp_sheet_type' => $erpSheetType]);
            $xdp = Excel::import(new ErpProductLessImportApi("$site"), $tempFilePath);

            ErpSheet::where('name', $filename)->where('type', 'less')->where('site_access', "$site")->update(['reverted' => 0]);

            // start qty update logic here

            $data = \Illuminate\Support\Facades\DB::table('sku_fulfillment_qtys')
                    ->select('sku', \Illuminate\Support\Facades\DB::raw('SUM(qty) as total_qty'))
                    ->where('site_access', $site)
                    ->groupBy('sku')
                    ->orderBy('sku', 'desc')
                    ->get();

            foreach ($data as $row) {
                $sku = $row->sku;
                $totalQty = $row->total_qty;

                \Illuminate\Support\Facades\DB::update("
                    UPDATE erp_products 
                    SET fullfillment_qty = ? 
                    WHERE sku = ? 
                    AND site_access = ?
                ", [$totalQty, $sku, $site]);
                
            } 

            unlink($tempFilePath);
        }
    }

    public function processRevertOutboundDropshipCsvApi($filename, $site)
    {

        $filename = urldecode($filename);

        $externalFileUrl = "https://www.artisanfurniture.net/wp-content/uploads/order_csv/" . $filename . ".csv";

        if( $site === "us" || $site === "california" ) {

            $externalFileUrl = "https://www.artisanfurniture.us/wp-content/uploads/order_csv/" . $filename . ".csv";

            if (str_contains(strtolower($filename), 'wayfair')) {
                $externalFileUrl = "https://www.artisanerp.net/public_html/storage/" . $filename . ".csv";
            }
        }


        if( $site === "eu" ) {

            $externalFileUrl = "https://www.artisanfurniture.eu/wp-content/uploads/order_csv/" . $filename . ".csv";
        }

        $response = Http::get($externalFileUrl);

        if ($response->successful()) {
            // Store the content temporarily
            $tempFilePath = storage_path('app/temp_file.csv');
            file_put_contents($tempFilePath, $response->body());

            // Read the CSV content
            $data = Excel::toArray(new class implements ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            }, $tempFilePath);

            $filename = $filename . ".csv";

            $erpSheetType = 0;

            if (stripos($filename, 'wayfair') !== false) {
               $erpSheetType = 1;
            }

            ErpSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add', 'site_access' => "$site", 'erp_sheet_type' => $erpSheetType]);
            Excel::import(new ErpRevertProductAddImportApi("$site"), $tempFilePath);

            ErpSheet::where('name', $filename)->where('type', 'less')->where('site_access', "$site")->update(['reverted' => 1]);

            // start qty update logic here

            $data = \Illuminate\Support\Facades\DB::table('sku_fulfillment_qtys')
                    ->select('sku', \Illuminate\Support\Facades\DB::raw('SUM(qty) as total_qty'))
                    ->where('site_access', $site)
                    ->groupBy('sku')
                    ->orderBy('sku', 'desc')
                    ->get();

            foreach ($data as $row) {
                $sku = $row->sku;
                $totalQty = $row->total_qty;

                \Illuminate\Support\Facades\DB::update("
                    UPDATE erp_products 
                    SET fullfillment_qty = ? 
                    WHERE sku = ? 
                    AND site_access = ?
                ", [$totalQty, $sku, $site]);
                
            } 

            unlink($tempFilePath);
        }
    }

    public function exportsalesBillForm(Request $request)
    {
        $invoice_ids = $request['invoice_ids'];
        return (new exportsalesBill($invoice_ids))->download('exportsales.xlsx');
    }
    public function saleRegister(Request $request)
    {
        $id = $request->id;
        $invoices = invoice::find($id);
        return view('invoice/sale_register', ['invoices' => $invoices]);
    }
    public function view($id)
    {

        $invoice = invoice::find($id);
        $dischargePorts = Port::get();

        //if($invoice->status !=1){
        $product = product::all();
        $buyer = buyer::all();
        $invoiceTable = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;
        if ($invoice) {
            $companySetting = setting::get()->first();
            return view('invoice/view', [
                'invoice' => $invoice,
                'product' => $product,
                'buyer' => $buyer,
                'consignee' => $consignee,
                'invoiceTable' => $invoiceTable,
                'dischargePorts' => $dischargePorts,
                'declarationExport' => $companySetting->invoice_declaration_export ?? '',
                'declarationLocal' => $companySetting->invoice_declaration_local ?? '',
            ]);
        } else {
            return redirect('/invoice')->with('danger', 'invoice was not found.');
        }


        //}
        //else {
        //   return redirect('/invoice')->with('danger', 'invoice is complete & cannot be edited.');
        //}
    }

    public function view_uk($id)
    {

        $invoice = invoiceuk::find($id);
        //if($invoice->status !=1){
        $product = product::all();
        $buyer = buyer::where('is_uk', 1)->get();
        $invoiceTable = invoiceTableUk::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;
        if ($invoice) {
            return view('invoice/view_uk', ['invoice' => $invoice, 'product' => $product, 'buyer' => $buyer, 'consignee' => $consignee, 'invoiceTable' => $invoiceTable]);
        } else {
            return redirect('/invoice')->with('danger', 'invoice was not found.');
        }

        //}
        //else {
        //   return redirect('/invoice')->with('danger', 'invoice is complete & cannot be edited.');
        //}
    }

    public function view_us($id)
    {

        $invoice = invoiceus::find($id);
        //if($invoice->status !=1){
        $product = product::all();
        $buyer = buyer::where('is_us', 1)->get();
        $invoiceTable = invoiceTableUs::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;
        if ($invoice) {
            return view('invoice/view_us', ['invoice' => $invoice, 'product' => $product, 'buyer' => $buyer, 'consignee' => $consignee, 'invoiceTable' => $invoiceTable]);
        } else {
            return redirect('/invoice')->with('danger', 'invoice was not found.');
        }

        //}
        //else {
        //   return redirect('/invoice')->with('danger', 'invoice is complete & cannot be edited.');
        //}
    }

    public function view_eu($id)
    {

        $invoice = invoiceeu::find($id);
        //if($invoice->status !=1){
        $product = product::all();
        $buyer = buyer::where('is_eu', 1)->get();
        $invoiceTable = invoiceTableEu::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;
        if ($invoice) {
            return view('invoice/view_eu', ['invoice' => $invoice, 'product' => $product, 'buyer' => $buyer, 'consignee' => $consignee, 'invoiceTable' => $invoiceTable]);
        } else {
            return redirect('/invoice')->with('danger', 'invoice was not found.');
        }

        //}
        //else {
        //   return redirect('/invoice')->with('danger', 'invoice is complete & cannot be edited.');
        //}
    }

    public function view_canada($id)
    {

        $invoice = invoicecanada::find($id);
        //if($invoice->status !=1){
        $product = product::all();
        $buyer = buyer::where('is_canada', 1)->get();
        $invoiceTable = invoiceTableCanada::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;
        if ($invoice) {
            return view('invoice/view_canada', ['invoice' => $invoice, 'product' => $product, 'buyer' => $buyer, 'consignee' => $consignee, 'invoiceTable' => $invoiceTable]);
        } else {
            return redirect('/invoice')->with('danger', 'invoice was not found.');
        }

        //}
        //else {
        //   return redirect('/invoice')->with('danger', 'invoice is complete & cannot be edited.');
        //}
    }

    public function view_california($id)
    {

        $invoice = invoicecalifornia::find($id);
        //if($invoice->status !=1){
        $product = product::all();
        $buyer = buyer::where('is_california', 1)->get();
        $invoiceTable = invoiceTableCalifornia::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;
        if ($invoice) {
            return view('invoice/view_california', ['invoice' => $invoice, 'product' => $product, 'buyer' => $buyer, 'consignee' => $consignee, 'invoiceTable' => $invoiceTable]);
        } else {
            return redirect('/invoice')->with('danger', 'invoice was not found.');
        }

        //}
        //else {
        //   return redirect('/invoice')->with('danger', 'invoice is complete & cannot be edited.');
        //}
    }

    public function create()
    {
        $buyer = buyer::where('is_uk', 0)->get();
        $dischargePorts = Port::get();
        $consignee = $buyer;
        $packing = packingList::where('is_sent_for_invoice', 1)->get();
        $allProducts = product::all();
        $companySetting = setting::get()->first();
        return view('invoice/create', [
            'buyer' => $buyer,
            'consignee' => $consignee,
            'packing' => $packing,
            'dischargePorts' => $dischargePorts,
            'allProducts' => $allProducts,
            'declarationExport' => $companySetting->invoice_declaration_export ?? '',
            'declarationLocal' => $companySetting->invoice_declaration_local ?? '',
        ]);
    }

    public function create_uk(Request $request)
    {
        $packing = packingList::get();
        $invoice = invoice::find($request->invoice_id);
        $product = product::all();
        $buyer = buyer::where('is_uk', 1)->get();
        $invoiceTable = invoiceTable::where('invoice_id', $request->invoice_id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;

        return view('invoice/create_uk', ['buyer' => $buyer, 'consignee' => $consignee, 'packing' => $packing, 'invoice' => $invoice, 'invoiceTable' => $invoiceTable]);
    }

    public function create_us(Request $request)
    {
        $packing = packingList::get();
        $invoice = invoice::find($request->invoice_id);
        $product = product::all();
        $buyer = buyer::where('is_us', 1)->get();
        $invoiceTable = invoiceTable::where('invoice_id', $request->invoice_id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;

        return view('invoice/create_us', ['buyer' => $buyer, 'consignee' => $consignee, 'packing' => $packing, 'invoice' => $invoice, 'invoiceTable' => $invoiceTable]);
    }

    public function create_eu(Request $request)
    {
        $packing = packingList::get();
        $invoice = invoice::find($request->invoice_id);
        $product = product::all();
        $buyer = buyer::where('is_eu', 1)->get();
        $invoiceTable = invoiceTable::where('invoice_id', $request->invoice_id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;

        return view('invoice/create_eu', ['buyer' => $buyer, 'consignee' => $consignee, 'packing' => $packing, 'invoice' => $invoice, 'invoiceTable' => $invoiceTable]);
    }
    public function store(Request $request)
    {
        $invoiceNo = strtoupper($request['invoiceno']);

        if (strpos($invoiceNo, 'CARTON') === false) {
            $packing_list = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();

            $packing_list_products = packingListProduct::where('packinglist_id', $packing_list->id ?? 0)->get();

            $packing_list_productsinvoice = packingListProduct::where('packinglist_id', $packing_list->id)->pluck('product_id')->toArray();

            foreach ($request['inv'] as $inv) {
                $invoiceProductID = $inv['product'];
                $product = product::find($invoiceProductID);
                if (!in_array($invoiceProductID, $packing_list_productsinvoice)) {
                    return redirect()->back()
                        ->with('custom_error', 'Product ID ' . $product->code . ' is not included in the packing list. Invoice cannot be created.')
                        ->withInput();
                }
            }



            foreach ($request['inv'] as $inv) {

                $invoiceProductID = $inv['product'];
                $invoiceQty = $inv['quantity'];

                $plp = $packing_list_products->where('product_id', $invoiceProductID)->first();
                $product = product::find($invoiceProductID);
                if ($plp && $invoiceQty > $plp->quantity) {
                    return redirect()->back()
                        ->with('custom_error', 'Invoice quantity (' . $invoiceQty . ') cannot be greater than packing list quantity (' . $plp->quantity . ') for product ID: ' . $product->code)
                        ->withInput();
                }
            }



            foreach ($packing_list_products as $plp) {

                $totalBatchQty = 0;

                $batchNumbers = $this->batchNoListFromPlpColumn($plp->batch_no);

                foreach ($batchNumbers as $batchNo) {

                    $batch = Batch::where('batch_no', $batchNo)->first();

                    if (!$batch) continue;


                    $batchProduct = BatchProduct::where('product_id', $plp->product_id)
                        ->where('batch_id', $batch->id)
                        ->first();

                    if ($batchProduct) {
                        $totalBatchQty += $batchProduct->quantity;
                    }
                }
                $product = product::find($plp->product_id);
                if ($totalBatchQty <  $plp->quantity) {
                    return redirect()->back()
                        ->with('custom_error', 'Batch  quantity not sufficient for product ID: ' . $product->code)
                        ->withInput();
                }
            }
        }
        $q = invoice::create([
            'consignee_id' => $request['consignee'],
            'buyer_id' => $request['buyer_id'],
            'date' => $request['date'],
            'buyerorderno' => strtoupper($request['buyerorderno']),
            'containerno' => strtoupper($request['containerno']),
            'vehicleno' => $request['vehicleno'],
            'totalbox' => $request['totalbox'],
            'pkgs' => $request['pkgs'],
            'currency' => $request['currency'],
            'fob' => $request['fob'],
            'payterms' => $request['payterms'],
            'shipmentby' => $request['shipmentby'],
            'desgoods' => $request['desgoods'],
            'carriage' => $request['carriage'],
            'receipt' => $request['receipt'],
            'shipment' => $request['shipment'],
            'postloading' => $request['postloading'],
            'discharge' => $request['discharge'],
            'destination' => $request['destination'],
            'totalgst' => $request['totalgst'],
            'totalquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'totalgrosswt' => $request['grosswt'],
            'totalamount' => $request['totalamount'],
            'conrate' => $request['cunrate'],
            'rateamount' => $request['rateamount'],
            'invoiceno' => strtoupper($request['invoiceno']),
            'invoicetype' => $request['invoicetype'],
            'exportstatus' => $request['exportstatus'],
            'ewaybillno' => strtoupper($request['ewaybillno']),
            'declaration' => $request['declaration'],
            'shipping_charges' => $request['shipping_charges'],
            'packing_charges' => $request['packing_charges'],
            'discount' => $request['discount'],
            'additional_info' => $request['additional_info'],
            'distance' => $request['distance'],
            'transportertame' => $request['transportertame'],
            'transporterid' => $request['transporterid'],
            'docdate' => $request['docdate']
        ]);

        foreach ($request['inv'] as $inv) {
            invoiceTable::create([
                'product_id' => $inv['product'],
                'invoice_id' => $q->id,
                'quantity' => $inv['quantity'],
                'rate' => $inv['rate'],
                'amount' => $inv['amount'],
                'weight' => $inv['weight'],
                'subtotalnetwt' => $inv['subtotalnetwt'],
                'grosswt' => $inv['grosswt'],
                'subtotalgrosswt' => $inv['subTotalGrossWT'],
                'gstslab' => $inv['gstslab'],
                'box' => $inv['box'],
                'endbox' => $inv['endBox'],
                'subtotalbox' => $inv['subTotalBox'],
                'qtybox' => $inv['qtybox'],
                'gstamount' => $inv['gstamount'],
                'remqty' => $inv['quantity'],
                'descriptionBox' => $inv['descriptionBox']
            ]);
        }

        if ($request['buyerorderno'] != "") {
            $packing = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();
            if (isset($packing->id)) {
                $packing->status = 1;
                $packing->save();
            }
        }
        invexport::create([
            'realisation_date' => NULL,
            'realisation_fc' => NULL,
            'invoice_id' => $q->id,
            'rate' => NULL,
            'bank_reference' => NULL
        ]);


        if ($request['exportstatus'] == 0) {
            if (isset($request['shipping_charges']) && $request['shipping_charges'] > 0) {
                $shipping_gst = $request['shipping_charges'] * .05;
                $q->shipping_gst = $shipping_gst;
                $totalgst = $request['totalgst'] + $shipping_gst;
                $q->totalgst = $totalgst;
                $q->save();
            }
        }

        return redirect('/invoice')->with('success', 'Invoice was added successfully.');
    }

    public function store_uk(Request $request, $invoice_id)
    {
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $q = invoiceuk::create([
            'consignee_id' => $request['consignee'],
            'buyer_id' => $request['buyer_id'],
            'date' => $request['date'],
            'buyerorderno' => strtoupper($request['buyerorderno']),
            'containerno' => strtoupper($request['containerno']),
            'vehicleno' => $request['vehicleno'],
            'totalbox' => $request['totalbox'],
            'pkgs' => $request['pkgs'],
            'currency' => $request['currency'],
            'fob' => $request['fob'],
            'payterms' => $request['payterms'],
            'shipmentby' => $request['shipmentby'],
            'desgoods' => $request['desgoods'],
            'carriage' => $request['carriage'],
            'receipt' => $request['receipt'],
            'shipment' => $request['shipment'],
            'postloading' => $request['postloading'],
            'discharge' => $request['discharge'],
            'destination' => $request['destination'],
            'totalquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'totalgrosswt' => $request['grosswt'],
            'totalamount' => $request['totalamount'],
            'conrate' => $request['cunrate'],
            'rateamount' => $request['rateamount'],
            'invoiceno' => strtoupper($request['invoiceno']),
            'invoicetype' => $request['invoicetype'],
            'ewaybillno' => strtoupper($request['ewaybillno']),
            'declaration' => $request['declaration'],
            'shipping_charges' => $request['shipping_charges'],
            'packing_charges' => $request['packing_charges'],
            'discount' => $request['discount'],
            'additional_info' => $request['additional_info'],
            'bill_to' => $request['bill_to'],
            'ship_to' => $request['ship_to'],
            'deposit_date' => date('Y-m-d', strtotime($request['deposit_date'])),
            'deposit' => $request['deposit'],
            'delivery_term' => $request['delivery_term'],
            'agent_name' => $request['agent_name'],
            'vat' => $request['vat'],
            'container_size' => $request['container_size']
        ]);

        foreach ($request['inv'] as $inv) {
            invoiceTableUk::create([
                'product_id' => $inv['product'],
                'invoice_id' => $q->id,
                'quantity' => $inv['quantity'],
                'rate' => $inv['rate'],
                'amount' => $inv['amount'],
                'weight' => $inv['weight'],
                'subtotalnetwt' => $inv['subtotalnetwt'],
                'grosswt' => $inv['grosswt'],
                'subtotalgrosswt' => $inv['subTotalGrossWT'],
                'box' => $inv['box'],
                'endbox' => $inv['endBox'],
                'subtotalbox' => $inv['subTotalBox'],
                'qtybox' => $inv['qtybox'],
                'remqty' => $inv['quantity'],
                'descriptionBox' => $inv['descriptionBox']
            ]);
        }

        if ($request['buyerorderno'] != "") {
            $packing = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();
            if (isset($packing->id)) {
                $packing->status = 1;
                $packing->save();
            }
        }
        // invexport::create([
        //                                 'realisation_date' => NULL,
        //                                 'realisation_fc' => NULL,
        //                                 'invoice_id' => $q->id,
        //                                 'rate' => NULL,
        //                                 'bank_reference' => NULL
        //                             ]);

        return redirect('/invoices/invoice-uk')->with('success', 'Invoice was added successfully.');
    }

    public function store_us(Request $request, $invoice_id)
    {
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $q = invoiceus::create([
            'consignee_id' => $request['consignee'],
            'buyer_id' => $request['buyer_id'],
            'date' => $request['date'],
            'buyerorderno' => strtoupper($request['buyerorderno']),
            'containerno' => strtoupper($request['containerno']),
            'vehicleno' => $request['vehicleno'],
            'totalbox' => $request['totalbox'],
            'pkgs' => $request['pkgs'],
            'currency' => $request['currency'],
            'fob' => $request['fob'],
            'payterms' => $request['payterms'],
            'shipmentby' => $request['shipmentby'],
            'desgoods' => $request['desgoods'],
            'carriage' => $request['carriage'],
            'receipt' => $request['receipt'],
            'shipment' => $request['shipment'],
            'postloading' => $request['postloading'],
            'discharge' => $request['discharge'],
            'destination' => $request['destination'],
            'totalquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'totalgrosswt' => $request['grosswt'],
            'totalamount' => $request['totalamount'],
            'conrate' => $request['cunrate'],
            'rateamount' => $request['rateamount'],
            'invoiceno' => strtoupper($request['invoiceno']),
            'invoicetype' => $request['invoicetype'],
            'ewaybillno' => strtoupper($request['ewaybillno']),
            'declaration' => $request['declaration'],
            'shipping_charges' => $request['shipping_charges'],
            'packing_charges' => $request['packing_charges'],
            'discount' => $request['discount'],
            'additional_info' => $request['additional_info'],
            'bill_to' => $request['bill_to'],
            'ship_to' => $request['ship_to'],
            'deposit_date' => date('Y-m-d', strtotime($request['deposit_date'])),
            'deposit' => $request['deposit'],
            'delivery_term' => $request['delivery_term'],
            'agent_name' => $request['agent_name'],
            'vat' => $request['vat'],
            'container_size' => $request['container_size']
        ]);

        foreach ($request['inv'] as $inv) {
            invoiceTableUs::create([
                'product_id' => $inv['product'],
                'invoice_id' => $q->id,
                'quantity' => $inv['quantity'],
                'rate' => $inv['rate'],
                'amount' => $inv['amount'],
                'weight' => $inv['weight'],
                'subtotalnetwt' => $inv['subtotalnetwt'],
                'grosswt' => $inv['grosswt'],
                'subtotalgrosswt' => $inv['subTotalGrossWT'],
                'box' => $inv['box'],
                'endbox' => $inv['endBox'],
                'subtotalbox' => $inv['subTotalBox'],
                'qtybox' => $inv['qtybox'],
                'remqty' => $inv['quantity'],
                'descriptionBox' => $inv['descriptionBox']
            ]);
        }

        if ($request['buyerorderno'] != "") {
            $packing = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();
            if (isset($packing->id)) {
                $packing->status = 1;
                $packing->save();
            }
        }
        // invexport::create([
        //                                 'realisation_date' => NULL,
        //                                 'realisation_fc' => NULL,
        //                                 'invoice_id' => $q->id,
        //                                 'rate' => NULL,
        //                                 'bank_reference' => NULL
        //                             ]);

        return redirect('/invoices/invoice-us')->with('success', 'Invoice was added successfully.');
    }

    public function store_sale_register(Request $request)
    {

        $invoice = invoice::where('id', $request->invoice_id)->first();
        $invoice->bl_no = $request->bl_no;
        $invoice->bl_date = $request->bl_date;
        $invoice->shipping_bill_no = $request->shipping_bill_no;
        $invoice->containerno = $request->containerno;
        $invoice->egm_date = $request->egm_date;
        $invoice->egm_no = $request->egm_no;
        $invoice->agent_name = $request->agent_name;
        $invoice->eta = $request->eta;
        $invoice->etd = $request->etd;

        $invoice->booking_value      = @$request['booking_value'];
        $invoice->fbc    = @$request['fbc'];
        $invoice->shipdawn            = @$request['shipdawn'];
        $invoice->inrat_booking_value          = @$request['inrat_booking_value'];
        $invoice->tax_type             = @$request['tax_type'];
        /*dd($invoice->booking_value);*/
        $invoice->save();

        $ieExport = invexport::where('invoice_id', $request['id'])->get();

        if (isset($ieExport)) {
            foreach ($ieExport as $ieExport) {
                $ieExport->delete();
            }
        }
        if (isset($request['ie'])) {
            foreach ($request['ie'] as $ie) {

                invexport::create([
                    'realisation_date' => $ie['realisation_date'],
                    'realisation_fc' => $ie['realisation_fc'],
                    'invoice_id' => $request['id'],
                    'rate' => $ie['rate'],
                    'bank_reference' => $ie['bank_reference'],

                ]);
            }
        }

        return redirect('/invoice')->with('success', 'Added successfully.');
    }
    public function updateinvoice(Request $request, $id)
    {
        $invoice = invoice::where('id', $id)->first();
        $stocklog = stockLog::where('voucher_no', $invoice->invoiceno)->get();
        foreach ($stocklog as $stocks) {
            $stocks->voucher_no = $request->invoiceno;
            $stocks->save();
        }
        $invoiceProduct = invoiceTable::where('invoice_id', $id)->get();
        //dd($request);
        //if ($invoice && $invoice->status!=1) {
        if (!isset($request['shipping_charges'])) {
            $request['shipping_charges'] = 0;
        }
        if (!isset($request['packing_charges'])) {
            $request['packing_charges'] = 0;
        }
        if (!isset($request['discount'])) {
            $request['discount'] = 0;
        }
        $invoice->consignee_id = $request['consignee'];
        $invoice->buyer_id = $request['buyer_id'];
        $invoice->date = $request['date'];
        $invoice->buyerorderno = strtoupper($request['buyerorderno']);
        $invoice->containerno = strtoupper($request['containerno']);
        $invoice->vehicleno = $request['vehicleno'];
        $invoice->totalbox = $request['totalbox'];
        $invoice->pkgs = $request['pkgs'];
        $invoice->currency = $request['currency'];
        $invoice->fob = $request['fob'];
        $invoice->payterms = $request['payterms'];
        $invoice->shipmentby = $request['shipmentby'];
        $invoice->desgoods = $request['desgoods'];
        $invoice->carriage = $request['carriage'];
        $invoice->receipt = $request['receipt'];
        $invoice->shipment = $request['shipment'];
        $invoice->postloading = $request['postloading'];
        $invoice->discharge = $request['discharge'];
        $invoice->destination = $request['destination'];
        $invoice->totalgst = $request['totalgst'];
        $invoice->totalquantity = $request['tquantity'];
        $invoice->totalwt = $request['totalwt'];
        $invoice->totalgrosswt = $request['grosswt'];
        $invoice->totalamount = $request['totalamount'];
        $invoice->conrate = $request['cunrate'];
        $invoice->rateamount = $request['rateamount'];
        $invoice->invoiceno = $request['invoiceno'];
        $invoice->invoicetype = $request['invoicetype'];
        $invoice->exportstatus = $request['exportstatus'];
        $invoice->ewaybillno = $request['ewaybillno'];
        $invoice->declaration = $request['declaration'];
        $invoice->shipping_charges = $request['shipping_charges'];
        $invoice->packing_charges = $request['packing_charges'];
        $invoice->discount = $request['discount'];
        $invoice->additional_info = $request['additional_info'];
        $invoice->distance = $request['distance'];
        $invoice->transportertame = $request['transportertame'];
        $invoice->transporterid = $request['transporterid'];
        $invoice->docdate = $request['docdate'];

        foreach ($invoiceProduct as $invoiceProduct) {
            $invoiceProduct->delete();
        }

        foreach ($request['inv'] as $inv) {
            if (!isset($inv['gstamount'])) {
                $inv['gstamount'] = 0;
            }
            if (!isset($inv['consumed'])) {
                $inv['consumed'] = '';
            }
            if ($inv['consumed'] == '') {
                invoiceTable::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'gstslab' => $inv['gstslab'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'gstamount' => $inv['gstamount'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            } else {
                invoiceTable::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'] - $inv['consumed'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'gstslab' => $inv['gstslab'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'gstamount' => $inv['gstamount'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            }
        }

        $x = invoiceTable::where('invoice_id', $id)->where('remqty', '>', 0)->first();
        if (isset($x)) {
            $invoice->status = 0;
            $invoice->update();
        }


        if ($invoice->save()) {
            return back()->with('success', 'Invoice updated successfully.');
        } else {
            return back()->with('danger', 'Error occurred while saving invoice.');
        }
        //} else {
        //  return redirect('/invoice')->with('danger', 'invoice was not found.');
        //}
    }

    public function updateinvoiceUk(Request $request, $id)
    {
        $invoice = invoiceuk::where('id', $id)->first();
        $invoiceProduct = invoiceTableUk::where('invoice_id', $id)->get();
        //dd($request);
        //if ($invoice && $invoice->status!=1) {
        if (!isset($request['shipping_charges'])) {
            $request['shipping_charges'] = 0;
        }
        if (!isset($request['packing_charges'])) {
            $request['packing_charges'] = 0;
        }
        if (!isset($request['discount'])) {
            $request['discount'] = 0;
        }
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $invoice->consignee_id = $request['consignee'];
        $invoice->buyer_id = $request['buyer_id'];
        $invoice->date = $request['date'];
        $invoice->buyerorderno = strtoupper($request['buyerorderno']);
        $invoice->containerno = strtoupper($request['containerno']);
        $invoice->vehicleno = $request['vehicleno'];
        $invoice->totalbox = $request['totalbox'];
        $invoice->pkgs = $request['pkgs'];
        $invoice->currency = $request['currency'];
        $invoice->fob = $request['fob'];
        $invoice->payterms = $request['payterms'];
        $invoice->shipmentby = $request['shipmentby'];
        $invoice->desgoods = $request['desgoods'];
        $invoice->carriage = $request['carriage'];
        $invoice->receipt = $request['receipt'];
        $invoice->shipment = $request['shipment'];
        $invoice->postloading = $request['postloading'];
        $invoice->discharge = $request['discharge'];
        $invoice->destination = $request['destination'];
        $invoice->totalquantity = $request['tquantity'];
        $invoice->totalwt = $request['totalwt'];
        $invoice->totalgrosswt = $request['grosswt'];
        $invoice->totalamount = $request['totalamount'];
        $invoice->conrate = $request['cunrate'];
        $invoice->rateamount = $request['rateamount'];
        $invoice->invoiceno = $request['invoiceno'];
        $invoice->invoicetype = $request['invoicetype'];
        $invoice->exportstatus = $request['exportstatus'];
        $invoice->ewaybillno = $request['ewaybillno'];
        $invoice->declaration = $request['declaration'];
        $invoice->shipping_charges = $request['shipping_charges'];
        $invoice->packing_charges = $request['packing_charges'];
        $invoice->discount = $request['discount'];
        $invoice->additional_info = $request['additional_info'];
        $invoice->bill_to = $request['bill_to'];
        $invoice->ship_to = $request['ship_to'];
        $invoice->deposit_date = date('Y-m-d', strtotime($request['deposit_date']));
        $invoice->deposit = $request['deposit'];
        $invoice->delivery_term = $request['delivery_term'];
        $invoice->agent_name = $request['agent_name'];
        $invoice->vat = $request['vat'];
        $invoice->container_size = $request['container_size'];

        foreach ($invoiceProduct as $invoiceProduct) {
            $invoiceProduct->delete();
        }

        foreach ($request['inv'] as $inv) {
            if (!isset($inv['gstamount'])) {
                $inv['gstamount'] = 0;
            }
            if (!isset($inv['consumed'])) {
                $inv['consumed'] = '';
            }
            if ($inv['consumed'] == '') {
                invoiceTableUk::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            } else {
                invoiceTableUk::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'] - $inv['consumed'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            }
        }

        $x = invoiceTableUk::where('invoice_id', $id)->where('remqty', '>', 0)->first();
        if (isset($x)) {
            $invoice->status = 0;
            $invoice->update();
        }


        if ($invoice->save()) {
            return back()->with('success', 'Invoice updated successfully.');
        } else {
            return back()->with('danger', 'Error occurred while saving invoice.');
        }
        //} else {
        //  return redirect('/invoice')->with('danger', 'invoice was not found.');
        //}
    }

    public function updateinvoiceUs(Request $request, $id)
    {
        $invoice = invoiceus::where('id', $id)->first();
        $invoiceProduct = invoiceTableUs::where('invoice_id', $id)->get();
        //dd($request);
        //if ($invoice && $invoice->status!=1) {
        if (!isset($request['shipping_charges'])) {
            $request['shipping_charges'] = 0;
        }
        if (!isset($request['packing_charges'])) {
            $request['packing_charges'] = 0;
        }
        if (!isset($request['discount'])) {
            $request['discount'] = 0;
        }
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $invoice->consignee_id = $request['consignee'];
        $invoice->buyer_id = $request['buyer_id'];
        $invoice->date = $request['date'];
        $invoice->buyerorderno = strtoupper($request['buyerorderno']);
        $invoice->containerno = strtoupper($request['containerno']);
        $invoice->vehicleno = $request['vehicleno'];
        $invoice->totalbox = $request['totalbox'];
        $invoice->pkgs = $request['pkgs'];
        $invoice->currency = $request['currency'];
        $invoice->fob = $request['fob'];
        $invoice->payterms = $request['payterms'];
        $invoice->shipmentby = $request['shipmentby'];
        $invoice->desgoods = $request['desgoods'];
        $invoice->carriage = $request['carriage'];
        $invoice->receipt = $request['receipt'];
        $invoice->shipment = $request['shipment'];
        $invoice->postloading = $request['postloading'];
        $invoice->discharge = $request['discharge'];
        $invoice->destination = $request['destination'];
        $invoice->totalquantity = $request['tquantity'];
        $invoice->totalwt = $request['totalwt'];
        $invoice->totalgrosswt = $request['grosswt'];
        $invoice->totalamount = $request['totalamount'];
        $invoice->conrate = $request['cunrate'];
        $invoice->rateamount = $request['rateamount'];
        $invoice->invoiceno = $request['invoiceno'];
        $invoice->invoicetype = $request['invoicetype'];
        $invoice->exportstatus = $request['exportstatus'];
        $invoice->ewaybillno = $request['ewaybillno'];
        $invoice->declaration = $request['declaration'];
        $invoice->shipping_charges = $request['shipping_charges'];
        $invoice->packing_charges = $request['packing_charges'];
        $invoice->discount = $request['discount'];
        $invoice->additional_info = $request['additional_info'];
        $invoice->bill_to = $request['bill_to'];
        $invoice->ship_to = $request['ship_to'];
        $invoice->deposit_date = date('Y-m-d', strtotime($request['deposit_date']));
        $invoice->deposit = $request['deposit'];
        $invoice->delivery_term = $request['delivery_term'];
        $invoice->agent_name = $request['agent_name'];
        $invoice->vat = $request['vat'];
        $invoice->container_size = $request['container_size'];

        foreach ($invoiceProduct as $invoiceProduct) {
            $invoiceProduct->delete();
        }

        foreach ($request['inv'] as $inv) {
            if (!isset($inv['gstamount'])) {
                $inv['gstamount'] = 0;
            }
            if (!isset($inv['consumed'])) {
                $inv['consumed'] = '';
            }
            if ($inv['consumed'] == '') {
                invoiceTableUs::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            } else {
                invoiceTableUs::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'] - $inv['consumed'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            }
        }

        $x = invoiceTableUs::where('invoice_id', $id)->where('remqty', '>', 0)->first();
        if (isset($x)) {
            $invoice->status = 0;
            $invoice->update();
        }


        if ($invoice->save()) {
            return back()->with('success', 'Invoice updated successfully.');
        } else {
            return back()->with('danger', 'Error occurred while saving invoice.');
        }
        //} else {
        //  return redirect('/invoice')->with('danger', 'invoice was not found.');
        //}
    }

    public function updateinvoiceEu(Request $request, $id)
    {
        $invoice = invoiceeu::where('id', $id)->first();
        $invoiceProduct = invoiceTableEu::where('invoice_id', $id)->get();
        //dd($request);
        //if ($invoice && $invoice->status!=1) {
        if (!isset($request['shipping_charges'])) {
            $request['shipping_charges'] = 0;
        }
        if (!isset($request['packing_charges'])) {
            $request['packing_charges'] = 0;
        }
        if (!isset($request['discount'])) {
            $request['discount'] = 0;
        }
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $invoice->consignee_id = $request['consignee'];
        $invoice->buyer_id = $request['buyer_id'];
        $invoice->date = $request['date'];
        $invoice->buyerorderno = strtoupper($request['buyerorderno']);
        $invoice->containerno = strtoupper($request['containerno']);
        $invoice->vehicleno = $request['vehicleno'];
        $invoice->totalbox = $request['totalbox'];
        $invoice->pkgs = $request['pkgs'];
        $invoice->currency = $request['currency'];
        $invoice->fob = $request['fob'];
        $invoice->payterms = $request['payterms'];
        $invoice->shipmentby = $request['shipmentby'];
        $invoice->desgoods = $request['desgoods'];
        $invoice->carriage = $request['carriage'];
        $invoice->receipt = $request['receipt'];
        $invoice->shipment = $request['shipment'];
        $invoice->postloading = $request['postloading'];
        $invoice->discharge = $request['discharge'];
        $invoice->destination = $request['destination'];
        $invoice->totalquantity = $request['tquantity'];
        $invoice->totalwt = $request['totalwt'];
        $invoice->totalgrosswt = $request['grosswt'];
        $invoice->totalamount = $request['totalamount'];
        $invoice->conrate = $request['cunrate'];
        $invoice->rateamount = $request['rateamount'];
        $invoice->invoiceno = $request['invoiceno'];
        $invoice->invoicetype = $request['invoicetype'];
        $invoice->exportstatus = $request['exportstatus'];
        $invoice->ewaybillno = $request['ewaybillno'];
        $invoice->declaration = $request['declaration'];
        $invoice->shipping_charges = $request['shipping_charges'];
        $invoice->packing_charges = $request['packing_charges'];
        $invoice->discount = $request['discount'];
        $invoice->additional_info = $request['additional_info'];
        $invoice->bill_to = $request['bill_to'];
        $invoice->ship_to = $request['ship_to'];
        $invoice->deposit_date = date('Y-m-d', strtotime($request['deposit_date']));
        $invoice->deposit = $request['deposit'];
        $invoice->delivery_term = $request['delivery_term'];
        $invoice->agent_name = $request['agent_name'];
        $invoice->vat = $request['vat'];
        $invoice->container_size = $request['container_size'];

        foreach ($invoiceProduct as $invoiceProduct) {
            $invoiceProduct->delete();
        }

        foreach ($request['inv'] as $inv) {
            if (!isset($inv['gstamount'])) {
                $inv['gstamount'] = 0;
            }
            if (!isset($inv['consumed'])) {
                $inv['consumed'] = '';
            }
            if ($inv['consumed'] == '') {
                invoiceTableEu::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            } else {
                invoiceTableEu::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'] - $inv['consumed'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            }
        }

        $x = invoiceTableEu::where('invoice_id', $id)->where('remqty', '>', 0)->first();
        if (isset($x)) {
            $invoice->status = 0;
            $invoice->update();
        }


        if ($invoice->save()) {
            return back()->with('success', 'Invoice updated successfully.');
        } else {
            return back()->with('danger', 'Error occurred while saving invoice.');
        }
        //} else {
        //  return redirect('/invoice')->with('danger', 'invoice was not found.');
        //}
    }

    // public function index()
    // {
    //     $invoice=invoice::orderBy('id','DESC')->get();

    // 	$hardwareSuppliers=hardwareSuppliers::get();
    // 	$contractor		=	contractor::get();
    // 	$upcontractor		=	upholstreyContractor::get();
    // 	$companyDetails = setting::first();
    // 	$smhsuppliers = smhsupplier::get();
    // 	$suppliers = supplier::get();
    //   /*  echo "<pre>";
    //     print_r($invoice);die;*/
    //     return view('invoice/index',['invoice'=>$invoice,'hardwareSuppliers'=>$hardwareSuppliers,'contractor'=>$contractor,'upcontractor'=>$upcontractor,'companyDetails'=>$companyDetails,'smhsuppliers'=>$smhsuppliers,'suppliers'=>$suppliers]);
    // }

    public function index(Request $request)
    {
        $invoiceType = $request->input('invoice_type', 'all');

        $invoiceQuery = invoice::with(['stockout', 'invoiceTable']);

        if ($invoiceType === '2') {
            $invoice = $invoiceQuery->whereIn('invoicetype', [2, 3])->orderBy('id', 'DESC')->get();
        } elseif (in_array($invoiceType, ['0', '1'])) {
            $invoice = $invoiceQuery->where('invoicetype', $invoiceType)->orderBy('id', 'DESC')->get();
        } else {
            $invoice = $invoiceQuery->orderBy('id', 'DESC')->get();
        }

        return view('invoice.index', [
            'invoice' => $invoice,
            'hardwareSuppliers' => hardwareSuppliers::get(),
            'contractor' => contractor::get(),
            'upcontractor' => upholstreyContractor::get(),
            'companyDetails' => setting::first(),
            'smhsuppliers' => smhsupplier::get(),
            'suppliers' => supplier::get(),
            'selectedInvoiceType' => $invoiceType,
            'lastPO' => purchaseOrderConsumable::where('pono', 'like', 'M/%')->orderBy('id', 'desc')->first(),
        ]);
    }


    public function cancel(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('cancel-invoice'))) {
            return redirect('/invoice')->with('danger', 'You are not allowed to cancel invoices.');
        }

        $invoice = invoice::with(['stockout', 'invoiceTable'])->where('id', $id)->first();
        if (!$invoice) {
            return redirect('/invoice')->with('danger', 'Invoice not found.');
        }

        if ((int) $invoice->is_canceled === 1) {
            return redirect('/invoice')->with('danger', 'Invoice is already canceled.');
        }

        if (!$invoice->canBeCanceled()) {
            return redirect('/invoice')->with('danger', 'Invoice cannot be canceled. Stock-out exists or remaining quantity is not full.');
        }

        $shouldClone = $request->input('clone') == '1';
        $cloneInvoiceNo = strtoupper(trim((string) $request->input('clone_invoiceno', '')));

        if ($shouldClone) {
            if ($cloneInvoiceNo === '') {
                return redirect('/invoice')->with('danger', 'Please enter the new invoice number to clone.');
            }
            if ($cloneInvoiceNo === strtoupper((string) $invoice->invoiceno)) {
                return redirect('/invoice')->with('danger', 'Cloned invoice number must be different from the canceled invoice.');
            }
            if (invoice::invoiceNumberExists($cloneInvoiceNo)) {
                return redirect('/invoice')->with('danger', 'Invoice number ' . $cloneInvoiceNo . ' already exists.');
            }
        }

        try {
            DB::transaction(function () use ($invoice, $shouldClone, $cloneInvoiceNo) {
                $invoice->is_canceled = 1;
                $invoice->save();

                if ($shouldClone) {
                    $this->cloneCanceledInvoice($invoice, $cloneInvoiceNo);
                }
            });
        } catch (\Throwable $e) {
            \Log::error('Invoice cancel/clone failed: ' . $e->getMessage(), ['invoice_id' => $id]);
            return redirect('/invoice')->with('danger', 'There was an error canceling the Invoice.');
        }

        if ($shouldClone) {
            return redirect('/invoice')->with('success', 'Invoice canceled and cloned as ' . $cloneInvoiceNo . '.');
        }

        return redirect('/invoice')->with('success', 'Invoice canceled successfully.');
    }

    protected function cloneCanceledInvoice(invoice $invoice, string $cloneInvoiceNo): invoice
    {
        $clone = $invoice->replicate();
        $clone->invoiceno = $cloneInvoiceNo;
        $clone->is_canceled = 0;
        $clone->status = 0;

        $resetFields = [
            'tally_status' => 0,
            'send_mail' => 0,
            'irn' => '',
            'ack_no' => '',
            'ack_date' => null,
            'einvoice_qr' => '',
            'lr_rr_no' => '',
            'shipping_bill_no' => null,
            'shipping_bill_date' => null,
            'ewaybillno' => null,
            'ewaybilldate' => null,
            'port_code' => null,
            'shipping_exchange_rate' => null,
            'bl_no' => null,
            'bl_date' => null,
            'egm_no' => null,
            'egm_date' => null,
            'billty_no' => null,
            'billty_date' => null,
        ];
        foreach ($resetFields as $field => $value) {
            if (array_key_exists($field, $clone->getAttributes())) {
                $clone->{$field} = $value;
            }
        }

        $clone->save();

        foreach ($invoice->invoiceTable as $line) {
            $newLine = $line->replicate();
            $newLine->invoice_id = $clone->id;
            $newLine->remqty = $line->quantity;
            $newLine->save();
        }

        invexport::create([
            'realisation_date' => null,
            'realisation_fc' => null,
            'invoice_id' => $clone->id,
            'rate' => null,
            'bank_reference' => null,
        ]);

        return $clone;
    }


    public function invoice_uk()
    {
        $invoice = invoice::where('buyerorderno', 'NOT LIKE', "%UK-18%")->orderBy('id', 'DESC')->get();
        $invoice_uk = invoiceuk::get();

        $hardwareSuppliers = hardwareSuppliers::get();
        $contractor     =   contractor::get();
        $upcontractor       =   upholstreyContractor::get();
        $companyDetails = settinguk::first();
        $smhsuppliers = smhsupplier::get();
        $suppliers = supplier::get();
        /*  echo "<pre>";
        print_r($invoice);die;*/
        return view('invoice/index_uk', ['invoice' => $invoice, 'hardwareSuppliers' => $hardwareSuppliers, 'contractor' => $contractor, 'upcontractor' => $upcontractor, 'companyDetails' => $companyDetails, 'smhsuppliers' => $smhsuppliers, 'suppliers' => $suppliers, 'invoice_uk' => $invoice_uk]);
    }

    public function invoice_us()
    {
        $invoice = invoice::where('buyerorderno', 'NOT LIKE', "%UK-18%")->orderBy('id', 'DESC')->get();
        $invoice_us = invoiceus::get();

        $hardwareSuppliers = hardwareSuppliers::get();
        $contractor     =   contractor::get();
        $upcontractor       =   upholstreyContractor::get();
        $companyDetails = settingus::first();
        $smhsuppliers = smhsupplier::get();
        $suppliers = supplier::get();
        /*  echo "<pre>";
        print_r($invoice);die;*/
        return view('invoice/index_us', ['invoice' => $invoice, 'hardwareSuppliers' => $hardwareSuppliers, 'contractor' => $contractor, 'upcontractor' => $upcontractor, 'companyDetails' => $companyDetails, 'smhsuppliers' => $smhsuppliers, 'suppliers' => $suppliers, 'invoice_us' => $invoice_us]);
    }

    public function invoice_eu()
    {
        $invoice = invoice::where('buyerorderno', 'NOT LIKE', "%UK-18%")->orderBy('id', 'DESC')->get();
        $invoice_eu = invoiceeu::get();

        $hardwareSuppliers = hardwareSuppliers::get();
        $contractor     =   contractor::get();
        $upcontractor       =   upholstreyContractor::get();
        $companyDetails = settingeu::first();
        $smhsuppliers = smhsupplier::get();
        $suppliers = supplier::get();
        /*  echo "<pre>";
        print_r($invoice);die;*/
        return view('invoice/index_eu', ['invoice' => $invoice, 'hardwareSuppliers' => $hardwareSuppliers, 'contractor' => $contractor, 'upcontractor' => $upcontractor, 'companyDetails' => $companyDetails, 'smhsuppliers' => $smhsuppliers, 'suppliers' => $suppliers, 'invoice_eu' => $invoice_eu]);
    }

    public function delete(Request $require, $id)
    {
        $invoice = invoice::where('id', $id)->first();
        $invoiceRelationCount = $invoice->stockout->count();
        if ($invoiceRelationCount > 0) {
            return redirect('/invoice')->with('danger', 'Invoice cannot be deleted. Invoice exist in other relations.');
        } else {
            $invoiceTable = invoiceTable::where('invoice_id', $id)->get();

            if ($invoiceTable) {
                foreach ($invoiceTable as $key => $value) {
                    $invoiceTable[$key]->delete();
                }
            }

            if ($invoice) {
                if ($invoice->delete()) {
                    return redirect('/invoice')->with('success', 'Invoice deleted successfully.');
                } else {
                    return redirect('/invoice')->with('danger', 'Invoice was not found.');
                }
            }
        }
    }

    public function delete_uk(Request $require, $id)
    {
        $invoice = invoiceuk::where('id', $id)->first();


        $invoiceTable = invoiceTableUk::where('invoice_id', $id)->get();

        if ($invoiceTable) {
            foreach ($invoiceTable as $key => $value) {
                $invoiceTable[$key]->delete();
            }
        }

        if ($invoice) {
            if ($invoice->delete()) {
                return redirect('/invoices/invoice-uk')->with('success', 'Invoice deleted successfully.');
            } else {
                return redirect('/invoices/invoice-uk')->with('danger', 'Invoice was not found.');
            }
        }
    }

    public function delete_us(Request $require, $id)
    {
        $invoice = invoiceus::where('id', $id)->first();


        $invoiceTable = invoiceTableUs::where('invoice_id', $id)->get();

        if ($invoiceTable) {
            foreach ($invoiceTable as $key => $value) {
                $invoiceTable[$key]->delete();
            }
        }

        if ($invoice) {
            if ($invoice->delete()) {
                return redirect('/invoices/invoice-us')->with('success', 'Invoice deleted successfully.');
            } else {
                return redirect('/invoices/invoice-us')->with('danger', 'Invoice was not found.');
            }
        }
    }

    public function delete_eu(Request $require, $id)
    {
        $invoice = invoiceeu::where('id', $id)->first();


        $invoiceTable = invoiceTableEu::where('invoice_id', $id)->get();

        if ($invoiceTable) {
            foreach ($invoiceTable as $key => $value) {
                $invoiceTable[$key]->delete();
            }
        }

        if ($invoice) {
            if ($invoice->delete()) {
                return redirect('/invoices/invoice-eu')->with('success', 'Invoice deleted successfully.');
            } else {
                return redirect('/invoices/invoice-eu')->with('danger', 'Invoice was not found.');
            }
        }
    }

    public function modal($id)
    {
        $invoice = invoice::find($id)->where('id', $id)->first();
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $showBatch = request()->boolean('show_batch');
        $packingBatchByProductId = [];
        if ($showBatch && $invoice && !empty($invoice->buyerorderno)) {
            $packing_list = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
            if ($packing_list) {
                $plProducts = packingListProduct::where('packinglist_id', $packing_list->id)->get();
                foreach ($plProducts as $plp) {
                    $pid = (int) $plp->product_id;
                    $raw = $plp->batch_no;
                    if (is_string($raw)) {
                        $raw = json_decode($raw, true) ?? $raw;
                    }
                    $labels = $this->batchLabelsFromPackingProductRaw($raw);
                    if (!isset($packingBatchByProductId[$pid])) {
                        $packingBatchByProductId[$pid] = [];
                    }
                    foreach ($labels as $label) {
                        if (!in_array($label, $packingBatchByProductId[$pid], true)) {
                            $packingBatchByProductId[$pid][] = $label;
                        }
                    }
                }
            }
        }

        return view('invoice/modal', [
            'invoice' => $invoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'invoiceTable' => $invoiceTable,
            'certificate' => $certificate,
            'fileMap' => $fileMap,
            'showBatch' => $showBatch,
            'packingBatchByProductId' => $packingBatchByProductId,
        ]);
    }

    /**
     * Normalize packing list batch data into unique labels.
     *
     * Supports null/empty values, JSON strings, comma-separated strings,
     * and nested arrays that use batch_no / batch keys.
     */
    private function batchLabelsFromPackingProductRaw($batchRaw): array
    {
        $out = [];
        if ($batchRaw === null || $batchRaw === '') {
            return $out;
        }
        if (is_string($batchRaw)) {
            $decoded = json_decode($batchRaw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->batchLabelsFromPackingProductRaw($decoded);
            }
            foreach (preg_split('/\s*,\s*/', $batchRaw) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $out[] = $part;
                }
            }
            return array_values(array_unique($out));
        }
        if (!is_array($batchRaw)) {
            $s = trim((string) $batchRaw);
            return $s === '' ? [] : [$s];
        }
        foreach ($batchRaw as $item) {
            if (is_array($item)) {
                if (array_key_exists('batch_no', $item)) {
                    $out = array_merge($out, $this->batchLabelsFromPackingProductRaw($item['batch_no']));
                } elseif (array_key_exists('batch', $item)) {
                    $out = array_merge($out, $this->batchLabelsFromPackingProductRaw($item['batch']));
                } else {
                    $out = array_merge($out, $this->batchLabelsFromPackingProductRaw($item));
                }
            } else {
                $out = array_merge($out, $this->batchLabelsFromPackingProductRaw((string) $item));
            }
        }
        return array_values(array_unique($out));
    }

    public function modal_uk($id)
    {
        $invoice = invoiceuk::find($id)->where('id', $id)->first();
        $companyDetails = settinguk::first();
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTableUk::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::where('id', 2)->first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $packing_list = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
        $packing_list_products = packingListProduct::where('packinglist_id', $packing_list->id ?? '')->get();

        return view('invoice/modal_uk', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'packing_list_products' => $packing_list_products]);
    }

    public function modal_us($id)
    {
        $invoice = invoiceus::find($id)->where('id', $id)->first();
        $companyDetails = settingus::first();
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTableUs::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::where('id', 3)->first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $packing_list = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
        $packing_list_products = packingListProduct::where('packinglist_id', $packing_list->id)->get();

        return view('invoice/modal_us', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'packing_list_products' => $packing_list_products]);
    }

    public function modal_eu($id)
    {
        $invoice = invoiceeu::find($id)->where('id', $id)->first();
        $companyDetails = settingeu::first();
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTableEu::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::where('id', 4)->first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $packing_list = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
        $packing_list_products = packingListProduct::where('packinglist_id', $packing_list->id)->get();

        return view('invoice/modal_eu', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'packing_list_products' => $packing_list_products]);
    }

    public function printinv($id)
    {
        $invoice = invoice::find($id)->where('id', $id)->first();
        $companyDetails = setting::first();
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('invoice/printinv', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate]);
    }

    public function hardwarebill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $hardware_supplier = $request['hardware_supplier'];
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    if (!isset($pricing[$invt->product_id])) {
                        $pricing[$invt->product_id] = pricingTable::where('product_id', $invt->product_id)->where('buyer_id2', 3)->where('productType', 1)->orderBy('id', 'desc')->first();
                        if (empty($pricing[$invt->product_id])) {
                            $pricing[$invt->product_id] = pricingTable::where('product_id', $invt->product_id)->where('productType', 1)->orderBy('id', 'desc')->first();
                            if (empty($pricing[$invt->product_id])) {
                                $pricing[$invt->product_id]     =    0;
                            }
                        }
                    }
                }
            }
        }
        //echo '<pre>';print_r($pricing);die;
        if (!empty($hardware_supplier)) {
            $hardwares = hardwares::where('hardware_supplier', $hardware_supplier)->get();
        } else {
            $hardwares = hardwares::get();
        }
        $hardwareSupplier = hardwareSuppliers::where('id', $hardware_supplier)->first();
        $supplier = supplier::where('c_name', $hardwareSupplier->name)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $date    =    $_REQUEST['date'];
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/hardwarebill', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'pricing' => $pricing, 'hardwares' => $hardwares, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date, 'invoice_ids' => $invoice_ids, 'hardware_supplier' => $hardware_supplier]);
    }

    public function hardwarebillfinal(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $hardware_supplier = $request['hardware_supplier'];
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    if (!isset($pricing[$invt->product_id])) {
                        $pricing[$invt->product_id] = pricingTable::where('product_id', $invt->product_id)->where('buyer_id2', 3)->where('productType', 1)->orderBy('id', 'desc')->first();
                        if (empty($pricing[$invt->product_id])) {
                            $pricing[$invt->product_id] = pricingTable::where('product_id', $invt->product_id)->where('productType', 1)->orderBy('id', 'desc')->first();
                            if (empty($pricing[$invt->product_id])) {
                                $pricing[$invt->product_id]     =    0;
                            }
                        }
                    }
                }
            }
        }
        //echo '<pre>';print_r($pricing);die;
        if (!empty($hardware_supplier)) {
            $hardwares = hardwares::where('hardware_supplier', $hardware_supplier)->get();
        } else {
            $hardwares = hardwares::get();
        }
        $hardwareSupplier = hardwareSuppliers::where('id', $hardware_supplier)->first();
        $supplier = supplier::where('c_name', $hardwareSupplier->name)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $date    =    $_REQUEST['date'];
        $companyDetails->opo_no = $companyDetails->opo_no + 1;
        $companyDetails->save();
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/hardwarebillfinal', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'pricing' => $pricing, 'hardwares' => $hardwares, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date]);
    }

    public function smallhardwarebill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $hardware_supplier = $request['smallhardware_supplier'];
        $pricings = array();
        if (!empty($hardware_supplier)) {
            $hardwares = smallhardwares::where('supplier', $hardware_supplier)->get();
        } else {
            $hardwares = smallhardwares::get();
        }
        //dd($_REQUEST);
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    if (!isset($pricings[$invt->product_id])) {
                        $smallhardwareprods  = smallHardwareProducts::where('product_id', $invt->product_id)->get();
                        //echo '<pre>';print_r($smallhardwareprod->smallhardwares->supplier);die;
                        foreach ($smallhardwareprods as $smallhardwareprod) {
                            if (isset($smallhardwareprod->smallhardwares->supplier)) {
                                if ($smallhardwareprod->smallhardwares->supplier == $request['smallhardware_supplier'] && in_array($smallhardwareprod->smallhardwares->buyer, $_REQUEST['uk18'])) {
                                    $pricings[$invt->product_id][] = $smallhardwareprod;
                                }
                            } else {
                                $pricings[$invt->product_id][]     =    0;
                            }
                        }
                    }
                }
            }
        }
        //echo '<pre>';print_r($pricings);die;

        //$hardwareSupplier = smallhardwareSupplier::where('id',$hardware_supplier)->first();
        $supplier = supplier::where('id', $hardware_supplier)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $uk18    =    $_REQUEST['uk18'];
        $date    =    $_REQUEST['date'];
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/smallhardwarebill', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'pricings' => $pricings, 'hardwares' => $hardwares, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date, 'invoice_ids' => $invoice_ids, 'hardware_supplier' => $hardware_supplier, 'uk18' => $uk18]);
    }

    public function smallhardwarebillfinal(Request $request)
    {



        $invoice_ids = explode(',', $request['invoice_id']);
        $hardware_supplier = $request['smallhardware_supplier'];
        $pricings = [];

        if (!empty($hardware_supplier)) {
            $hardwares = smallhardwares::where('supplier', $hardware_supplier)->get();
        } else {
            $hardwares = smallhardwares::all();
        }

        $uk18 = explode(',', $_REQUEST['uk18']);
        $invoice = [];
        $invoiceTable = [];

        foreach ($invoice_ids as $id) {
            if (!empty($id)) {
                $invoice[] = invoice::where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();

                foreach ($invoiceTable[$id] as $invt) {
                    if (!isset($pricings[$invt->product_id])) {
                        $smallhardwareprods = smallHardwareProducts::where('product_id', $invt->product_id)->get();

                        foreach ($smallhardwareprods as $smallhardwareprod) {
                            if (isset($smallhardwareprod->smallhardwares->supplier)) {
                                if (
                                    $smallhardwareprod->smallhardwares->supplier == $request['smallhardware_supplier']
                                    && in_array($smallhardwareprod->smallhardwares->buyer, $uk18)
                                ) {
                                    $pricings[$invt->product_id][] = $smallhardwareprod;
                                }
                            } else {
                                $pricings[$invt->product_id][] = 0;
                            }
                        }
                    }
                }
            }
        }
        $smhcounts = [];
        $totalQuantity = 0;
        $totalAmount = 0;

        // Initialize grand total value
        $grandValue = 0;

        if (isset($invoice)) {
            foreach ($invoice as $inv) {
                $totalValue = 0;
                $k = 0;

                if (isset($invoiceTable[$inv['id']])) {
                    foreach ($invoiceTable[$inv['id']] as $invt) {
                        if (isset($pricings[$invt['product_id']])) {
                            foreach ($pricings[$invt['product_id']] as $pricing) {
                                if (isset($pricing['quantity']) && $pricing['quantity'] !== 0) {
                                    $totsmh = $pricing['quantity'] * $invt['quantity'];
                                    if (isset($smhcounts[$pricing['smallhardwares']['name']])) {
                                        $smhcounts[$pricing['smallhardwares']['name']] += $totsmh;
                                    } else {
                                        $smhcounts[$pricing['smallhardwares']['name']] = $totsmh;
                                    }
                                    $totalQuantity += $totsmh;

                                    // Calculate value
                                    $value = (isset($pricing['price']) ? $pricing['price'] : $pricing['smallhardwares']['rate']) * $invt['quantity'] * $pricing['quantity'];
                                    $totalValue += $value;
                                }
                            }
                        }
                    }
                }

                // Update grand totals
                $grandValue += $totalValue;
                $totalAmount += $totalValue;
            }
        }



        $supplier = supplier::where('id', $hardware_supplier)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $data = [
            'invoice' => $invoice,
            'print' => isset($_REQUEST['print']) && $_REQUEST['print'] == 1 ? 1 : 0,
            'companyDetails' => $companyDetails,
            'invoiceTable' => $invoiceTable,
            'certificate' => $certificate,
            'pricings' => $pricings,
            'hardwares' => $hardwares,
            'supplier' => $supplier,
            'purchaseOrderNo' => $_REQUEST['po_no'],
            'date' => $_REQUEST['date'],
            'invoice_ids' => $invoice_ids,
            'hardware_supplier' => $hardware_supplier,
            'uk18' => $_REQUEST['uk18'],
        ];
        $pdf = PDF::loadView('invoice/monthendpopdf', $data)->setPaper([0, 0, 1500, 3000]);

        // Define file path and name

        $outsidePath = realpath(__DIR__ . '/../../../../uploads/monthend');
        // Save PDF to storage folder (storage/app/public/pdfs)
        $filename = '/monthend_' . time() . '.pdf';
        $filePath = $outsidePath . $filename;

        // Store the PDF file
        file_put_contents($filePath, $pdf->output());
        $podate = \Carbon\Carbon::createFromFormat('d-M-Y', $_REQUEST['date'])->format('Y-m-d');

        // Creating a Purchase Order
        $purchaseOrder = purchaseOrder::create(array_merge([
            'pono' => strtoupper($_REQUEST['po_no']),
            'supplier_id' => $supplier->id,
            'podate' => $podate,
            'del_date' => $podate,
            'ref_supplier' => strtoupper($_REQUEST['uk18']) ?? '',
            'buyer_orderno' => strtoupper($_REQUEST['uk18']) ?? '',
            'payterms' => $request['payterms'] ?? '',
            'remarks' => $request['remarks'] ?? '',
            'subTotal' => $totalAmount,
            'tgst' => $request['tgst'] ?? 0,
            'tquantity' => $totalQuantity,
            'tamount' => $totalAmount,
            'remqty' => $totalQuantity,
            'address_option' => 100,
            'pdf_path' => $filename,
        ], SendToSupplierPo::createAttributes(100, strtoupper($_REQUEST['po_no']), 'purchase_order')));

        $smhcounts = [];
        $totalQuantity = 0;
        $totalAmount = 0;

        if (!empty($invoice)) {
            foreach ($invoice as $inv) {
                if (!empty($invoiceTable[$inv->id])) {
                    foreach ($invoiceTable[$inv->id] as $invt) {
                        if (!empty($pricings[$invt->product_id])) {
                            foreach ($pricings[$invt->product_id] as $pricing) {
                                if (!empty($pricing['quantity']) && $pricing['quantity'] != 0) {
                                    $product = product::where('id', $invt->product_id)->first();
                                    if (!empty($product)) {
                                        $totsmh = $pricing['quantity'] * $invt->quantity;

                                        if (!empty($pricing['smallhardwares']) && isset($pricing['smallhardwares']['name'])) {
                                            $smhName = $pricing['smallhardwares']['name'];
                                            $smhcounts[$smhName] = ($smhcounts[$smhName] ?? 0) + $totsmh;
                                        }

                                        // Ensure price or rate exists
                                        $unitPrice = isset($pricing['price']) ? $pricing['price'] : ($pricing['smallhardwares']['rate'] ?? 0);
                                        $value = $unitPrice * $invt->quantity * $pricing['quantity'];

                                        poTable::create([
                                            'product_id' => $product->id,
                                            'poid' => $purchaseOrder->id,
                                            'ean' => $product->EAN ?? '',
                                            'quantity' => $totsmh,
                                            'unit' => $product->unit ?? '',
                                            'remqty' =>  $totsmh  ?? 0,
                                            'rate' => $unitPrice,
                                            'amount' => $value,
                                            'gstslab' => $product->gstslab ?? '',
                                            'gstamount' => $product->gstamount ?? 0,
                                            'description' => $product->description ?? '',
                                        ]);

                                        $totalQuantity +=  $totsmh;
                                        $totalAmount += $value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Final Totals Output



        // Debugging the invoice variable


        // Updating company details
        $companyDetails->opo_no += 1;
        $companyDetails->save();




        // Define storage path

        // Create the directory if not exists


        // Define file name

        return view('invoice/smallhardwarebillfinal', [
            'invoice' => $invoice,
            'print' => isset($_REQUEST['print']) && $_REQUEST['print'] == 1 ? 1 : 0,
            'companyDetails' => $companyDetails,
            'invoiceTable' => $invoiceTable,
            'certificate' => $certificate,
            'pricings' => $pricings,
            'hardwares' => $hardwares,
            'supplier' => $supplier,
            'purchaseOrderNo' => $_REQUEST['po_no'],
            'date' => $_REQUEST['date'],
            'invoice_ids' => $invoice_ids,
            'hardware_supplier' => $hardware_supplier,
            'uk18' => $_REQUEST['uk18'],
        ]);
    }






    public function dustcoverbill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $supplier_id = $request['supplier'];
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    $pricing[$invt->product_id] = smallhardware::where('supplier_id', $supplier_id)->where('product_id', $invt->product_id)->first();
                }
            }
        }
        //echo '<pre>';print_r($pricing);die;
        $supplier = smhsupplier::where('id', $supplier_id)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $date    =    $_REQUEST['date'];
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/dustcoverbill', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'supplier_id' => $supplier_id, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date, 'invoice_ids' => $invoice_ids, 'pricing' => $pricing]);
    }

    public function dustcoverbillfinal(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $supplier_id = $request['supplier'];
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    $pricing[$invt->product_id] = smallhardware::where('supplier_id', $supplier_id)->where('product_id', $invt->product_id)->first();
                }
            }
        }
        //dd($supplier_id);
        //echo '<pre>';print_r($pricing);die;
        $supplier = smhsupplier::where('id', $supplier_id)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $date    =    $_REQUEST['date'];
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/dustcoverbillfinal', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'supplier_id' => $supplier_id, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date, 'invoice_ids' => $invoice_ids, 'pricing' => $pricing]);
    }


    public function pouchbill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $supplier_id = $request['supplier'];
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    $pricing[$invt->product_id] = smallhardware::where('supplier_id', $supplier_id)->where('product_id', $invt->product_id)->first();
                }
            }
        }
        //echo '<pre>';print_r($pricing);die;
        $supplier = smhsupplier::where('id', $supplier_id)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $date    =    $_REQUEST['date'];
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/pouchbill', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'supplier_id' => $supplier_id, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date, 'invoice_ids' => $invoice_ids, 'pricing' => $pricing]);
    }

    public function pouchbillfinal(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_id']);
        $supplier_id = $request['supplier'];
        foreach ($invoice_ids as $id) {
            if ($id != "") {
                $invoice[] = invoice::find($id)->where('id', $id)->first();
                $invoiceTable[$id] = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
                foreach ($invoiceTable[$id] as $invt) {
                    $pricing[$invt->product_id] = smallhardware::where('supplier_id', $supplier_id)->where('product_id', $invt->product_id)->first();
                }
            }
        }
        //dd($supplier_id);
        //echo '<pre>';print_r($pricing);die;
        $supplier = smhsupplier::where('id', $supplier_id)->first();
        $certificate = certificate::first();
        $companyDetails = setting::first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }
        $purchaseOrderNo    =    $_REQUEST['po_no'];
        $date    =    $_REQUEST['date'];
        //echo "<pre>";print_r($hardwares);die;
        return view('invoice/pouchbillfinal', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'supplier_id' => $supplier_id, 'supplier' => $supplier, 'purchaseOrderNo' => $purchaseOrderNo, 'date' => $date, 'invoice_ids' => $invoice_ids, 'pricing' => $pricing]);
    }

    public function contractorbill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_ids']);
        $invoices        =    invoice::whereIn('id', $invoice_ids)->get();
        $cbills            =    [];
        $contractors     =    contractor::all();
        foreach ($invoices as $invoice) {
            $products[$invoice->invoiceno]    =    invoiceTable::where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
            foreach ($products[$invoice->invoiceno] as $invt) {
                foreach ($contractors as $contractor) {
                    $cbills[$invt->invoice_id][$invt->product_id][$contractor->id]    =    contractorBill::where('invoice_id', $invt->invoice_id)->where('product_id', $invt->product_id)->where('contractor_id', $contractor->id)->first();
                }
                $cbills[$invt->invoice_id][$invt->product_id][0]    =    contractorBill::where('invoice_id', $invt->invoice_id)->where('product_id', $invt->product_id)->where('contractor_id', 0)->first();
            }
        }
        //echo "<pre>";print_r($cbills);die;

        $companySettings = setting::first();
        $cutoverRaw = $companySettings->contractor_finishing_new_rate_from ?? null;
        $contractorFinishingNewRateFrom = $cutoverRaw
            ? Carbon::parse($cutoverRaw)->startOfDay()
            : Carbon::create(1970, 1, 1)->startOfDay();

        $finishRates = finishRate::all()->keyBy('name');

        return view('invoice/contractorbill', [
            'invoice_ids' => $invoice_ids,
            'products' => $products,
            'contractors' => $contractors,
            'cbills' => $cbills,
            'invoices' => $invoices->keyBy('id'),
            'contractorFinishingNewRateFrom' => $contractorFinishingNewRateFrom,
            'finishRates' => $finishRates,
        ]);
    }

    /**
     * Dry-run Excel: contractor_bill stored vs corrected old-price formula. GET → browser download.
     * Query: invoice_ids (comma), created_from, created_to (defaults match artisan command).
     */
    public function downloadContractorBillFinishingDryRun(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'nullable|string',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
        ]);

        $csv = $request->input('invoice_ids', '1053,1055,1056,1059,1058,1062,1063,1050');
        $from = $request->input('created_from', '2026-04-01');
        $to = $request->input('created_to', '2026-04-02');

        [$rows] = ContractorBillFinishingDryRunBuilder::buildRows($csv, $from, $to);

        $filename = 'contractor_bill_finishing_dry_run_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new ContractorBillFinishingDryRunExport($rows), $filename);
    }

    /**
     * Apply dry-run corrected finishing to DB. GET query: invoice_ids, created_from, created_to (same as dry-run).
     */
    public function applyContractorBillFinishingDryRun(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'nullable|string',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
        ]);

        $csv = $request->input('invoice_ids', '1053,1055,1056,1059,1058,1062,1063,1050');
        $from = $request->input('created_from', '2026-04-01');
        $to = $request->input('created_to', '2026-04-02');

        $summary = ContractorBillFinishingDryRunBuilder::applyCorrections($csv, $from, $to);

        $message = 'Applied corrected finishing to '.$summary['updated'].' contractor bill line(s). '
            .'Skipped '.$summary['skipped'].' (missing invoice or product).';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'updated' => $summary['updated'],
                'skipped' => $summary['skipped'],
            ]);
        }

        return response($message, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function createContractorBill(Request $request)
    {
        $cbill = contractorBill::where('invoice_id', $request['invoice_id'])->where('product_id', $request['product_id'])->where('contractor_id', $request['contractor_id'])->first();
        if (isset($cbill->id)) {
            $cbill->finishing_rate        =    $request['finishing_rate'];
            $cbill->quantity            =    $request['quantity'];
            $cbill->amount                =    $request['amount'];
            $cbill->finishing            =    $request['finishing'];
            $cbill->save();
        } else {
            contractorBill::create([
                'finishing_rate'    =>    $request['finishing_rate'],
                'quantity'            =>    $request['quantity'],
                'amount'            =>    $request['amount'],
                'invoice_id'        =>    $request['invoice_id'],
                'product_id'        =>    $request['product_id'],
                'contractor_id'        =>    $request['contractor_id'],
                'finishing'            =>    $request['finishing']
            ]);
        }
        die;
    }

    public function createContractorBillbulk(Request $request)
    {
        // Bulk save (bill_data array) or single row save (auto-save)
        $billData = $request->has('bill_data') ? $request->bill_data : [$request->all()];

        foreach ($billData as $row) {
            if (empty($row['invoice_id']) || empty($row['product_id'])) {
                continue;
            }

            contractorBill::updateOrCreate(
                [
                    'invoice_id'    => $row['invoice_id'],
                    'product_id'    => $row['product_id'],
                    'contractor_id' => $row['contractor_id'],
                ],
                [
                    'finishing_rate' => $row['finishing_rate'] ?? 0,
                    'quantity'       => $row['quantity'] ?? 0,
                    'amount'         => $row['amount'] ?? 0,
                    'finishing'      => $row['finishing'] ?? '',
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Data Saved']);
    }

    public function upholsterybill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_ids']);
        $invoices        =    invoice::whereIn('id', $invoice_ids)->get();
        $cbills            =    [];
        $contractors     =    upholstreyContractor::where('status', 1)->get();
        foreach ($invoices as $invoice) {
            $products[$invoice->invoiceno]    =    invoiceTable::where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
            foreach ($products[$invoice->invoiceno] as $invt) {
                foreach ($contractors as $contractor) {
                    $cbills[$invt->invoice_id][$invt->product_id][$contractor->id]    =    upholstreyBill::where('invoice_id', $invt->invoice_id)->where('product_id', $invt->product_id)->where('contractor_id', $contractor->id)->first();
                }
                $cbills[$invt->invoice_id][$invt->product_id][0]    =    upholstreyBill::where('invoice_id', $invt->invoice_id)->where('product_id', $invt->product_id)->where('contractor_id', 0)->first();
            }
        }
        //echo "<pre>";print_r($cbills);die;

        return view('invoice/upholsterybill', ['invoice_ids' => $invoice_ids, 'products' => $products, 'contractors' => $contractors, 'cbills' => $cbills]);
    }

    public function createUpholsteryBill(Request $request)
    {
        $cbill = upholstreyBill::where('invoice_id', $request['invoice_id'])->where('product_id', $request['product_id'])->where('contractor_id', $request['contractor_id'])->first();
        if (isset($cbill->id)) {
            $cbill->upholestry_rate        =    $request['upholestry_rate'];
            $cbill->quantity            =    $request['quantity'];
            $cbill->amount                =    $request['amount'];
            $cbill->save();
        } else {
            upholstreyBill::create([
                'upholestry_rate'    =>    $request['upholestry_rate'],
                'quantity'            =>    $request['quantity'],
                'amount'            =>    $request['amount'],
                'invoice_id'        =>    $request['invoice_id'],
                'product_id'        =>    $request['product_id'],
                'contractor_id'        =>    $request['contractor_id']
            ]);
        }
        die;
    }

    public function downloadUpholstreyBill(Request $request)
    {
        $contractor =     upholstreyContractor::where('id', $request['contractor'])->first();
        $month         =    explode('-', $request['month']);
        $invoices    =    invoice::whereMonth('date', '=', $month[0])->whereYear('date', '=', $month[1])->get();
        if (count($invoices)) {
            foreach ($invoices as $invoice) {
                $bills[$invoice->buyerorderno]     =    upholstreyBill::where('contractor_id', $contractor->id)->where('quantity', '>', 0)->where('invoice_id', $invoice->id)->get();
                $invoice_ids[]            =    $invoice->id;
            }
            $bill     =    upholstreyBill::where('contractor_id', $contractor->id)->where('quantity', '>', 0)->whereIn('invoice_id', $invoice_ids)->get();
            if (count($bill)) {
                return view('invoice/downloadUpholstreyBill', ['bills' => $bills, 'contractor' => $contractor, 'month' => $month]);
            } else {
                return redirect('/invoice')->with('danger', 'Upholstrey Bill was not found.');
            }
        } else {
            return redirect('/invoice')->with('danger', 'Upholstrey Bill was not found.');
        }
    }

    public function cornerbill(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_ids']);
        $invoices        =    invoice::whereIn('id', $invoice_ids)->get();
        foreach ($invoices as $invoice) {
            $products[$invoice->invoiceno]    =    cornerBill::where('invoice_id', $invoice->id)->get();
        }

        $companyDetails = setting::first();
        return view('invoice/cornerbill', ['invoice_ids' => $invoice_ids, 'products' => $products, 'companyDetails' => $companyDetails]);
    }

    public function cornerbillEdit(Request $request)
    {
        $invoice_ids    =    explode(',', $request['invoice_ids']);
        $invoices        =    invoice::whereIn('id', $invoice_ids)->get();
        foreach ($invoices as $invoice) {
            $products[$invoice->invoiceno]    =    invoiceTable::where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }

        if (isset($products)) {
            foreach ($products as $key => $product) {
                if (count($product)) {
                    $total_corner_quantity = 0;
                    $total_corner_amount = 0;
                    $total_l_quantity = 0;
                    $total_l_amount = 0;
                    foreach ($product as $k => $b) {
                        $amount                  =     0.50;
                        $corner_amount             =     $b->product->corner * $amount * $b->quantity;
                        $l_amount                 =     $b->product->lhardware * $amount * $b->quantity;
                        $product_quantity       =   $b->quantity;
                        $corner_quantity           =   $b->product->corner;
                        $total_corner           =   $b->product->corner * $b->quantity;
                        $l_quantity               =   $b->product->lhardware;
                        $total_l                   =   $b->product->lhardware * $b->quantity;
                        $invoice_id                =    $b->invoice_id;
                        $product_id                =    $b->product->id;
                        $cornerBill             =    cornerBill::where('invoice_id', $invoice_id)->where('product_id', $product_id)->first();
                        if (!isset($cornerBill->id)) {
                            cornerBill::create([
                                'invoice_id'            =>    $invoice_id,
                                'product_id'            =>    $product_id,
                                'product_quantity'        =>    $product_quantity,
                                'corners'                =>    $corner_quantity,
                                'total_corners'            =>    $total_corner,
                                'total_corners_amount'    =>    $corner_amount,
                                'l'                        =>    $l_quantity,
                                'total_l'                =>    $total_l,
                                'total_l_amount'        =>    $l_amount,
                            ]);
                        }
                    }
                }
            }
        }
        foreach ($invoices as $invoice) {
            $cornerBills[$invoice->invoiceno]             =    cornerBill::where('invoice_id', $invoice->id)->get();
        }

        $companyDetails = setting::first();
        return view('invoice/cornerbillEdit', ['invoice_ids' => $invoice_ids, 'cornerBills' => $cornerBills, 'companyDetails' => $companyDetails]);
    }

    public function modalpackingsheet($id)
    {
        $invoice = invoice::find($id)->where('id', $id)->first();
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        $invoiceTable = invoiceTable::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('invoice/modalpackingsheet', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'fileMap' => $fileMap]);
    }



    /**
     * Shared by web stockstore and API /stockOut: batch + unique ref deductions and stock_log rows
     * (supplier_name, buyer_name, reference_number) aligned with web stock-out.
     *
     * Per batch: deduct min(remaining need, snapshot ref qty on batch). Batch physical stock
     * must be >= full snapshot ref qty on that batch or the line fails. Extra batch stock beyond
     * the snapshot is not consumed on that batch.
     *
     * @return int Unallocated quantity remaining (0 = full allocation)
     */
    public function applyStockOutBatchRefAndStockLogs(
        invoice $invoice,
        product $product,
        ?packingListProduct $plp,
        $receiveQty,
        string $suppInvNoStr,
        int $stockOutEntityId,
        bool $isCartonInvoice
    ): int {
        $receiveQty = (int) $receiveQty;
        if ($receiveQty <= 0) {
            return 0;
        }

        $buyerName = null;
        if (! empty($invoice->buyer_id)) {
            $buyerName = optional(buyer::find((int) $invoice->buyer_id))->c_name;
        }

        if ($isCartonInvoice || ! $plp) {
            $openBefore = (int) $product->quantity;
            $product->quantity = max(0, $openBefore - $receiveQty);
            $product->save();

            stockLog::create([
                'product_id' => $product->id,
                'voucher_no' => $invoice->invoiceno,
                'ref_no' => 'Stock Out - Buyer Order No. - ' . $invoice->buyerorderno,
                'quantity' => $receiveQty,
                'opening_balance' => $openBefore,
                'remaining_stock' => $product->quantity,
                'type' => 2,
                'supplier_inv_no' => $suppInvNoStr !== '' ? $suppInvNoStr : null,
                'batch_balance' => null,
                'batch_no' => null,
                'entity_id' => $stockOutEntityId,
                'supplier_name' => null,
                'buyer_name' => $buyerName,
                'reference_number' => null,
                'reference_quantity' => $receiveQty,
            ]);

            return 0;
        }

        $batchNos = $this->batchNoListFromPlpColumn($plp->batch_no);
        if ($batchNos === []) {
            return $receiveQty;
        }

        $refSnapFull = $plp->ref_source_snapshot ?? [];
        if (is_string($refSnapFull)) {
            $decodedSnap = json_decode($refSnapFull, true);
            $refSnapFull = is_array($decodedSnap) ? $decodedSnap : [];
        }
        if (! is_array($refSnapFull) || $refSnapFull === []) {
            // Snapshot is mandatory for furniture stock-out; do not fall back to approved SI pools.
            return $receiveQty;
        }

        $totalRefAvailForSelection = 0;
        foreach ($batchNos as $bnCheck) {
            $bnCheck = trim((string) $bnCheck);
            if ($bnCheck === '') {
                continue;
            }
            $snapshotRowsForBatch = $this->refSnapshotRowsForBatch($refSnapFull, $bnCheck);
            if ($snapshotRowsForBatch === []) {
                // Every selected batch must have snapshot rows.
                return $receiveQty;
            }
            $totalRefAvailForSelection += $this->totalUniqueRefRemainingQtyFromSnapshotBeforeAddBack(
                (int) $product->id,
                $bnCheck,
                $refSnapFull
            );
        }
        if ($totalRefAvailForSelection < $receiveQty) {
            return $receiveQty;
        }

        $remainingQty = $receiveQty;

        $snapshotSiByBatch = [];
        if (is_array($plp->ref_source_snapshot ?? null) && $plp->ref_source_snapshot !== []) {
            $snapshotSiByBatch = $this->supplierInvoiceIdsByBatchFromRefSnapshot($plp->ref_source_snapshot);
        }

        foreach ($batchNos as $batchNo) {
            if ($remainingQty <= 0) {
                break;
            }

            $batchNo = trim((string) $batchNo);
            if ($batchNo === '') {
                continue;
            }

            $snapshotQtyForBatch = $this->totalUniqueRefRemainingQtyFromSnapshotBeforeAddBack(
                (int) $product->id,
                $batchNo,
                $refSnapFull
            );

            if ($snapshotQtyForBatch <= 0) {
                continue;
            }

            $batch = Batch::where('batch_no', $batchNo)->lockForUpdate()->first();
            if (! $batch || $batch->quantity <= 0) {
                return max(1, $remainingQty);
            }

            $batchProduct = BatchProduct::where('batch_id', $batch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $batchProduct || (int) $batchProduct->quantity <= 0) {
                return max(1, $remainingQty);
            }

            $batchProductQty = (int) $batchProduct->quantity;
            if ($batchProductQty < $snapshotQtyForBatch) {
                return max(1, $remainingQty);
            }

            $deductQty = min($remainingQty, $snapshotQtyForBatch);
            if ($deductQty <= 0) {
                continue;
            }

            $batchProduct->quantity -= $deductQty;
            $batchProduct->save();
            $batch->quantity -= $deductQty;
            $batch->save();
            $product->quantity -= $deductQty;
            $product->save();

            $remainingDeduct = $deductQty;
            $supplierForLog = null;

            $snapshotLinesThisBatch = $this->refSnapshotRowsForBatch($refSnapFull, $batchNo);

            // Build current available pools by exact unique reference (authoritative for deduct + logs).
            $availableByUr = [];
            $availableRefQtyBySupplierInvoiceId = [];
            $availableRefQtyNoSi = 0;
            foreach ($this->uniqueRefSnapshotLinesForProductOnBatch((int) $product->id, (string) $batchNo) as $line) {
                $urId = (int) ($line['unique_referencenumber_id'] ?? 0);
                if ($urId <= 0) {
                    continue;
                }
                $sid = (int) ($line['supplier_invoice_id'] ?? 0);
                $isOld = ! empty($line['is_no_supplier_invoice']);
                $qtyAvail = (int) ($line['available_ref_qty'] ?? 0);
                $availableByUr[$urId] = [
                    'supplier_invoice_id' => $sid,
                    'supplier_invoice_number' => (string) ($line['supplier_invoice_number'] ?? ''),
                    'is_old' => $isOld,
                    'available_ref_qty' => $qtyAvail,
                ];
                if ($isOld) {
                    $availableRefQtyNoSi += $qtyAvail;
                } elseif ($sid > 0) {
                    $availableRefQtyBySupplierInvoiceId[$sid] = (int) ($availableRefQtyBySupplierInvoiceId[$sid] ?? 0) + $qtyAvail;
                }
            }

            $usedByUr = [];
            $legacyPreferredSiIds = [];

            // Primary allocation path: consume exactly the uref ids selected in snapshot order for this batch.
            foreach ($snapshotLinesThisBatch as $snapRow) {
                if ($remainingDeduct <= 0) {
                    break;
                }
                $urId = (int) ($snapRow['unique_referencenumber_id'] ?? 0);
                if ($urId > 0) {
                    $dUr = $this->deductUniqueRefRemainingQtyForUrId(
                        $urId,
                        (int) $product->id,
                        (int) $batch->id,
                        $remainingDeduct
                    );
                    if ($dUr > 0) {
                        $usedByUr[$urId] = ($usedByUr[$urId] ?? 0) + $dUr;
                        $remainingDeduct -= $dUr;
                    }

                    continue;
                }

                // Legacy row shape support: {batch_no, supplier_invoice_id}
                $sidLegacy = (int) ($snapRow['supplier_invoice_id'] ?? 0);
                if ($sidLegacy > 0 && ! in_array($sidLegacy, $legacyPreferredSiIds, true)) {
                    $legacyPreferredSiIds[] = $sidLegacy;
                }
            }

            // Legacy fallback for old snapshot rows that did not carry uref ids.
            if ($remainingDeduct > 0 && $legacyPreferredSiIds !== []) {
                foreach ($legacyPreferredSiIds as $sid) {
                    if ($remainingDeduct <= 0) {
                        break;
                    }
                    $dUrp = $this->deductUniqueRefRemainingQtyForProductOnBatch(
                        (int) $sid,
                        (int) $product->id,
                        (int) $batch->id,
                        $remainingDeduct
                    );
                    if ($dUrp > 0) {
                        $remainingDeduct -= $dUrp;
                    }
                }
            }

            if ($remainingDeduct > 0) {
                // Snapshot-selected refs are mandatory; no approved-SI fallback allowed.
                return max(1, $remainingQty);
            }

            $usedRefQtyBySupplierInvoiceId = [];
            $usedNoSi = 0;
            foreach ($usedByUr as $urId => $usedQty) {
                $meta = $availableByUr[(int) $urId] ?? null;
                if (! $meta) {
                    continue;
                }
                if (! empty($meta['is_old'])) {
                    $usedNoSi += (int) $usedQty;
                    continue;
                }
                $sid = (int) ($meta['supplier_invoice_id'] ?? 0);
                if ($sid > 0) {
                    $usedRefQtyBySupplierInvoiceId[$sid] = (int) ($usedRefQtyBySupplierInvoiceId[$sid] ?? 0) + (int) $usedQty;
                }
            }

            $referenceNumbers = [];
            if ($usedRefQtyBySupplierInvoiceId !== []) {
                $usedSupplierInvoiceIds = array_keys($usedRefQtyBySupplierInvoiceId);
                sort($usedSupplierInvoiceIds);
                $usedInvoiceRows = supplierInvoice::whereIn('id', $usedSupplierInvoiceIds)->get()->keyBy('id');
                foreach ($usedSupplierInvoiceIds as $sid) {
                    $used = (int) ($usedRefQtyBySupplierInvoiceId[$sid] ?? 0);
                    if ($used <= 0) {
                        continue;
                    }
                    $supInv = $usedInvoiceRows->get((int) $sid);
                    if ($supplierForLog === null && $supInv) {
                        $supplierForLog = $supInv;
                    }
                    $total = (int) ($availableRefQtyBySupplierInvoiceId[$sid] ?? 0);
                    if ($total <= 0) {
                        $total = $used;
                    }
                    $label = $supInv ? ($supInv->supplier_invoice_number ?? '') : ('SI#'.$sid);
                    $referenceNumbers[] = $label . '(' . $used . '/' . $total . ')';
                }
            }
            if ($usedNoSi > 0) {
                $totalNoSi = $availableRefQtyNoSi > 0 ? $availableRefQtyNoSi : $usedNoSi;
                $referenceNumbers[] = self::REF_LOG_NO_SUPPLIER_INVOICE . '(' . $usedNoSi . '/' . $totalNoSi . ')';
            }

            $referenceNumber = implode(', ', array_filter($referenceNumbers));
            $supplierName = null;
            if (! empty($batch->supplier_id)) {
                $supplierName = optional(supplier::find((int) $batch->supplier_id))->c_name;
            }

            stockLog::create([
                'product_id' => $product->id,
                'voucher_no' => $invoice->invoiceno,
                'ref_no' => 'Stock Out - Buyer Order No. - ' . $invoice->buyerorderno,
                'quantity' => $deductQty,
                'opening_balance' => $product->quantity + $deductQty,
                'remaining_stock' => $product->quantity,
                'type' => 2,
                'supplier_inv_no' => $suppInvNoStr !== '' ? $suppInvNoStr : null,
                'batch_balance' => $batchProduct->quantity,
                'batch_no' => $batchNo,
                'entity_id' => $stockOutEntityId,
                'supplier_name' => $supplierName,
                'buyer_name' => $buyerName,
                'reference_number' => $referenceNumber !== '' ? $referenceNumber : null,
                'reference_quantity' => $deductQty,
            ]);

            $remainingQty -= $deductQty;
        }

        return max(0, $remainingQty);
    }

    /**
     * Consumable deductions for one furniture stock-out line. Runs after furniture/batch/ref.
     * Partial issue when stock is low; never throws (logs on unexpected errors).
     *
     * @param  array<string, mixed>  $ps
     * @param  array<int, string>  $pendingConsumables
     */
    public function applyStockOutConsumableDeductionsForLine(
        invoice $invoice,
        int $invoiceId,
        stockout $q,
        product $product,
        array $ps,
        array &$pendingConsumables
    ): void {
        try {
            $receiveQty = (float) ($ps['receiveqty'] ?? 0);
            $remQty = (float) ($ps['remqty'] ?? 0);
            $wfConsumables = WfConsumable::where('product_id', $product->id)->get();

            foreach ($wfConsumables as $wfConsumable) {
                $wfAttrs = $wfConsumable->getAttributes();
                $consumableId = (int) ($wfAttrs['consumables_id'] ?? $wfAttrs['consumable_id'] ?? 0);
                if ($consumableId <= 0) {
                    continue;
                }

                $need = (float) ($wfConsumable->qty ?? 0) * $receiveQty;
                if ($need <= 0) {
                    continue;
                }

                $consumable = consumable::find($consumableId);
                if (! $consumable) {
                    ManualStockoutPendingConsumable::updateOrCreate(
                        [
                            'invoice_id' => $invoiceId,
                            'stock_id' => (int) $q->id,
                            'product_id' => $product->id,
                            'consumable_id' => $consumableId,
                        ],
                        [
                            'location' => $ps['location'] ?? null,
                            'reason' => 'Consumable record missing (required: ' . $need . ')',
                            'status' => 0,
                        ]
                    );
                    $pendingConsumables[$consumableId] = (string) $consumableId;

                    continue;
                }

                if ($this->isMonthEndManagedConsumable($consumable) || $this->isHardwareMonthendConsumable($consumable)) {
                    // Month-end and hardware month-end PO consumables are not deducted during regular furniture stock-out.
                    continue;
                }

                $openbalanceConsumable = (float) $consumable->quantity;
                $take = min($need, max(0.0, $openbalanceConsumable));
                $short = $need - $take;

                if ($take > 0) {
                    $remainingConsumableQty = (float) ($wfConsumable->qty ?? 0) * $remQty;

                    stockoutTableConsumable::create([
                        'invoice_id' => $invoiceId,
                        'consumable_id' => $consumable->id,
                        'stock_id' => (int) $q->id,
                        'orderqty' => $remainingConsumableQty,
                        'receiveqty' => $take,
                        'remainingqty' => 0,
                        'location' => $ps['location'] ?? null,
                    ]);

                    $consumable->quantity = $openbalanceConsumable - $take;
                    $consumable->save();

                    stockLogConsumable::create([
                        'product_id' => $product->id,
                        'consumable_id' => $consumable->id,
                        'voucher_no' => $invoice->invoiceno,
                        'ref_no' => 'Stock Out - Buyer Order No. - ' . $invoice->buyerorderno,
                        'quantity' => $take,
                        'opening_balance' => $openbalanceConsumable,
                        'remaining_stock' => $consumable->quantity,
                        'type' => 2,
                        'remark' => 'Sell',
                    ]);
                }

                if ($short > 0.00001) {
                    $pendingReason = 'Insufficient consumable quantity (required: ' . $need . ', issued: ' . $take . ', still need: ' . $short . ')';
                    if (strlen($pendingReason) > 500) {
                        $pendingReason = substr($pendingReason, 0, 497) . '...';
                    }
                    ManualStockoutPendingConsumable::updateOrCreate(
                        [
                            'invoice_id' => $invoiceId,
                            'stock_id' => (int) $q->id,
                            'product_id' => $product->id,
                            'consumable_id' => $consumable->id,
                        ],
                        [
                            'location' => $ps['location'] ?? null,
                            'reason' => $pendingReason,
                            'status' => 0,
                        ]
                    );
                    $pendingConsumables[$consumableId] = $consumable->name ?? (string) $consumableId;
                } else {
                    ManualStockoutPendingConsumable::where('stock_id', (int) $q->id)
                        ->where('consumable_id', (int) $consumable->id)
                        ->where('product_id', (int) $product->id)
                        ->where('status', 0)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            \Log::error('applyStockOutConsumableDeductionsForLine: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (function_exists('report')) {
                report($e);
            }
        }
    }

    /**
     * Carton (box 1 / box 2) deductions for one line; boxes are independent. After furniture/batch/ref.
     *
     * @param  array<string, mixed>  $ps
     * @param  array<int, string>  $pendingCartons
     */
    public function applyStockOutCartonDeductionsForLine(
        invoice $invoice,
        int $invoiceId,
        stockout $q,
        product $product,
        array $ps,
        array &$pendingCartons
    ): void {
        try {
            $receiveQty = (float) ($ps['receiveqty'] ?? 0);
            $remQty = (float) ($ps['remqty'] ?? 0);
            $productCart = ProductCarton::where('product_id', $product->id)->first();
            if (! $productCart) {
                return;
            }

            $need1 = (float) ($productCart->quantity1 ?? 0) * $receiveQty;
            $need2 = (float) ($productCart->quantity2 ?? 0) * $receiveQty;

            if ($need1 <= 0 && $need2 <= 0) {
                return;
            }

            $packaging = packaging::where('product_id', $product->id)->first();
            if (! $packaging) {
                ManualStockoutPendingCarton::updateOrCreate(
                    [
                        'invoice_id' => $invoiceId,
                        'stock_id' => (int) $q->id,
                        'product_id' => (int) $productCart->product_id,
                    ],
                    [
                        'location' => $ps['location'] ?? null,
                        'reason' => 'Packaging record missing',
                        'short_qty_box1' => $need1 > 0 ? $need1 : null,
                        'short_qty_box2' => $need2 > 0 ? $need2 : null,
                        'status' => 0,
                    ]
                );
                $pendingCartons[(int) $productCart->product_id] = $product->code ?? (string) $productCart->product_id;

                return;
            }

            $avail1 = (float) ($packaging->box_1_qty ?? 0);
            $avail2 = (float) ($packaging->box_2_qty ?? 0);
            $take1 = min($need1, max(0.0, $avail1));
            $take2 = min($need2, max(0.0, $avail2));
            $short1 = max(0.0, $need1 - $take1);
            $short2 = max(0.0, $need2 - $take2);

            $open1 = $avail1;
            $open2 = $avail2;

            if ($take1 > 0 || $take2 > 0) {
                $remainingpackagingQty = (float) ($productCart->quantity1 ?? 0) * $remQty;
                $remainingpackagingQty2 = (float) ($productCart->quantity2 ?? 0) * $remQty;

                StockOutTableCarton::create([
                    'product_id' => $productCart->product_id,
                    'stock_id' => $q->id,
                    'orderqty' => $remainingpackagingQty,
                    'orderqty2' => $remainingpackagingQty2,
                    'receiveqty' => $take1,
                    'receiveqty2' => $take2,
                    'remainingqty' => 0,
                    'remainingqty2' => 0,
                    'location' => $ps['location'] ?? null,
                ]);

                $packaging->box_1_qty = $avail1 - $take1;
                $packaging->box_2_qty = $avail2 - $take2;
                $packaging->save();

                StockLogCarton::create([
                    'product_id' => $productCart->product_id,
                    'voucher_no' => $invoice->invoiceno,
                    'ref_no' => 'Stock Out - Buyer Order No. - ' . $invoice->buyerorderno,
                    'quantity' => $take1,
                    'quantity2' => $take2,
                    'opening_balance' => $open1,
                    'opening_balance2' => $open2,
                    'remaining_stock' => $packaging->box_1_qty,
                    'remaining_stock2' => $packaging->box_2_qty,
                    'type' => 2,
                ]);
            }

            if ($short1 > 0.00001 || $short2 > 0.00001) {
                $reasonParts = [];
                if ($short1 > 0.00001) {
                    $reasonParts[] = 'Box 1 short by ' . $short1;
                }
                if ($short2 > 0.00001) {
                    $reasonParts[] = 'Box 2 short by ' . $short2;
                }

                ManualStockoutPendingCarton::updateOrCreate(
                    [
                        'invoice_id' => $invoiceId,
                        'stock_id' => (int) $q->id,
                        'product_id' => (int) $productCart->product_id,
                    ],
                    [
                        'location' => $ps['location'] ?? null,
                        'reason' => implode('; ', $reasonParts),
                        'short_qty_box1' => $short1 > 0.00001 ? $short1 : null,
                        'short_qty_box2' => $short2 > 0.00001 ? $short2 : null,
                        'status' => 0,
                    ]
                );
                $pendingCartons[(int) $productCart->product_id] = $product->code ?? (string) $productCart->product_id;
            } else {
                ManualStockoutPendingCarton::where('stock_id', (int) $q->id)
                    ->where('product_id', (int) $productCart->product_id)
                    ->where('status', 0)
                    ->delete();
            }
        } catch (\Throwable $e) {
            \Log::error('applyStockOutCartonDeductionsForLine: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * When the export invoice is fully stocked out: deduct is_container consumables once per invoice (container_quantity).
     *
     * @param  array<int, string>  $pendingConsumables
     */
    public function applyStockOutContainerConsumablesForCompletedInvoice(
        invoice $invoice,
        int $invoiceId,
        stockout $q,
        array &$pendingConsumables
    ): void {
        try {
            $consumables = consumable::where('is_container', 1)->get();

            foreach ($consumables as $consumable) {
                $need = (float) ($consumable->container_quantity ?? 0);
                $openBalance = (float) ($consumable->quantity ?? 0);

                if ($need <= 0) {
                    continue;
                }

                $take = min($need, max(0.0, $openBalance));
                $short = $need - $take;

                if ($take > 0) {
                    stockLogConsumable::create([
                        'consumable_id' => $consumable->id,
                        'voucher_no' => $invoice->invoiceno,
                        'ref_no' => 'Stock Out - Buyer Order No. - ' . $invoice->buyerorderno,
                        'quantity' => $take,
                        'opening_balance' => $openBalance,
                        'remaining_stock' => $openBalance - $take,
                        'type' => 2,
                        'remark' => 'Container Stock out',
                    ]);

                    $consumable->quantity = $openBalance - $take;
                    $consumable->save();
                }

                if ($short > 0.00001) {
                    ManualStockoutPendingConsumable::updateOrCreate(
                        [
                            'invoice_id' => $invoiceId,
                            'stock_id' => (int) $q->id,
                            'product_id' => 0,
                            'consumable_id' => (int) $consumable->id,
                        ],
                        [
                            'location' => null,
                            'reason' => "Container consumable (invoice): required {$need}, issued {$take}, still need {$short}",
                            'status' => 0,
                        ]
                    );
                    $pendingConsumables[(int) $consumable->id] = $consumable->name ?? $consumable->id;
                } else {
                    ManualStockoutPendingConsumable::where('stock_id', (int) $q->id)
                        ->where('consumable_id', (int) $consumable->id)
                        ->where('product_id', 0)
                        ->where('status', 0)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            \Log::error('applyStockOutContainerConsumablesForCompletedInvoice: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    public function stockcreate()
    {
        $invoice = invoice::where('status', '0')->get();
        return view('stockout/create', ['invoice' => $invoice]);
    }

    public function stockstore(Request $request)
    {
        //    return $request->all();
        $invoice = invoice::where('id', $request['invoice_id'])->first();
        $errorList = [];
        $containerConsumableShortages = []; // container consumables where quantity < container_quantity

        $containerConsumables = consumable::where('is_container', 1)->get();

        foreach ($containerConsumables as $cns) {
            $required = (float) ($cns->container_quantity ?? 0);
            if ($required <= 0) {
                continue;
            }
            $available = (float) ($cns->quantity ?? 0);
            if ($available < $required) {
                $containerConsumableShortages[(int) $cns->id] = [
                    'name' => $cns->name,
                    'required' => $required,
                    'available' => $available,
                ];
            }
        }

// container shortages are handled as pending rows after stockout (stock_id) exists

        if (!$invoice) {
            return redirect('/stockout')->with('error', 'Invoice not found.');
        }

        $isCartonInvoice = stripos($invoice->invoiceno, 'CARTON') !== false;
        $q  = stockout::where('invoice_id', $request['invoice_id'])->first();


        if (!$q) {
            $q = stockout::create([
                'invoice_id' => $request['invoice_id'],
                'buyer_ref_no' => $request['buyerorderno'],
            ]);
        }

        // If container consumable quantity is not enough, save pending rows.
        // These will be shown in `Manual Consumable Invoice Count`.
        if (!empty($containerConsumableShortages)) {
            foreach ($containerConsumableShortages as $consumableId => $meta) {
                $required = $meta['required'] ?? 0;
                $available = $meta['available'] ?? 0;

                ManualStockoutPendingConsumable::updateOrCreate(
                    [
                        'invoice_id' => (int) $request['invoice_id'],
                        'stock_id' => (int) $q->id,
                        'product_id' => 0,
                        'consumable_id' => (int) $consumableId,
                    ],
                    [
                        'location' => null,
                        'reason' => "Container consumable shortage (Required: {$required}, Available: {$available})",
                        'status' => 0,
                    ]
                );
            }
        }


        $totalConsumables = [];

        foreach ($request['ps'] as $ps) {
            $product = product::find($ps['product']);
            if (!$product) {
                $errorList[] = "Product Not Found (ID: {$ps['product']})";
                continue;
            }

            $wfConsumables = WfConsumable::where('product_id', $product->id)->get();
            foreach ($wfConsumables as $wf) {
                $consumable = consumable::find((int) ($wf->consumables_id ?? 0));
                if (! $consumable) {
                    continue;
                }
                if ($this->isMonthEndManagedConsumable($consumable) || $this->isHardwareMonthendConsumable($consumable)) {
                    // Exclude month-end and hardware month-end PO consumables from regular stock-out shortage validation.
                    continue;
                }
                $requiredQty = (float) ($wf->qty ?? 0) * (float) ($ps['receiveqty'] ?? 0);
                $totalConsumables[(int) $consumable->id] = ($totalConsumables[(int) $consumable->id] ?? 0) + $requiredQty;
            }
        }

        foreach ($totalConsumables as $consumableId => $requiredQty) {
            $cons = consumable::find($consumableId);
            if (!$cons || $cons->quantity < $requiredQty) {
                $errorList[] = "Consumable Shortage: " . ($cons ? $cons->name : $consumableId) .
                    " Required: {$requiredQty}, Available: " . ($cons->quantity ?? 0);
            }
        }



        $backup = StockoutRequestBackup::where('invoice_id', $request['invoice_id'])->first();

        if (!$backup) {
            $backup = StockoutRequestBackup::create([
                'invoice_id' => $request['invoice_id'],
                'buyer_order_no' => $request['buyerorderno'],
                'error_message' => implode(" | ", $errorList),
            ]);
        } else {
            $backup->update([
                'error_message' => implode(" | ", $errorList),
            ]);
        }

        foreach ($request['ps'] as $ps) {
            $existingProduct = StockoutRequestProduct::where('backup_id', $backup->id)
                ->where('product_id', $ps['product'] ?? null)
                ->first();

            if ($existingProduct) {
                // Update existing record: subtract quantities
                $existingProduct->orderqty -= $ps['remqty'] ?? 0;
                $existingProduct->receiveqty -= $ps['receiveqty'] ?? 0;
                $newRemaining = ($existingProduct->remainingqty ?? 0) - ($ps['receiveqty'] ?? 0);

                $existingProduct->remainingqty = $newRemaining;

                if ($existingProduct->remainingqty <= 0) {
                    $existingProduct->delete();
                } else {
                    $existingProduct->save();
                }
            } else {
                $remainingQty = ($ps['remqty'] ?? 0) - ($ps['receiveqty'] ?? 0);
                if ($ps['receiveqty'] > 0) {
                    StockoutRequestProduct::create([
                        'backup_id' => $backup->id,
                        'product_id' => $ps['product'] ?? null,
                        'ean' => $ps['EAN'] ?? null,
                        'orderqty' => $ps['remqty'] ?? 0,
                        'receiveqty' => $ps['receiveqty'] ?? 0,
                        'remainingqty' => $ps['receiveqty'],
                        'location' => $ps['location'] ?? null,
                    ]);
                }
            }
        }

        // Delete backup if no products left
        if ($backup->products()->count() == 0) {
            $backup->delete();
        }

        \DB::beginTransaction();
        try {
            $statusremqty = 0;
            $totalConsumables = [];
            $errorList = [];
            $pendingConsumables = [];


            $packingid = null;
            if (!$isCartonInvoice) {
                $packingid = packinglist::where('buyer_order_no', $request['buyerorderno'])->first();
                if (!$packingid) {
                    // If you expect packinglist for non-carton invoices, warn and skip batch logic later
                    \Log::warning('Packing list not found for buyer_order_no: ' . $request['buyerorderno']);
                    return back()->with('error', 'Packing list nahi mili for Buyer Order No: ' . $request['buyerorderno']);
                }
            }



            foreach ($request['ps'] as $ps) {


                $product = product::where('id', $ps['product'])->first();
                if (!$product) {
                    \Log::warning("Product not found (id): " . $ps['product']);
                    return back()->with('error', "Product nahi mila (ID: {$ps['product']})");
                    // continue;

                }
                $batch_no = null;
                if (!$isCartonInvoice && $packingid) {
                    $batch_no = packingListProduct::where('product_id', $ps['product'])
                        ->where('packinglist_id', $packingid->id)
                        ->first();
                    if (!$batch_no) {
                        \Log::warning("packingListProduct not found for product_id {$ps['product']} and packinglist_id {$packingid->id}");
                        return back()->with('error', "Packing list product nahi mila (Product ID: {$ps['product']}, Packinglist ID: {$packingid->id})");
                    }
                }

                stockoutTable::create([
                    'product_id' => $ps['product'],
                    'stock_id' => $q->id,
                    'ean' => $ps['EAN'],
                    'orderqty' => $ps['remqty'],
                    'receiveqty' => $ps['receiveqty'],
                    'remainingqty' => $ps['remqty'] - $ps['receiveqty'],
                    'location' => $ps['location'],

                ]);

                $invProduct = invoiceTable::where('invoice_id', $request['invoice_id'])->where('product_id', $ps['product'])->first();
                if ($invProduct) {
                    $invProduct->remqty = $ps['remqty'] - $ps['receiveqty'];
                    $invProduct->save();
                }

                if (trim((string) ($ps['location'] ?? '')) != '') {
                    $plocation = productLocations::where('product_id', $ps['product'])
                        ->where('location', $ps['location'])
                        ->first();
                    if ($plocation) {
                        $plocation->quantity = $plocation->quantity - $ps['receiveqty'];
                        $plocation->save();
                    }
                }

                //Track supplier invoice number
                $pbTable     =    pbTable::where('product_id', $product->id)->orderBy('created_at', 'DESC')->get();

                $qty = 0;
                $prod_quant = $product->quantity;
                foreach ($pbTable as $inn) {
                    $qty = $qty + $inn->receiveqty;
                    if ($qty > $product->quantity) {
                        $inn->prod_remaining = $prod_quant;
                        $inn->save();
                        break;
                    } else {
                        $prod_quant = $prod_quant - $inn->receiveqty;
                        $inn->prod_remaining = $inn->receiveqty;
                    }
                    $inn->save();
                }

                $pbTable1     =    pbTable::where('product_id', $product->id)->whereNotNull('prod_remaining')->where('prod_remaining', '>', '0')->orderBy('created_at', 'ASC')->get();
                $qty = 0;
                $supp_inv_no = "";
                $prod_quant = $ps['receiveqty'];
                foreach ($pbTable1 as $pb) {
                    if (!$pb->purchaseBillTable) {
                        \Log::error('Missing purchaseBillTable for pbTable ID: ' . $pb->id);
                        continue;
                    }
                    $qty = $qty + $pb->prod_remaining;
                    $break = 0;
                    if ($qty > $ps['receiveqty']) {
                        $outqty = $pb->prod_remaining;
                        $pb->prod_remaining = $pb->prod_remaining - $prod_quant;
                        $pb->save();
                        $outqty = $outqty - $pb->prod_remaining;
                        $supplierInvNo = $pb->purchaseBillTable->supp_inv_no;
                        $supp_inv_no = $supp_inv_no . $supplierInvNo . ' (' . $outqty . ')';
                        $break = 1;
                    } else {
                        $outqty = $pb->prod_remaining;
                        $prod_quant = $prod_quant - $outqty;
                        $supplierInvNo = $pb->purchaseBillTable->supp_inv_no;
                        $supp_inv_no = $supp_inv_no . $supplierInvNo . ' (' . $pb->prod_remaining . '), ';
                        $pb->prod_remaining = 0;
                        $pb->save();
                    }


                    if ($break) {
                        break;
                    }
                }
                $shortBy = $this->applyStockOutBatchRefAndStockLogs(
                    $invoice,
                    $product,
                    $isCartonInvoice ? null : $batch_no,
                    $ps['receiveqty'],
                    (string) ($supp_inv_no ?? ''),
                    (int) $q->id,
                    $isCartonInvoice
                );
                if ($shortBy > 0) {
                    $productLabel = trim((string) ($product->code ?? '')) !== ''
                        ? $product->code
                        : ('ID ' . $product->id);
                    throw new \RuntimeException(
                        'Insufficient batch stock for product ' . $productLabel . ' (short by ' . $shortBy . ').'
                    );
                }

                $this->applyStockOutConsumableDeductionsForLine(
                    $invoice,
                    (int) $request['invoice_id'],
                    $q,
                    $product,
                    $ps,
                    $pendingConsumables
                );
            }

            $invoiceTableCheck = invoiceTable::where('invoice_id', $request['invoice_id'])->get();

            foreach ($invoiceTableCheck as $invoiceTableChecks) {
                $statusremqty = $statusremqty + $invoiceTableChecks->remqty;
            }

            $invoice->status = ($statusremqty == 0) ? 1 : 0;
            if ($invoice->status == 1) {
                $this->applyStockOutContainerConsumablesForCompletedInvoice(
                    $invoice,
                    (int) $request['invoice_id'],
                    $q,
                    $pendingConsumables
                );
            }
            $invoice->save();

            \DB::commit();
            $msgParts = [];
            if (!empty($pendingConsumables)) {
                $msgParts[] = 'Pending consumables: ' . implode(', ', array_values($pendingConsumables));
            }
            $msg = 'Stockout was added successfully.';
            if (count($msgParts) > 0) {
                $msg .= ' ' . implode(' | ', $msgParts);
            }

            return redirect('/stockout')->with('success', $msg);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('stockstore error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect('/stockout')->with('error', 'An error occurred while adding stockout: ' . $e->getMessage());
        }
    }


    public function stockreversestore(Request $request)
    {
        $invoice = invoice::where('id', 586)->first(); // Hardcoded invoice_id = 541
        $statusremqty = 0;

        // Find the stockout record and delete or revert it
        $q = stockout::where('invoice_id', 586)->first(); // Hardcoded invoice_id = 541

        if ($q) {
            // Data inserted directly into the function
            $psData = [
                ['product' => 2996, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 554, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 618, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 620, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 1371, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 1344, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 768, 'receiveqty' => 6, 'location' => NULL],
                ['product' => 3284, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 3109, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 3047, 'receiveqty' => 13, 'location' => NULL],
                ['product' => 3046, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 3042, 'receiveqty' => 2, 'location' => NULL],
                ['product' => 3015, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 2994, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 2986, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 2982, 'receiveqty' => 6, 'location' => NULL],
                ['product' => 2980, 'receiveqty' => 7, 'location' => NULL],
                ['product' => 2974, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 2794, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 2964, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 2958, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 2947, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 2847, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 2750, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 2745, 'receiveqty' => 3, 'location' => NULL],
                ['product' => 2650, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 2635, 'receiveqty' => 17, 'location' => NULL],
                ['product' => 2631, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 2621, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 2614, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 2609, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 2633, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 2573, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 2572, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 2140, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 2076, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 1855, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 1853, 'receiveqty' => 8, 'location' => NULL],
                ['product' => 1851, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 1598, 'receiveqty' => 2, 'location' => NULL],
                ['product' => 1357, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 1370, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 793, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 789, 'receiveqty' => 6, 'location' => NULL],
                ['product' => 1921, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 2961, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 600, 'receiveqty' => 4, 'location' => NULL],
                ['product' => 479, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 305, 'receiveqty' => 20, 'location' => NULL],
                ['product' => 274, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 465, 'receiveqty' => 10, 'location' => NULL],
                ['product' => 162, 'receiveqty' => 6, 'location' => NULL],
                ['product' => 69, 'receiveqty' => 5, 'location' => NULL],
                ['product' => 3401, 'receiveqty' => 2, 'location' => NULL],
                ['product' => 3372, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3382, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3368, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3370, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3338, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3383, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3380, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3381, 'receiveqty' => 2, 'location' => NULL],
                ['product' => 3385, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 3361, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 486, 'receiveqty' => 2, 'location' => NULL],
                ['product' => 2780, 'receiveqty' => 1, 'location' => NULL],
                ['product' => 1282, 'receiveqty' => 1, 'location' => NULL],

            ];

            foreach ($psData as $ps) {
                // Reverse the creation of stockoutTable entry by deleting it
                $stockoutRecord = stockoutTable::where('stock_id', $q->id)
                    ->where('product_id', $ps['product'])
                    ->first();
                if ($stockoutRecord) {
                    $stockoutRecord->delete();
                }

                // Reverse the invoice product remaining quantity
                $invProduct = invoiceTable::where('invoice_id', 586) // Hardcoded invoice_id = 541
                    ->where('product_id', $ps['product'])->first();
                $invProduct->remqty = $invProduct->remqty + $ps['receiveqty'];
                $invProduct->save();

                // Reverse product location quantity
                if (trim($ps['location']) != "") {
                    $plocation = productLocations::where('product_id', $ps['product'])
                        ->where('location', $ps['location'])->first();
                    if ($plocation) {
                        $plocation->quantity = $plocation->quantity + $ps['receiveqty'];
                        $plocation->save();
                    }
                }

                // Reverse product quantity
                $product = product::where('id', $ps['product'])->first();
                $product->quantity = $product->quantity + $ps['receiveqty'];
                $product->save();

                // Track supplier invoice number for stock reversal (stock-in)
                $pbTable = pbTable::where('product_id', $product->id)->orderBy('created_at', 'ASC')->get(); // ASC to reverse back in

                $qty = 0;
                $prod_quant = $ps['receiveqty']; // Quantity to reverse back into stock

                foreach ($pbTable as $inn) {
                    // Add stock back instead of subtracting
                    $qty = $qty + $inn->prod_remaining;

                    if ($qty > $ps['receiveqty']) {
                        $inn->prod_remaining = $inn->prod_remaining + $prod_quant;
                        $inn->save();
                        break;
                    } else {
                        $prod_quant = $prod_quant - $inn->prod_remaining;
                        $inn->prod_remaining = $inn->prod_remaining + $inn->receiveqty; // Adding stock back
                    }
                    $inn->save();
                }

                // Get updated product details after reversal
                $pbTable1 = pbTable::where('product_id', $product->id)->whereNotNull('prod_remaining')->where('prod_remaining', '>', 0)->orderBy('created_at', 'DESC')->get(); // Reversed order

                $qty = 0;
                $supp_inv_no = "";
                $prod_quant = $ps['receiveqty']; // Total quantity reversed

                foreach ($pbTable1 as $pb) {
                    if (is_null($pb->purchaseBillTable)) {
                        continue;
                    }

                    $qty = $qty + $pb->prod_remaining;
                    $break = 0;

                    if ($qty > $ps['receiveqty']) {
                        $in_qty = $pb->prod_remaining;
                        $pb->prod_remaining = $pb->prod_remaining + $prod_quant;
                        $pb->save();
                        $in_qty = $in_qty - $pb->prod_remaining;
                        $supplierInvNo = $pb->purchaseBillTable?->supp_inv_no;
                        $supp_inv_no = $supp_inv_no . $supplierInvNo . ' (' . $in_qty . ')';
                        $break = 1;
                    } else {
                        $in_qty = $pb->prod_remaining;
                        $prod_quant = $prod_quant - $in_qty;
                        $supplierInvNo = $pb->purchaseBillTable?->supp_inv_no;
                        $supp_inv_no = $supp_inv_no . $supplierInvNo . ' (' . $pb->prod_remaining . '), ';
                        $pb->prod_remaining = $pb->prod_remaining + $pb->receiveqty; // Adding back
                        $pb->save();
                    }

                    if ($break) {
                        break;
                    }
                }


                $voucher = "GVD/UK/2425/041";
                // Reverse stock log entry
                $stockLog = stockLog::where('product_id', $ps['product'])
                    ->where('voucher_no', $voucher)
                    ->where('type', 2) // Stock Out type
                    ->first();
                if ($stockLog) {
                    $stockLog->delete();
                }
            }

            // Delete the stockout record
            $q->delete();
        }

        // Recalculate the remaining quantity for the invoice
        //$invoiceTableCheck = invoiceTable::where('invoice_id', 541)->get(); // Hardcoded invoice_id = 541

        //foreach ($invoiceTableCheck as $invoiceTableChecks) {
        //$statusremqty += $invoiceTableChecks->remqty;
        // }

        //$invoice->status = ($statusremqty == 0) ? 1 : 0; // Mark as completed if no remaining quantity
        //$invoice->save();

        return redirect('/stockout')->with('success', 'Stockout reversal was processed successfully.');
    }
    public function stockindex(Request $request)
    {
        $search = $request->input('search');
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        $stockout = stockout::with([
            'invoice',
            'stockoutTable.product',
            'stockoutRequestBackup' // <-- Add this relation
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('invoice', function ($q2) use ($search) {
                        $q2->where('invoiceno', 'like', '%' . $search . '%');
                    })
                        ->orWhereHas('stockoutTable.product', function ($q2) use ($search) {
                            $q2->where('code', 'like', '%' . $search . '%');
                        })
                        ->orWhere('buyer_ref_no', 'like', '%' . $search . '%');
                });
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('created_at', [$date_from, $date_to]);
            })
            ->orderByDesc('id')
            ->paginate(50);

        return view('stockout/index', ['stockout' => $stockout]);
    }


    public function stockmodal($id)
    {
        $stockout = stockout::find($id)->where('id', $id)->first();
        $stockoutTable = stockoutTable::where('stock_id', $id)->get();
        $companyDetails = setting::first();
        return view('stockout/view', ['stockout' => $stockout, 'stockoutTable' => $stockoutTable, 'companyDetails' => $companyDetails]);
    }

    // public function viewreport()
    // {
    //     $pendinginv = invoice::where('status','0')->get();
    //     return view('report/index', ['pendinginv'=>$pendinginv]);
    // }

    public function viewshutout()
    {

        $invoiceTable = invoiceTable::where('remqty', '>', 0)->get();
        return view('report/shutout', ['invoiceTable' => $invoiceTable]);
    }

    public function swapping()
    {
        $swaps = productSwapping::orderBy('created_at', 'desc')->get();
        return view('invoice/swapping', ['swaps' => $swaps]);
    }

    public function createswapping(Request $request)
    {
        $products     =    product::all();
        $batches = StockLog::whereNotNull('batch_no')
            ->select('product_id', 'batch_no', 'batch_balance')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('stock_log')
                    ->whereNotNull('batch_no')
                    ->groupBy('product_id', 'batch_no');
            })
            ->get();

        return view('invoice/createswapping', ['products' => $products, 'batches' => $batches]);
    }

    

    // public function storeswapping(Request $request){
    // 	//echo '<pre>';print_r($_REQUEST);die;
    // 	foreach($request['po'] as $prod){

    //         $log_data=stockLog::where('product_id',$prod['product'])->where('batch_no',$prod['batch_no'])->latest('id')->first();

    // 		$swe = productSwapping::create([
    // 			'invoice_no'=>$request['invoice_no'],
    // 			'product_id'=>$prod['product'],
    // 			'swapped_with'=>$prod['swapped_with'],
    // 			'quantity'=>$prod['quantity']
    // 		]);
    // 		$product 		= 	product::where('id',$prod['product'])->first();
    // 		$swapped_with 	= 	product::where('id',$prod['swapped_with'])->first();

    // 		$pbTable 	=	pbTable::where('product_id',$product->id)->whereRaw('(SELECT supplier_id from purchase_order where id = pb_table.purchaseOrder_id LIMIT 1) != 17')->orderBy('created_at','DESC')->get();

    // 		$qty = 0;
    // 		$prod_quant = $product->quantity;
    // 		foreach($pbTable as $inn){
    // 			$qty = $qty + $inn->receiveqty;
    // 			if($qty > $product->quantity){
    // 				$inn->prod_remaining = $prod_quant;
    // 				$inn->save();
    // 				break;
    // 			}else{
    // 				$prod_quant = $prod_quant - $inn->receiveqty;
    // 				$inn->prod_remaining = $inn->receiveqty;
    // 			}
    // 			$inn->save();
    // 		}

    // 		$pbTable1 	=	pbTable::where('product_id',$product->id)->whereNotNull('prod_remaining')->where('prod_remaining','>','0')->whereRaw('(SELECT supplier_id from purchase_order where id = pb_table.purchaseOrder_id LIMIT 1) != 17')->orderBy('created_at','ASC')->get();
    // 		$qty = 0;
    // 		$supp_inv_no = "";
    // 		$prod_quant = $prod['quantity'];
    // 		foreach($pbTable1 as $pb){
    //             if (is_null($pb->purchaseBillTable)) {
    //                 continue;
    //             }
    // 			$qty = $qty + $pb->prod_remaining;
    // 			$break = 0;
    // 			if($qty > $prod['quantity']){
    // 				$outqty = $pb->prod_remaining;
    // 				$pb->prod_remaining = $pb->prod_remaining - $prod_quant;
    // 				$pb->remarks = 'Converted to '.$swapped_with->code.' ('. $prod_quant .' pieces)';
    // 				$pb->save();
    // 				$outqty = $outqty - $pb->prod_remaining;
    // 				$supp_inv_no = $supp_inv_no . $pb->purchaseBillTable->supp_inv_no . ' (' . $outqty . ')';
    // 				$break = 1;
    // 			}else{
    // 				$outqty = $pb->prod_remaining;
    // 				$prod_quant = $prod_quant - $outqty;
    // 				$supp_inv_no = $supp_inv_no . $pb->purchaseBillTable->supp_inv_no . ' (' . $pb->prod_remaining . '), ';
    // 				$pb->prod_remaining = 0;
    // 				$pb->remarks = 'Converted to '.$swapped_with->code.' ('. $outqty .' pieces)';
    // 				$pb->save();
    // 			}

    // 			$purchaseBill = purchaseBill::where('id',$pb->purchasebill_id)->first();


    // 			$p = pbTable::create([
    //                 'product_id' => $swapped_with->id,
    //                 'ean' => $swapped_with->EAN,
    //                 'purchaseOrder_id' => $pb->purchaseOrder_id,
    //                 'purchaseBill_id' => $purchaseBill->id,
    //                 'orderqty' => $outqty,
    //                 'receiveqty' => $outqty,
    //                 'remainingqty' => 0,
    //                 'rate' => $pb->rate,
    //                 'amount' => $pb->rate * $outqty,
    // 				'remarks' => 'Swapped from '.$product->code.' ('. $outqty .' pieces)'
    //             ]);

    // 			if($break){
    // 				break;
    // 			}

    // 		}
    //         if(!empty($log_data)){
    //             stockLog::create([
    //                 'ref_no'=>'SWAP OUT - '. $request['invoice_no'],
    //                 'product_id'=>$prod['product'],
    //                 'quantity'=>$prod['quantity'],
    //                 'type'=>2,
    //                 'opening_balance'=>$product->quantity,
    //                 'remaining_stock'=>$product->quantity - $prod['quantity'],
    //                 'voucher_no' => $supp_inv_no,
    //                 'entity_id' => $swe->id,

    //                 'batch_no'  => $prod['batch_no'],
    //                 'batch_balance'=>$log_data->batch_balance-$prod['quantity'],
    //         ]);

    //             stockLog::create([
    //                 'ref_no'=>'SWAP IN - '. $request['invoice_no'],
    //                 'product_id'=>$prod['swapped_with'],
    //                 'quantity'=>$prod['quantity'],
    //                 'type'=>1,
    //                 'opening_balance'=>$swapped_with->quantity,
    //                 'remaining_stock'=>$swapped_with->quantity + $prod['quantity'],
    //                 'voucher_no' => $supp_inv_no,
    //             'entity_id' => $swe->id,
    //                'batch_no'  => $prod['batch_no'],
    //                 'batch_balance'=>$prod['quantity']
    //         ]);

    //         }else{
    //             stockLog::create([
    //                 'ref_no'=>'SWAP OUT - '. $request['invoice_no'],
    //                 'product_id'=>$prod['product'],
    //                 'quantity'=>$prod['quantity'],
    //                 'type'=>2,
    //                 'opening_balance'=>$product->quantity,
    //                 'remaining_stock'=>$product->quantity - $prod['quantity'],
    //                 'voucher_no' => $supp_inv_no,
    //                 'entity_id' => $swe->id,


    //         ]);

    //             stockLog::create([
    //                 'ref_no'=>'SWAP IN - '. $request['invoice_no'],
    //                 'product_id'=>$prod['swapped_with'],
    //                 'quantity'=>$prod['quantity'],
    //                 'type'=>1,
    //                 'opening_balance'=>$swapped_with->quantity,
    //                 'remaining_stock'=>$swapped_with->quantity + $prod['quantity'],
    //                 'voucher_no' => $supp_inv_no,
    //             'entity_id' => $swe->id,

    //         ]);
    //         }
    // 		//echo $supp_inv_no;die;


    // 		$stock = stockout::create([
    // 			'invoice_id'=>0,
    // 			'buyer_ref_no'=>$request['invoice_no'],
    // 			'swap_id'=>$swe->id
    // 		]);
    // 		stockoutTable::create([
    // 			'ref_no'=>$request['invoice_no'],
    // 			'product_id'=>$product->id,
    // 			'stock_id'=>$stock->id,
    // 			'receiveqty'=>$prod['quantity'],
    // 			'orderqty'=>$prod['quantity'],
    // 			'remainingqty'=> 0,
    // 			'ean'=>$product->EAN,
    // 			'supp_inv_no'=>$supp_inv_no,
    // 		]);

    // 		$product->quantity = $product->quantity - $prod['quantity'];
    // 		$product->save();

    // 		$swapped_with->quantity = $swapped_with->quantity + $prod['quantity'];
    // 		$swapped_with->save();
    // 	}
    // 	return redirect('/invoice/swapping')->with('success', 'Swapping was added successfully.');
    // }



    public function storeswapping(Request $request)
    {
        $poRows = array_values($request['po'] ?? []);
        if ($poRows === []) {
            return redirect('/invoice/swapping')->with('error', 'No product lines submitted.');
        }

        $snapshots = [];
        foreach ($poRows as $idx => $prod) {
            $batchNos = $prod['batch_no'] ?? [];
            if (! is_array($batchNos)) {
                $batchNos = ($batchNos !== null && $batchNos !== '') ? [(string) $batchNos] : [];
            }
            $batchNos = array_values(array_unique(array_filter(array_map('strval', $batchNos))));
            $productOutId = (int) ($prod['product'] ?? 0);
            $qty = (int) ($prod['quantity'] ?? 0);
            if ($productOutId <= 0 || $qty <= 0 || $batchNos === []) {
                return redirect()->back()->withInput()->with('error', 'Invalid product, batch, or quantity on row '.($idx + 1).'.');
            }
            $v = $this->validateSwappingRefSelection($productOutId, $qty, $batchNos, $prod['ref_selection_json'] ?? '');
            if (! $v['ok']) {
                return redirect()->back()->withInput()->with('error', $v['message']);
            }
            $snapshots[$idx] = $v['snapshot'];
        }

        try {
            DB::transaction(function () use ($request, $poRows, $snapshots) {
                foreach ($poRows as $idx => $prod) {
                    $batchNos = $prod['batch_no'] ?? [];
                    if (! is_array($batchNos)) {
                        $batchNos = ($batchNos !== null && $batchNos !== '') ? [(string) $batchNos] : [];
                    }
                    $batchNos = array_values(array_unique(array_filter(array_map('strval', $batchNos))));

                    $productOutId = (int) $prod['product'];
                    $productInId = (int) $prod['swapped_with'];
                    $qtyTotal = (int) $prod['quantity'];
                    $snapshot = $snapshots[$idx] ?? [];
                    $snapshotSiByBatch = $this->supplierInvoiceIdsByBatchFromRefSnapshot($snapshot);

                    $product = product::find($productOutId);
                    $swapped_with = product::find($productInId);
                    if (! $product || ! $swapped_with) {
                        throw new \RuntimeException('Product not found.');
                    }

                    $openBalanceOut = (int) $product->quantity;
                    $openBalanceIn = (int) $swapped_with->quantity;

                    $swapInQtyByRefNumber = [];
                    $swapOutRefParts = [];
                    $swapIdsUsedRow = [];
                    $lastBatchProductOut = null;
                    $lastBatchProductIn = null;
                    $lastSupplierName = null;
                    $lastBatchNoStr = '';

                    $remainingQty = $qtyTotal;
                    foreach ($batchNos as $batchNo) {
                        if ($remainingQty <= 0) {
                            break;
                        }

                        $batch = Batch::where('batch_no', $batchNo)->first();
                        if (! $batch) {
                            throw new \RuntimeException('Batch not found: '.$batchNo);
                        }

                        $supplier = supplier::where('id', $batch->supplier_id)->first();
                        $lastSupplierName = $supplier->c_name ?? null;

                        $batchProduct = BatchProduct::where('batch_id', $batch->id)
                            ->where('product_id', $productOutId)
                            ->first();
                        if (! $batchProduct || $batchProduct->quantity <= 0) {
                            throw new \RuntimeException('Batch quantity not available for product out on batch '.$batchNo.'.');
                        }

                        $deductQty = min($remainingQty, (int) $batchProduct->quantity);

                        $batchProductswap = BatchProduct::where('batch_id', $batch->id)
                            ->where('product_id', $productInId)
                            ->first();
                        if ($batchProductswap) {
                            $batchProductswap->quantity += $deductQty;
                            $batchProductswap->save();
                        } else {
                            $batchProductswap = BatchProduct::create([
                                'batch_id' => $batch->id,
                                'product_id' => $productInId,
                                'quantity' => $deductQty,
                                'date' => now()->toDateString(),
                                'supp_in_no' => 'Swapping In',
                            ]);
                        }

                        $batchProduct->quantity -= $deductQty;
                        $batchProduct->save();

                        $preferredSiIds = $snapshotSiByBatch[$batchNo] ?? [];
                        $snapshotLinesThisBatch = $this->refSnapshotRowsForBatch($snapshot, $batchNo);
                        $this->deductSwapOutRefForBatchSlice(
                            $batchNo,
                            $productOutId,
                            $productInId,
                            $deductQty,
                            $preferredSiIds,
                            $swapInQtyByRefNumber,
                            $swapIdsUsedRow,
                            $swapOutRefParts,
                            $snapshotLinesThisBatch
                        );

                        $lastBatchProductOut = $batchProduct;
                        $lastBatchProductIn = $batchProductswap;
                        $lastBatchNoStr = $batchNo;
                        $remainingQty -= $deductQty;
                    }

                    if ($remainingQty > 0) {
                        throw new \RuntimeException('Insufficient batch quantity to complete swap.');
                    }

                    $refNumsForSwapRow = [];
                    foreach ($swapInQtyByRefNumber as $k => $q) {
                        if ((int) $q <= 0) {
                            continue;
                        }
                        $parts = explode("\0", (string) $k, 2);
                        $rn = trim((string) ($parts[1] ?? ''));
                        if ($rn !== '') {
                            $refNumsForSwapRow[$rn] = true;
                        }
                    }
                    $refNumsForSwapRow = array_keys($refNumsForSwapRow);
                    $swe = productSwapping::create([
                        'invoice_no' => $request['invoice_no'],
                        'product_id' => $productOutId,
                        'swapped_with' => $productInId,
                        'quantity' => $qtyTotal,
                        'reference_number' => implode(', ', $refNumsForSwapRow),
                        'ref_quantity' => 0,
                        'ref_source_snapshot' => $snapshots[$idx] ?? [],
                    ]);

                    $swapInRefParts = [];
                    foreach ($swapInQtyByRefNumber as $k => $qAdd) {
                        $qAdd = (int) $qAdd;
                        $parts = explode("\0", (string) $k, 2);
                        $bn = trim((string) ($parts[0] ?? ''));
                        $rn = trim((string) ($parts[1] ?? ''));
                        if ($qAdd <= 0 || $rn === '') {
                            continue;
                        }

                        $openIn = $this->openQtySumForInProductOnBatchReference($bn, $rn, $productInId);
                        $labelIn = $this->supplierInvoiceLabelForBatchReference($bn, $rn);

                        $this->createUniqueRefForSwapIn($bn, $rn, $productInId, $qAdd);
                        $swapInRefParts[] = $labelIn.'('.$qAdd.'/'.(int) $openIn.')';
                    }

                    $pbTable = pbTable::where('product_id', $product->id)
                        ->whereRaw('(SELECT supplier_id from purchase_order where id = pb_table.purchaseOrder_id LIMIT 1) != 17')
                        ->orderBy('created_at', 'DESC')
                        ->get();

                    $qtyPb = 0;
                    $prod_quant = $product->quantity;
                    foreach ($pbTable as $inn) {
                        $qtyPb += $inn->receiveqty;
                        if ($qtyPb > $product->quantity) {
                            $inn->prod_remaining = $prod_quant;
                            $inn->save();
                            break;
                        } else {
                            $prod_quant -= $inn->receiveqty;
                            $inn->prod_remaining = $inn->receiveqty;
                        }
                        $inn->save();
                    }

                    $pbTable1 = pbTable::where('product_id', $product->id)
                        ->whereNotNull('prod_remaining')
                        ->where('prod_remaining', '>', 0)
                        ->whereRaw('(SELECT supplier_id from purchase_order where id = pb_table.purchaseOrder_id LIMIT 1) != 17')
                        ->orderBy('created_at', 'ASC')
                        ->get();

                    $qtyPb = 0;
                    $supp_inv_no = '';
                    $prod_quant = $qtyTotal;
                    foreach ($pbTable1 as $pb) {
                        if (is_null($pb->purchaseBillTable)) {
                            continue;
                        }

                        $qtyPb += $pb->prod_remaining;
                        $break = 0;

                        if ($qtyPb > $qtyTotal) {
                            $outqty = $pb->prod_remaining;
                            $pb->prod_remaining -= $prod_quant;
                            $pb->remarks = 'Converted to '.$swapped_with->code.' ('.$prod_quant.' pieces)';
                            $pb->save();
                            $outqty = $outqty - $pb->prod_remaining;
                            $supp_inv_no .= $pb->purchaseBillTable->supp_inv_no.' ('.$outqty.')';
                            $break = 1;
                        } else {
                            $outqty = $pb->prod_remaining;
                            $prod_quant -= $outqty;
                            $supp_inv_no .= $pb->purchaseBillTable->supp_inv_no.' ('.$pb->prod_remaining.'), ';
                            $pb->prod_remaining = 0;
                            $pb->remarks = 'Converted to '.$swapped_with->code.' ('.$outqty.' pieces)';
                            $pb->save();
                        }

                        if ($break) {
                            break;
                        }
                    }

                    $swapOutRefLine = implode(', ', array_filter($swapOutRefParts));
                    $swapInRefLine = implode(', ', array_filter($swapInRefParts));

                    stockLog::create([
                        'ref_no' => 'SWAP OUT - '.$request['invoice_no'],
                        'product_id' => $productOutId,
                        'quantity' => $qtyTotal,
                        'type' => 2,
                        'opening_balance' => $openBalanceOut,
                        'remaining_stock' => $openBalanceOut - $qtyTotal,
                        'voucher_no' => $supp_inv_no,
                        'entity_id' => $swe->id,
                        'batch_no' => count($batchNos) > 1 ? implode(', ', $batchNos) : $lastBatchNoStr,
                        'batch_balance' => $lastBatchProductOut ? (int) $lastBatchProductOut->quantity : null,
                        'supplier_name' => $lastSupplierName,
                        'supplier_inv_no' => $request['invoice_no'],
                        'reference_number' => $swapOutRefLine,
                    ]);

                    stockLog::create([
                        'ref_no' => 'SWAP IN - '.$request['invoice_no'],
                        'product_id' => $productInId,
                        'quantity' => $qtyTotal,
                        'type' => 1,
                        'opening_balance' => $openBalanceIn,
                        'remaining_stock' => $openBalanceIn + $qtyTotal,
                        'voucher_no' => $supp_inv_no,
                        'entity_id' => $swe->id,
                        'batch_no' => count($batchNos) > 1 ? implode(', ', $batchNos) : $lastBatchNoStr,
                        'batch_balance' => $lastBatchProductIn ? (int) $lastBatchProductIn->quantity : null,
                        'supplier_name' => $lastSupplierName,
                        'supplier_inv_no' => $request['invoice_no'],
                        'reference_number' => $swapInRefLine,
                        'reference_quantity' => $qtyTotal,
                    ]);

                    $stock = stockout::create([
                        'invoice_id' => 0,
                        'buyer_ref_no' => $request['invoice_no'],
                        'swap_id' => $swe->id,
                    ]);

                    stockoutTable::create([
                        'ref_no' => $request['invoice_no'],
                        'product_id' => $product->id,
                        'stock_id' => $stock->id,
                        'receiveqty' => $qtyTotal,
                        'orderqty' => $qtyTotal,
                        'remainingqty' => 0,
                        'ean' => $product->EAN,
                        'supp_inv_no' => $supp_inv_no,
                    ]);

                    $product->quantity = $openBalanceOut - $qtyTotal;
                    $product->save();

                    $swapped_with->quantity = $openBalanceIn + $qtyTotal;
                    $swapped_with->save();

                    app(\App\Services\Carton\CartonSwapService::class)->mirrorFurnitureSwapCarton(
                        $productOutId,
                        $productInId,
                        $qtyTotal,
                        (string) $request['invoice_no']
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with('error', $e->getMessage() ?: 'Swapping failed.');
        }

        return redirect('/invoice/swapping')->with('success', 'Swapping was added successfully.');
    }


    public function data()
    {
        $product = product::all();
        $batches = StockLog::whereNotNull('batch_no')
            ->select('product_id', 'batch_no', 'batch_balance')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('stock_log')
                    ->whereNotNull('batch_no')
                    ->groupBy('batch_no');
            })
            ->get();


        return response()->json(['product' => $product, 'batch' => $batches]);
    }

    public function updateCornerBill(Request $request)
    {
        $cbill = cornerBill::where('id', $request['id'])->first();
        $amount = 0.50;
        if (isset($cbill->id)) {
            if ($request['type'] == 'L') {
                $cbill->l                 = $request['value'];
                $cbill->total_l         = $request['value'] * $cbill->product_quantity;
                $cbill->total_l_amount     = $request['value'] * $cbill->product_quantity * $amount;
            } else {
                $cbill->corners                 = $request['value'];
                $cbill->total_corners             = $request['value'] * $cbill->product_quantity;
                $cbill->total_corners_amount    = $request['value'] * $cbill->product_quantity * $amount;
            }
            $cbill->save();
        }
        die;
    }

    public function updateShippingBill(Request $request)
    {
        $invoice = invoice::where('id', $request['id'])->first();
        $invoice->shipping_bill_no        = $request['shipping_bill_no'];
        $invoice->shipping_bill_date    = $request['shipping_bill_date'];
        $invoice->ewaybillno             = $request['ewaybillno'];
        $invoice->ewaybilldate             = $request['ewaybilldate'];
        $invoice->port_code             = $request['port_code'];
        $invoice->shipping_exchange_rate = $request['shipping_exchange_rate'];
        $invoice->bl_no                    = $request['bl_no'];
        $invoice->bl_date               = $request['bl_date'];
        $invoice->billty_no             = $request['billty_no'];
        $invoice->billty_date           = $request['billty_date'];
        $invoice->agent                 = $request['agent'];
        $invoice->port_of_loading       = $request['port_of_loading'];
        $invoice->port_of_discharge     = $request['port_of_discharge'];
        $invoice->ship_to               = $request['ship_to'];
        $invoice->bill_to                = $request['bill_to'];
        $invoice->save();
        return redirect('/invoice')->with('success', 'Shipping bill updated successfully.');
    }

    public function updateInvoiceExport(Request $request)
    {

        $invoice = invoice::where('id', $request['id'])->first();
        $invoice->booking_value      = @$request['booking_value'];
        //$invoice->fbc    = @$request['fbc'];
        $invoice->shipdawn            = date('Y-m-d', strtotime(@$request['shipdawn']));
        $invoice->inrat_booking_value          = @$request['booking_rate'];
        $invoice->tax_type             = @$request['tax_type'];
        /*dd($invoice->booking_value);*/
        $invoice->save();

        $ieExport = invexport::where('invoice_id', $request['id'])->get();

        if (isset($ieExport)) {
            foreach ($ieExport as $ieExport) {
                $ieExport->delete();
            }
        }
        if (isset($request['ie'])) {
            foreach ($request['ie'] as $ie) {

                invexport::create([
                    'realisation_date' => $ie['realisation_date'],
                    'realisation_fc' => $ie['realisation_fc'],
                    'invoice_id' => $request['id'],
                    'rate' => $ie['rate'],
                    'bank_reference' => $ie['bank_reference'],
                    'fbc' => $ie['fbc'],

                ]);
            }
        }

        return redirect('/invoice')->with('success', 'Invoice details updated successfully.');
    }

    public function packing_list()
    {
        $packing_list =    packingList::orderBy('id', 'DESC')->get();
        return view('invoice/packing_list', ['packing_list' => $packing_list]);
    }

    public function packing_create()
    {
        $batches = stockLog::whereNotNull('batch_no')
            ->whereNotNull('batch_balance') // Exclude null batch balances
            ->where('batch_balance', '>', 0) // Exclude batch balances equal to 0
            ->select('product_id', 'batch_no', 'batch_balance')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('stock_log')
                    ->whereNotNull('batch_no')
                    ->groupBy('product_id', 'batch_no');
            })
            ->get();

        return view('invoice/packing_create', compact('batches'));
    }

  
    public function packing_store(Request $request)
    {

        // return $request->all();


        $invRows = array_values($request['inv'] ?? []);
        $invRows = $this->normalizePackingInvBatchOrders($invRows);
        $productIds = array_values(array_unique(array_map(static function ($row) {
            return (int) ($row['product_id'] ?? 0);
        }, $invRows)));
        $productCodesById = product::whereIn('id', $productIds)->pluck('code', 'id');
        $snapshots = [];
        foreach ($invRows as $rowIdx => $inv) {
            $productId = (int) $inv['product_id'];
            $batchNos = $inv['batch_no'] ?? [];
            $qty = (int) $inv['quantity'];
            $v = $this->validatePackingRefSelection($productId, $qty, $batchNos, $inv['ref_selection_json'] ?? '');
            if (! $v['ok']) {
                $productCode = $productCodesById[$productId] ?? ('ID ' . $productId);
                $errorMessage = 'Row ' . ((int) $rowIdx + 1) . ' | Product Code: ' . $productCode . ' | ' . $v['message'];
                return redirect()->back()->withInput()->withErrors(['error' => $errorMessage]);
            }
            $snapshots[] = $v['snapshot'];
        }

        $q = packingList::create([
            'buyer_order_no' => strtoupper($request['buyerorderno']),
            'totalbox' => $request['totalbox'],
            'tquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'grosswt' => $request['grosswt']
        ]);

        foreach ($invRows as $idx => $inv) {
            $p = new packingListProduct();
            $p->product_id = $inv['product_id'];
            $p->packinglist_id = $q->id;
            $p->quantity = $inv['quantity'];
            $p->weight = $inv['weight'];
            $p->subtotalnetwt = $inv['subtotalnetwt'];
            $p->grosswt = $inv['grosswt'];
            $p->subtotalgrosswt = $inv['subtotalgrosswt'];
            $p->box = $inv['box'];
            $p->endBox = $inv['endBox'];
            $p->subTotalBox = $inv['subTotalBox'];
            $p->qtybox = $this->normalizePackingListQtyBox($inv['qtybox'] ?? null);
            $p->batch_no = json_encode($inv['batch_no']);
            $p->ref_source_snapshot = $snapshots[$idx] ?? [];
            $p->save();
        }


        return redirect('/invoice/packing_list')->with('success', 'Packing was added successfully.');
    }
    public function packing_view($id)
    {
        $packing = packingList::find($id);

        $product = product::all();
        // $batches = stockLog::whereNotNull('batch_no')
        // ->select('product_id', 'batch_no', 'batch_balance')
        // ->whereIn('id', function ($query) {
        //     $query->selectRaw('MAX(id)')
        //     ->from('stock_log')
        //     ->whereNotNull('batch_no')
        //     ->groupBy('product_id', 'batch_no');
        // })
        // ->get();


        $batches = stockLog::whereNotNull('batch_no')
            ->whereNotNull('batch_balance') // Exclude null batch balances
            ->where('batch_balance', '>', 0) // Exclude batch balances equal to 0
            ->select('product_id', 'batch_no', 'batch_balance')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('stock_log')
                    ->whereNotNull('batch_no')
                    ->groupBy('product_id', 'batch_no');
            })
            ->get();


        $packingListProduct = packingListProduct::where('packinglist_id', $id)->orderBy('box', 'ASC')->get();

        $refSelectionsByLine = [];
        foreach ($packingListProduct as $lineKey => $plp) {
            $batchNosForLine = array_values(array_filter(array_map('strval', $this->batchNoListFromPlpColumn($plp->batch_no))));

            $snap = $plp->ref_source_snapshot;
            if (is_array($snap) && $snap !== []) {
                $refSelectionsByLine[$lineKey] = $this->thinRefSourceSnapshotRows($snap);
            } else {
                $refSelectionsByLine[$lineKey] = $this->thinRefSourceSnapshotRows(
                    $this->buildRefSourceSnapshot((int) $plp->product_id, $batchNosForLine)
                );
            }
        }

        $batchErrors = [];
        $uniqueRefWarnings = [];
        if ($packing->status == 0) {

        foreach ($packingListProduct as $lineKey => $plProduct) {

                $totalBatchQty = 0;
                $batchNos = $this->batchNoListFromPlpColumn($plProduct->batch_no);

                foreach ($batchNos as $batchNo) {

                    $batch = Batch::where('batch_no', $batchNo)->first();

                    if (!$batch) continue;

                    $batchProduct = BatchProduct::where('batch_id', $batch->id)
                        ->where('product_id', $plProduct->product_id)
                        ->first();

                    if ($batchProduct) {
                        $totalBatchQty += $batchProduct->quantity;
                    }
                }

                // Compare required vs available qty
                if ($plProduct->quantity > $totalBatchQty) {

                    $batchErrors[] = [
                        'product_code' => $plProduct->product->code ?? '',
                        'required_qty' => $plProduct->quantity,
                        'available_qty' => $totalBatchQty,
                        'batches_used' => implode(',', $batchNos), // FIXED
                    ];
                }

                // Unique reference warnings: if selected unique reference pool is insufficient
                // (sum of remaining_qty per unique_referencenumber_id), show a display-only warning.
                $requiredQty = (int) ($plProduct->quantity ?? 0);
                if ($requiredQty <= 0) {
                    continue;
                }

                $selectedRefRows = $refSelectionsByLine[$lineKey] ?? [];
                if (!is_array($selectedRefRows)) {
                    continue;
                }

                // Build map: unique_referencenumber_id => batch_no (dedup uref ids).
                $urefIdToBatchNo = [];
                foreach ($selectedRefRows as $row) {
                    $urefId = (int) ($row['unique_referencenumber_id'] ?? 0);
                    $bn = trim((string) ($row['batch_no'] ?? ''));
                    if ($urefId > 0 && $bn !== '') {
                        $urefIdToBatchNo[$urefId] = $bn;
                    }
                }

                $urefIds = array_keys($urefIdToBatchNo);
                if ($urefIds === []) {
                    continue;
                }

                $availableByUref = DB::table('unique_referencenumber as ur')
                    ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
                    ->leftJoin('supplier_invoices as si', 'si.id', '=', 'ur.supplier_invoice_id')
                    ->whereIn('ur.id', $urefIds)
                    ->where('urp.product_id', (int) $plProduct->product_id)
                    ->where('urp.remaining_qty', '>', 0)
                    ->where(function ($q) {
                        $q->where('ur.is_old', 1)
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('ur.supplier_invoice_id')
                                    ->where('ur.supplier_invoice_id', '>', 0)
                                    ->whereNotNull('si.id')
                                    ->where('si.is_approved', 1);
                            });
                    })
                    ->select(
                        'ur.id as unique_referencenumber_id',
                        DB::raw('SUM(COALESCE(urp.remaining_qty, 0)) AS available_ref_qty')
                    )
                    ->groupBy('ur.id')
                    ->get();

                $availableMap = [];
                foreach ($urefIds as $uid) {
                    $availableMap[(int) $uid] = 0;
                }
                foreach ($availableByUref as $r) {
                    $uid = (int) ($r->unique_referencenumber_id ?? 0);
                    $qty = (int) ($r->available_ref_qty ?? 0);
                    if ($uid > 0) {
                        $availableMap[$uid] = $qty;
                    }
                }

                $totalAvailableSelectedRefs = 0;
                foreach ($availableMap as $qty) {
                    $totalAvailableSelectedRefs += (int) $qty;
                }

                if ($totalAvailableSelectedRefs < $requiredQty) {
                    foreach ($availableMap as $uid => $qty) {
                        // show only urefs that currently have some remaining qty
                        if ((int) $qty <= 0) {
                            continue;
                        }
                        $uniqueRefWarnings[] = [
                            'product_code' => optional($plProduct->product)->code ?? '',
                            'required_qty' => $requiredQty,
                            'total_available_qty' => $totalAvailableSelectedRefs,
                            'unique_referencenumber_id' => (int) $uid,
                            'batch_no' => $urefIdToBatchNo[$uid] ?? '',
                            'available_qty' => (int) $qty,
                        ];
                    }
                }
            }
        }

        if ($packing) {
            return view('invoice/packing_view', [
                'packing' => $packing,
                'product' => $product,
                'packingListProducts' => $packingListProduct,
                'batches' => $batches,
                'batchErrors' => $batchErrors,
                'uniqueRefWarnings' => $uniqueRefWarnings,
                'refSelectionsByLine' => $refSelectionsByLine,
            ]);
        } else {
            return redirect('/invoice/packing_list')->with('danger', 'invoice was not found.');
        }
    }


    public function lock_packing_list(Request $request)
    {
        $packing = packingList::where('id', $request->id)->first();
        if ($packing) {
            $packing->is_sent_for_invoice = 1;
            $packing->status = 1;
            $packing->update();
            return back()->with('success', 'Packing updated successfully.');
        }
        return back()->with('error', 'Packing list not found.');
    }


    public function unlock_packing_list(Request $request)
    {
        $packing = packingList::where('id', $request->id)->first();
        if ($packing) {
            $packing->is_sent_for_invoice = 0;
            $packing->status = 0;
            $packing->update();
            return back()->with('success', 'Packing updated successfully.');
        }
        return back()->with('error', 'Packing list not found.');
    }


    public function updatepacking($id, Request $request)
    {
        // return $request->all();
        $packing = packingList::where('id', $id)->first();

        if ($packing->status != 1) {
            $existingPlps = packingListProduct::where('packinglist_id', $id)->orderBy('box', 'ASC')->get();
            $invRows = array_values($request['inv'] ?? []);
            $invRows = $this->normalizePackingInvBatchOrders($invRows);
            $invRows = $this->mergePackingInvRefFromExistingRows($invRows, $existingPlps);
            $productIds = array_values(array_unique(array_map(static function ($row) {
                return (int) ($row['product_id'] ?? 0);
            }, $invRows)));
            $productCodesById = product::whereIn('id', $productIds)->pluck('code', 'id');
            $snapshots = [];
            foreach ($invRows as $rowIdx => $inv) {
                $productId = (int) $inv['product_id'];
                $batchNos = $inv['batch_no'] ?? [];
                $qty = (int) $inv['quantity'];
                $v = $this->validatePackingRefSelection(
                    $productId,
                    $qty,
                    $batchNos,
                    $inv['ref_selection_json'] ?? ''
                );
                if (! $v['ok']) {
                    $productCode = $productCodesById[$productId] ?? ('ID ' . $productId);
                    $errorMessage = 'Row ' . ((int) $rowIdx + 1) . ' | Product Code: ' . $productCode . ' | ' . $v['message'];
                    return back()->withInput()->withErrors(['error' => $errorMessage]);
                }
                $snapshots[] = $v['snapshot'];
            }

            try {
                DB::transaction(function () use ($packing, $id, $request, $invRows, $snapshots) {
                    // Update header only after all line validations succeed.
                    $packing->buyer_order_no = strtoupper($request['buyer_order_no']);
                    $packing->totalbox = $request['totalbox'];
                    $packing->tquantity = $request['tquantity'];
                    $packing->totalwt = $request['totalwt'];
                    $packing->grosswt = $request['grosswt'];

                    // Replace all existing lines after validation (data integrity).
                    packingListProduct::where('packinglist_id', $id)->delete();

                    foreach ($invRows as $idx => $inv) {
                        packingListProduct::create([
                            'product_id' => $inv['product_id'],
                            'packinglist_id' => $packing->id,
                            'quantity' => $inv['quantity'],
                            'weight' => $inv['weight'],
                            'subtotalnetwt' => $inv['subtotalnetwt'],
                            'grosswt' => $inv['grosswt'],
                            'subtotalgrosswt' => $inv['subtotalgrosswt'],
                            'box' => $inv['box'],
                            'endBox' => $inv['endBox'],
                            'subTotalBox' => $inv['subTotalBox'],
                            'qtybox' => $this->normalizePackingListQtyBox($inv['qtybox'] ?? null),
                            'batch_no' => json_encode($inv['batch_no']),
                            'ref_source_snapshot' => $snapshots[$idx] ?? [],
                        ]);
                    }

                    if (! $packing->save()) {
                        throw new \RuntimeException('Error occurred while saving invoice.');
                    }
                });

                return back()->with('success', 'Packing updated successfully.');
            } catch (\Throwable $e) {
                report($e);
                return back()->with('danger', $e->getMessage() ?: 'Error occurred while saving invoice.');
            }
        } else {
            return back()->with('danger', 'Editting not allowed on this packing list');
        }
    }
    public function packing_delete($id)
    {
        DB::beginTransaction();
        try {
            $packing = packingList::where('id', $id)->first();
            $packingListProduct = packingListProduct::where('packinglist_id', $id)->get();

            if ($packingListProduct) {
                foreach ($packingListProduct as $key => $value) {
                    $packingListProduct[$key]->delete();
                }
            }
            if ($packing) {
                if ($packing->delete()) {
                    // Remove package list id from container allocation as well 
                    ContainersAllocation::where('package_list_id', $id)->update(['package_list_generated' => 0, 'package_list_locked' => 0, 'package_list_id' => null]);

                    DB::commit();
                    return redirect('/invoice/packing_list')->with('success', 'Packing deleted successfully.');
                } else {
                    return redirect('/invoice/packing_list')->with('danger', 'Packing was not found.');
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('danger', $e->getMessage());
        }
    }

    public function packing_detail(Request $request, $id)
    {
        $packingListProducts = packingListProduct::with('product')
            ->where('packinglist_id', $id)
            ->get();

        $productIds = $packingListProducts->pluck('product_id')->toArray();
        $invoiceBuyerId = $request->query('buyer_id');
        $tempBuyerId = InvoicePricing::resolveTempBuyerIdForInvoiceBuyer($invoiceBuyerId);

        $prices = collect();
        if ($tempBuyerId !== null && !empty($productIds)) {
            $prices = pricingTable::whereIn('product_id', $productIds)
                ->where('buyer_id2', $tempBuyerId)
                ->where('productType', 1)
                ->orderBy('created_at', 'desc')
                ->get()
                ->unique('product_id')
                ->keyBy('product_id');
        }

        foreach ($packingListProducts as $pp) {
            $pricing = $prices->get($pp->product_id);
            if ($pp->product) {
                $pp->product->fob_pricing = $pricing ? $pricing->fobINCost : null;
            }
        }

        return response()->json($packingListProducts);
    }


    public function einvoice(Request $request)
    {
        $invoice = Invoice::where('id', $request->invoice_id)->first();
        $invoice->irn           = $request->irn;
        $invoice->ack_no        = $request->ack_no;
        $invoice->ack_date      = $request->ack_date;
        $invoice->lr_rr_no      = $request->lr_rr_no;

        if ($request->hasfile('einvoice_qr')) {
            $file = $request->file('einvoice_qr');
            $extension = $file->getClientOriginalExtension(); // getting image extension
            $filename =   $request->invoice_id . '.' . $extension;

            $directory = public_path('../../uploads/einvoice');
            $imageUrl = $directory . '/' . $filename;
            Image::make($file)->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save($imageUrl);
            $invoice->einvoice_qr    = $filename;
        }

        $invoice->save();
        return redirect('/invoice/')->with('success', 'E-Invoice fields updated successfully!');
    }

    public function email(Request $request)
    {
        $emails = $request->emails;
        $es = explode(',', $emails);
        if (count($es) > 0) {
            foreach ($es as $key => $email) {
                $mailrequest['subject'] = $request->subject;
                $mailrequest['message'] = $request->body;
                $mailrequest['email'] = $email;
                Mail::send(new CustomInvoiceMail($mailrequest));
            }
        } else {
            $mailrequest['subject'] = $request->subject;
            $mailrequest['message'] = $request->body;
            $mailrequest['email'] = $emails;
            Mail::send(new CustomInvoiceMail($mailrequest));
        }
        return redirect('/invoice/')->with('success', 'Mail sent successfully!');
    }

    //Credit notes

    public function credit_notes()
    {
        $credit_notes = CreditNote::orderBy('id', 'desc')->get();
        return view('invoice/credit_notes', ['credit_notes' => $credit_notes]);
    }

    public function credit_note_add()
    {
        $invoice = invoice::orderBy('id', 'desc')->get();
        return view('invoice/credit_note_add', ['invoice' => $invoice]);
    }

      public function credit_note_ref_snapshot_status(Request $request)
    {
        $invoiceId = (int) ($request->invoice_id ?? 0);
        $productId = (int) ($request->product_id ?? 0);

        if ($invoiceId <= 0 || $productId <= 0) {
            return response()->json([
                'ok' => false,
                'code' => 'INVALID_INPUT',
                'message' => 'Invalid invoice/product.',
            ], 422);
        }

        $invoice = invoice::find($invoiceId);
        if (! $invoice) {
            return response()->json([
                'ok' => false,
                'code' => 'INVOICE_NOT_FOUND',
                'message' => 'Invoice not found.',
            ], 404);
        }

        $packing = packinglist::where('buyer_order_no', $invoice->buyerorderno)->first();
        if (! $packing) {
            return response()->json([
                'ok' => false,
                'code' => 'PACKINGLIST_NOT_FOUND',
                'message' => 'Packing list not found for this invoice.',
            ], 404);
        }

        $plp = packingListProduct::where('packinglist_id', $packing->id)
            ->where('product_id', $productId)
            ->first();

        if (! $plp) {
            return response()->json([
                'ok' => false,
                'code' => 'PACKINGLIST_PRODUCT_NOT_FOUND',
                'message' => 'Product not found in packing list.',
            ], 404);
        }

        $batchNos = $plp->batch_no;
        if (is_string($batchNos)) {
            $decoded = json_decode($batchNos, true);
            $batchNos = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($batchNos)) {
            $batchNos = [];
        }
        $batchNos = array_values(array_unique(array_filter(array_map('strval', $batchNos))));

        if (empty($batchNos)) {
            return response()->json([
                'ok' => false,
                'code' => 'BATCH_NO_MISSING',
                'message' => 'Batch number is missing for this product in packing list.',
            ], 422);
        }

        $snap = $plp->ref_source_snapshot ?? [];
        if (is_string($snap)) {
            $decoded = json_decode($snap, true);
            $snap = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($snap)) {
            $snap = [];
        }

        $needsSelection = (count($snap) === 0);

        return response()->json([
            'ok' => true,
            'code' => $needsSelection ? 'REF_SNAPSHOT_MISSING' : 'OK',
            'message' => $needsSelection ? 'Reference snapshot missing; selection required.' : 'OK',
            'packinglist_id' => (int) $packing->id,
            'packinglist_product_id' => (int) $plp->id,
            'batch_nos' => $batchNos,
            'needs_selection' => $needsSelection,
        ]);
    }

    public function credit_note_ref_snapshot_save(Request $request)
    {
        $invoiceId = (int) ($request->invoice_id ?? 0);
        $productId = (int) ($request->product_id ?? 0);
        $lines = $request->lines;

        if ($invoiceId <= 0 || $productId <= 0) {
            return response()->json([
                'ok' => false,
                'code' => 'INVALID_INPUT',
                'message' => 'Invalid invoice/product.',
            ], 422);
        }

        if (! is_array($lines) || count($lines) === 0) {
            return response()->json([
                'ok' => false,
                'code' => 'NO_LINES',
                'message' => 'Please select at least one reference line.',
            ], 422);
        }

        $invoice = invoice::find($invoiceId);
        if (! $invoice) {
            return response()->json([
                'ok' => false,
                'code' => 'INVOICE_NOT_FOUND',
                'message' => 'Invoice not found.',
            ], 404);
        }

        $packing = packinglist::where('buyer_order_no', $invoice->buyerorderno)->first();
        if (! $packing) {
            return response()->json([
                'ok' => false,
                'code' => 'PACKINGLIST_NOT_FOUND',
                'message' => 'Packing list not found for this invoice.',
            ], 404);
        }

        $plp = packingListProduct::where('packinglist_id', $packing->id)
            ->where('product_id', $productId)
            ->first();

        if (! $plp) {
            return response()->json([
                'ok' => false,
                'code' => 'PACKINGLIST_PRODUCT_NOT_FOUND',
                'message' => 'Product not found in packing list.',
            ], 404);
        }

        $batchNos = $plp->batch_no;
        if (is_string($batchNos)) {
            $decoded = json_decode($batchNos, true);
            $batchNos = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($batchNos)) {
            $batchNos = [];
        }
        $batchNos = array_values(array_unique(array_filter(array_map('strval', $batchNos))));
        if (empty($batchNos)) {
            return response()->json([
                'ok' => false,
                'code' => 'BATCH_NO_MISSING',
                'message' => 'Batch number is missing for this product in packing list.',
            ], 422);
        }

        $clean = [];
        foreach ($lines as $row) {
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            $bn = trim((string) ($row['batch_no'] ?? ''));
            if ($urId <= 0 || $bn === '') {
                continue;
            }
            if (! in_array($bn, $batchNos, true)) {
                return response()->json([
                    'ok' => false,
                    'code' => 'INVALID_BATCH',
                    'message' => 'Selected batch does not match packing list batch.',
                ], 422);
            }
            $clean[] = [
                'unique_referencenumber_id' => $urId,
                'batch_no' => $bn,
            ];
        }

        if (count($clean) === 0) {
            return response()->json([
                'ok' => false,
                'code' => 'NO_VALID_LINES',
                'message' => 'No valid reference lines selected.',
            ], 422);
        }

        $plp->ref_source_snapshot = $clean;
        $plp->save();

        return response()->json([
            'ok' => true,
            'code' => 'SAVED',
            'message' => 'Reference snapshot saved.',
            'count' => count($clean),
        ]);
    }

    private function generate_credit_note_number()
    {
        $basePrefix = 'GVD/CN/2627';

        $latest = CreditNote::where('num', 'like', $basePrefix . '/%')
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $lastNum = intval(substr($latest->num, strrpos($latest->num, '/') + 1));
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $basePrefix . '/' . $newNum;
    }

    // public function credit_note_store(Request $request){
    //     $invoice=invoice::where('id', $request['invoice_id'])->first();
    //     $statusremqty = 0;
    //     $cnn = $this->generate_credit_note_number();
    //     $total_quantity = 0;
    //     $total_amount = 0;
    //     $total_tax = 0;
    //     $additional_info = [];
    //     foreach ($request['ps'] as $key=>$ps) {
    //         $total_quantity = $total_quantity + $ps['receiveqty'];

    //         $it = invoiceTable::where('invoice_id',$request['invoice_id'])->where('product_id',$ps['product'])->first();

    //         $additional_info['ps'][$key]['rate'] = $it->rate * $invoice->conrate;
    //         $additional_info['ps'][$key]['amount'] = $additional_info['ps'][$key]['rate'] * $ps['receiveqty'];
    //         $total_amount = $total_amount + $additional_info['ps'][$key]['amount'];
    //         $taxpp = $it->gstamount / $it->quantity;
    //         $tax = $taxpp * $ps['receiveqty'];
    //         $additional_info['ps'][$key]['tax'] = $tax;
    //         $total_tax = $total_tax + $tax;
    //     }
    //     //dd($request);
    //     $q=CreditNote::create([
    //         'num' => $cnn,
    //         'invoice_id'=>$request['invoice_id'],
    //         'total_quantity'=>$total_quantity,
    //         'amount'=>$total_amount,
    //         'total_tax'=>$total_tax,
    //         'total_amount'=>$total_amount + $total_tax
    //     ]);

    //     foreach ($request['ps'] as $k=>$ps) {
    //         CreditNoteProduct::create([
    //              'product_id'=>$ps['product'],
    //              'credit_note_id'=>$q->id,
    //              'rate'=>$additional_info['ps'][$k]['rate'],
    //              'tax'=>$additional_info['ps'][$k]['tax'],
    //              'quantity'=>$ps['receiveqty'],
    //              'amount'=>$additional_info['ps'][$k]['amount']
    //           ]);



    //         $product = product::where('id', $ps['product'])->first();
    // 		$open_balance = $product->quantity;
    //         $product->quantity = $product->quantity + $ps['receiveqty'];
    //         $product->save();


    // 		$s = stockLog::create([
    // 			'product_id' => $ps['product'],
    // 			'voucher_no'=>$cnn,
    // 			'ref_no'=>'Credit Note',
    // 			'quantity'=>$ps['receiveqty'],
    // 			'opening_balance'=>$open_balance,
    // 			'remaining_stock'=>$product->quantity,
    // 			'type'=>1,
    // 			'supplier_inv_no' => '',
    // 			'entity_id' => $q->id
    // 		]);
    //     }

    //     return redirect('/invoice/credit_notes')->with('success', 'Credit Note was added successfully.');
    // }



    public function credit_note_store(Request $request)
    {
        try {
            return \DB::transaction(function () use ($request) {
                $invoice = invoice::find((int) ($request['invoice_id'] ?? 0));
                if (!$invoice) {
                    return redirect()->back()->with('error', 'Invoice not found.');
                }
                $invoiceCompleted = (int) ($invoice->status ?? 0) === 1;

                $buyerId = $invoice->buyer_id ?? ($invoice->buyerid ?? null);
                $buyerName = $buyerId ? buyer::find((int) $buyerId) : null;

                $packingid = packinglist::where('buyer_order_no', $invoice->buyerorderno)->lockForUpdate()->first();
                if (!$packingid) {
                    return redirect()->back()->with('error', 'Packing list not found for this invoice.');
                }

                $psRows = is_array($request['ps'] ?? null) ? $request['ps'] : [];
                if ($psRows === []) {
                    return redirect()->back()->with('error', 'No products selected.');
                }

                // Validate all lines up-front (including mandatory snapshot + return caps).
                $validatedLines = [];
                foreach ($psRows as $idx => $ps) {
                    $productId = (int) ($ps['product'] ?? 0);
                    $qtyReturn = (int) ($ps['receiveqty'] ?? 0);
                    if ($productId <= 0 || $qtyReturn <= 0) {
                        return redirect()->back()->with('error', 'Invalid product/quantity in request.');
                    }

                    $it = invoiceTable::where('invoice_id', (int) $invoice->id)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();
                    if (! $it) {
                        return redirect()->back()->with('error', 'Invoice line not found for product ID: ' . $productId);
                    }
                    $soldQty = (int) ($it->quantity ?? 0) - (int) ($it->remqty ?? 0);
                    if ($soldQty < 0) {
                        $soldQty = 0;
                    }
                    $alreadyCredited = (int) CreditNoteProduct::query()
                        ->join('credit_notes as cn', 'cn.id', '=', 'credit_note_products.credit_note_id')
                        ->where('cn.invoice_id', (int) $invoice->id)
                        ->where('credit_note_products.product_id', $productId)
                        ->sum('credit_note_products.quantity');
                    $maxReturnable = max(0, $soldQty - $alreadyCredited);
                    if ($qtyReturn > $maxReturnable) {
                        return redirect()->back()->with(
                            'error',
                            'Return qty (' . $qtyReturn . ') exceeds max returnable (' . $maxReturnable . ') for product ID: ' . $productId
                        );
                    }

                    $plp = packingListProduct::where('product_id', $productId)
                        ->where('packinglist_id', $packingid->id)
                        ->lockForUpdate()
                        ->first();
                    if (!$plp) {
                        return redirect()->back()->with('error', 'Packing list product not found for Product ID: ' . $productId);
                    }

                    $batchNos = $this->batchNoListFromPlpColumn($plp->batch_no);
                    $batchNos = array_values(array_unique(array_filter(array_map('strval', is_array($batchNos) ? $batchNos : []))));
                    if ($batchNos === []) {
                        return redirect()->back()->with('error', 'Batch number missing for Product ID: ' . $productId);
                    }

                    $snap = $plp->ref_source_snapshot ?? [];
                    if (! is_array($snap) || $snap === []) {
                        return redirect()->back()->with('error', 'Reference snapshot missing for Product ID: ' . $productId . '. Please select ref lines first.');
                    }
                    // Ensure every batch has at least one snapshot row.
                    foreach ($batchNos as $bn) {
                        if ($this->refSnapshotRowsForBatch($snap, (string) $bn) === []) {
                            return redirect()->back()->with('error', 'Reference snapshot incomplete (missing batch ' . $bn . ') for Product ID: ' . $productId);
                        }
                    }

                    $validatedLines[] = [
                        'product_id' => $productId,
                        'qty_return' => $qtyReturn,
                        'invoice_line' => $it,
                        'packing_line' => $plp,
                        'batch_nos' => $batchNos,
                        'snapshot' => $snap,
                        'idx' => $idx,
                    ];
                }

                $cnn = $this->generate_credit_note_number();
                $total_quantity = 0;
                $total_amount = 0;
                $total_tax = 0;
                $additional_info = [];

                foreach ($validatedLines as $key => $line) {
                    $productId = (int) $line['product_id'];
                    $qtyReturn = (int) $line['qty_return'];
                    $it = $line['invoice_line'];
                    $total_quantity += $qtyReturn;

                    $additional_info['ps'][$key]['rate'] = ((float) ($it->rate ?? 0)) * ((float) ($invoice->conrate ?? 1));
                    $additional_info['ps'][$key]['amount'] = $additional_info['ps'][$key]['rate'] * $qtyReturn;
                    $total_amount += $additional_info['ps'][$key]['amount'];

                    $qtyLine = (float) ($it->quantity ?? 0);
                    $taxpp = $qtyLine > 0 ? ((float) ($it->gstamount ?? 0)) / $qtyLine : 0.0;
                    $tax = $taxpp * $qtyReturn;
                    $additional_info['ps'][$key]['tax'] = $tax;
                    $total_tax += $tax;
                }

                $creditNote = CreditNote::create([
                    'num' => $cnn,
                    'invoice_id' => (int) $invoice->id,
                    'total_quantity' => $total_quantity,
                    'amount' => $total_amount,
                    'total_tax' => $total_tax,
                    'total_amount' => $total_amount + $total_tax
                ]);

                foreach ($validatedLines as $k => $line) {
                    $productId = (int) $line['product_id'];
                    $qtyReturn = (int) $line['qty_return'];
                    /** @var packingListProduct $plp */
                    $plp = $line['packing_line'];
                    $batchNos = $line['batch_nos'];
                    $snap = $line['snapshot'];

                    $creditNoteProduct = CreditNoteProduct::create([
                        'product_id' => $productId,
                        'credit_note_id' => $creditNote->id,
                        'rate' => $additional_info['ps'][$k]['rate'],
                        'tax' => $additional_info['ps'][$k]['tax'],
                        'quantity' => $qtyReturn,
                        'amount' => $additional_info['ps'][$k]['amount']
                    ]);

                    $product = product::whereKey($productId)->lockForUpdate()->first();
                    if (! $product) {
                        throw new \RuntimeException('Product not found: '.$productId);
                    }
                    $open_balance = (float) ($product->quantity ?? 0);
                    $product->quantity = (float) ($product->quantity ?? 0) + $qtyReturn;
                    $product->save();

                    // Rule: if invoice completed => add-back LAST batch -> FIRST and LAST snapshot line -> FIRST.
                    // Else (invoice not completed) => add-back FIRST batch -> LAST and FIRST snapshot line -> LAST.
                    $cnRefAudit = [];
                    $remainingToRestore = $qtyReturn;
                    $totalRestored = 0;

                    $batchNosOrdered = array_values(array_filter(array_map('strval', $batchNos)));
                    if ($invoiceCompleted) {
                        $batchNosOrdered = array_reverse($batchNosOrdered);
                    }

                    foreach ($batchNosOrdered as $batchNo) {
                        if ($remainingToRestore <= 0) {
                            break;
                        }

                        $batch = Batch::where('batch_no', (string) $batchNo)->lockForUpdate()->first();
                        if (! $batch) {
                            throw new \RuntimeException('Batch not found: '.$batchNo);
                        }
                        $batchProduct = BatchProduct::where('batch_id', (int) $batch->id)
                            ->where('product_id', (int) $productId)
                            ->lockForUpdate()
                            ->first();
                        if (! $batchProduct) {
                            throw new \RuntimeException('BatchProduct not found for Batch: '.$batchNo);
                        }

                        $beforeRemaining = $this->totalUniqueRefRemainingQtyFromSnapshotBeforeAddBack(
                            (int) $productId,
                            (string) $batchNo,
                            $snap
                        );

                        $restore = $invoiceCompleted
                            ? $this->addBackUniqueRefRemainingQtyFromSnapshotReverseSelectionOrder(
                                (int) $productId,
                                (string) $batchNo,
                                $snap,
                                (int) $remainingToRestore
                            )
                            : $this->addBackUniqueRefRemainingQtyFromSnapshotForwardSelectionOrder(
                                (int) $productId,
                                (string) $batchNo,
                                $snap,
                                (int) $remainingToRestore
                            );
                        $added = (int) ($restore['added'] ?? 0);
                        if ($added > 0) {
                            $batchProduct->quantity = (int) ($batchProduct->quantity ?? 0) + $added;
                            $batchProduct->save();
                            $batch->quantity = (int) ($batch->quantity ?? 0) + $added;
                            $batch->save();

                            stockLog::create([
                                'product_id'         => (int) $productId,
                                'voucher_no'         => $cnn,
                                'ref_no'             => 'Credit Note',
                                'quantity'           => $added,
                                'opening_balance'    => $open_balance,
                                'remaining_stock'    => $product->quantity,
                                'type'               => 1,
                                'supplier_inv_no'    => $cnn,
                                'supplier_name'      => ! empty($batch->supplier_id) ? (optional(supplier::find((int) $batch->supplier_id))->c_name) : null,
                                'buyer_name'         => $buyerName?->c_name,
                                'batch_no'           => (string) $batchNo,
                                'batch_balance'      => $batchProduct->quantity,
                                'entity_id'          => (int) $creditNote->id,
                                'reference_number'   => 'snapshot('.$added.'/'.((int) $beforeRemaining).')',
                                'reference_quantity' => $added,
                            ]);

                            if (is_array($restore['lines'] ?? null)) {
                                foreach ($restore['lines'] as $ln) {
                                    $cnRefAudit[] = $ln;
                                }
                            }

                            $remainingToRestore -= $added;
                            $totalRestored += $added;
                        }
                    }

                    if ($totalRestored !== $qtyReturn) {
                        throw new \RuntimeException(
                            'Unable to restore full reference qty from snapshot for product '.$productId.
                            ' (needed '.$qtyReturn.', restored '.$totalRestored.').'
                        );
                    }

                    $creditNoteProduct->ref_source_snapshot = $cnRefAudit;
                    $creditNoteProduct->save();
                }

                return redirect('/invoice/credit_notes')->with('success', 'Credit Note was added successfully.');
            });
        } catch (\Throwable $e) {
            \Log::error('credit_note_store error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'An error occurred while adding credit note: ' . $e->getMessage());
        }
    }

    /**
     * Credit note add-back (forward) rule:
     * For a given batch, add-back reference qty using FIRST snapshot line -> LAST (selection order),
     * and cap by (original - remaining) for both unique_referencenumber_product and unique_referencenumber.
     *
     * @return array{added:int, lines:array<int,array{batch_no:string,unique_referencenumber_id:int,qty:int}>}
     */
    protected function addBackUniqueRefRemainingQtyFromSnapshotForwardSelectionOrder(
        int $productId,
        string $batchNo,
        array $refSourceSnapshot,
        int $qtyToAddBack
    ): array {
        $productId = (int) $productId;
        $batchNo = trim((string) $batchNo);
        $qtyToAddBack = (int) $qtyToAddBack;
        if ($productId <= 0 || $batchNo === '' || $qtyToAddBack <= 0) {
            return ['added' => 0, 'lines' => []];
        }
        if (! is_array($refSourceSnapshot) || $refSourceSnapshot === []) {
            return ['added' => 0, 'lines' => []];
        }

        $urIds = [];
        foreach ($refSourceSnapshot as $row) {
            if (! is_array($row)) {
                continue;
            }
            $bn = trim((string) ($row['batch_no'] ?? ''));
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($bn === $batchNo && $urId > 0) {
                $urIds[] = $urId;
            }
        }
        if ($urIds === []) {
            return ['added' => 0, 'lines' => []];
        }

        // Preserve snapshot order (first selected -> last selected), removing duplicates while preserving first occurrence.
        $seen = [];
        $ordered = [];
        foreach ($urIds as $u) {
            $u = (int) $u;
            if ($u <= 0) {
                continue;
            }
            if (isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $ordered[] = $u;
        }

        $added = 0;
        $remaining = $qtyToAddBack;
        $auditLines = [];

        foreach ($ordered as $urId) {
            if ($remaining <= 0) {
                break;
            }

            $ur = DB::table('unique_referencenumber')->where('id', (int) $urId)->lockForUpdate()->first();
            if (! $ur) {
                continue;
            }

            $urp = DB::table('unique_referencenumber_product')
                ->where('unique_referencenumber_id', (int) $urId)
                ->where('product_id', (int) $productId)
                ->lockForUpdate()
                ->first();
            if (! $urp) {
                continue;
            }

            $origP = (int) ($urp->originalqty ?? 0);
            $remP  = (int) ($urp->remaining_qty ?? 0);
            $capP  = max(0, $origP - $remP);
            if ($capP <= 0) {
                continue;
            }

            $origH = (int) ($ur->original_qty ?? 0);
            $remH  = (int) ($ur->remqty ?? 0);
            $capH  = max(0, $origH - $remH);

            $toAdd = min($remaining, $capP);
            if ($capH > 0) {
                $toAdd = min($toAdd, $capH);
            }
            if ($toAdd <= 0) {
                continue;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $urp->id)
                ->update([
                    'remaining_qty' => $remP + $toAdd,
                    'updated_at' => now(),
                ]);
            DB::table('unique_referencenumber')
                ->where('id', (int) $urId)
                ->update([
                    'remqty' => $remH + $toAdd,
                    'updated_at' => now(),
                ]);

            $added += $toAdd;
            $remaining -= $toAdd;
            $auditLines[] = [
                'batch_no' => $batchNo,
                'unique_referencenumber_id' => (int) $urId,
                'qty' => (int) $toAdd,
            ];
        }

        return ['added' => $added, 'lines' => $auditLines];
    }

    /**
     * Credit note add-back rule requested:
     * For a given batch, add-back reference qty using LAST snapshot line -> FIRST (reverse selection order),
     * and cap by (original - remaining) for both unique_referencenumber_product and unique_referencenumber.
     *
     * @return array{added:int, lines:array<int,array{batch_no:string,unique_referencenumber_id:int,qty:int}>}
     */
    protected function addBackUniqueRefRemainingQtyFromSnapshotReverseSelectionOrder(
        int $productId,
        string $batchNo,
        array $refSourceSnapshot,
        int $qtyToAddBack
    ): array {
        $productId = (int) $productId;
        $batchNo = trim((string) $batchNo);
        $qtyToAddBack = (int) $qtyToAddBack;
        if ($productId <= 0 || $batchNo === '' || $qtyToAddBack <= 0) {
            return ['added' => 0, 'lines' => []];
        }
        if (! is_array($refSourceSnapshot) || $refSourceSnapshot === []) {
            return ['added' => 0, 'lines' => []];
        }

        $urIds = [];
        foreach ($refSourceSnapshot as $row) {
            if (! is_array($row)) {
                continue;
            }
            $bn = trim((string) ($row['batch_no'] ?? ''));
            $urId = (int) ($row['unique_referencenumber_id'] ?? 0);
            if ($bn === $batchNo && $urId > 0) {
                $urIds[] = $urId;
            }
        }
        if ($urIds === []) {
            return ['added' => 0, 'lines' => []];
        }

        // Reverse snapshot order (last selected -> first selected), but keep duplicates out while preserving reverse order.
        $urIds = array_reverse($urIds);
        $seen = [];
        $ordered = [];
        foreach ($urIds as $u) {
            $u = (int) $u;
            if ($u <= 0) {
                continue;
            }
            if (isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $ordered[] = $u;
        }

        $added = 0;
        $remaining = $qtyToAddBack;
        $auditLines = [];

        foreach ($ordered as $urId) {
            if ($remaining <= 0) {
                break;
            }

            $ur = DB::table('unique_referencenumber')->where('id', (int) $urId)->lockForUpdate()->first();
            if (! $ur) {
                continue;
            }

            $urp = DB::table('unique_referencenumber_product')
                ->where('unique_referencenumber_id', (int) $urId)
                ->where('product_id', (int) $productId)
                ->lockForUpdate()
                ->first();
            if (! $urp) {
                continue;
            }

            $origP = (int) ($urp->originalqty ?? 0);
            $remP  = (int) ($urp->remaining_qty ?? 0);
            $capP  = max(0, $origP - $remP);
            if ($capP <= 0) {
                continue;
            }

            $origH = (int) ($ur->original_qty ?? 0);
            $remH  = (int) ($ur->remqty ?? 0);
            $capH  = max(0, $origH - $remH);

            $toAdd = min($remaining, $capP);
            if ($capH > 0) {
                $toAdd = min($toAdd, $capH);
            }
            if ($toAdd <= 0) {
                continue;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $urp->id)
                ->update([
                    'remaining_qty' => $remP + $toAdd,
                    'updated_at' => now(),
                ]);
            DB::table('unique_referencenumber')
                ->where('id', (int) $urId)
                ->update([
                    'remqty' => $remH + $toAdd,
                    'updated_at' => now(),
                ]);

            $added += $toAdd;
            $remaining -= $toAdd;
            $auditLines[] = [
                'batch_no' => $batchNo,
                'unique_referencenumber_id' => (int) $urId,
                'qty' => (int) $toAdd,
            ];
        }

        return ['added' => $added, 'lines' => $auditLines];
    }


    public function credit_note_modal($id)
    {
        $credit_note = CreditNote::where('id', $id)->first();
        $credit_note_products = CreditNoteProduct::where('credit_note_id', $id)->get();
        $companyDetails = setting::first();
        return view('invoice/credit_note_modal', ['credit_note' => $credit_note, 'credit_note_products' => $credit_note_products, 'companyDetails' => $companyDetails]);
    }

    public function credit_note_delete($id)
    {
        $credit_note = CreditNote::find($id);
        $cn_products = CreditNoteProduct::where('credit_note_id', $id)->get();
        foreach ($cn_products as $key => $cn_product) {
            $product = product::where('id', $cn_product->product_id)->first();
            $open_balance = $product->quantity;
            $product->quantity = $product->quantity - $cn_product->quantity;
            $product->save();
            $s = stockLog::create([
                'product_id' => $cn_product->product_id,
                'voucher_no' => $credit_note->num,
                'ref_no' => 'Credit Note Deleted',
                'quantity' => $cn_product->quantity,
                'opening_balance' => $open_balance,
                'remaining_stock' => $product->quantity,
                'type' => 2,
                'supplier_inv_no' => '',
                'entity_id' => $credit_note->id
            ]);
            $cn_product->delete();
        }

        $credit_note->delete();

        return redirect('/invoice/credit_notes')->with('success', 'Credit Note was deleted successfully.');
    }
    // public function getbatchesbyid($id)
    // {
    //     $product=product::where('id',$id)->first();
    //     $batches = stockLog::where('product_id', $id)
    //         ->whereNotNull('batch_no') // Ensure batch_no is not null
    //         ->whereNotNull('batch_balance') // Ensure batch_balance is not null
    //         ->where('batch_balance', '>', 0) // Ensure batch_balance is greater than 0
    //         ->select('product_id', 'batch_no', 'batch_balance')
    //         ->whereIn('id', function ($query) {
    //             $query->selectRaw('MAX(id)')
    //                 ->from('stock_log')
    //                 ->whereNotNull('batch_no') // Ensure batch_no is not null in subquery
    //                 ->groupBy('product_id', 'batch_no');
    //         })
    //         ->get();

    //     if(count($batches) > 0)
    //     {
    //         $batches = $batches->concat($batches->map(function ($batch) use ($product, $batches) {
    //             return [
    //                 'product_id' => $batch->product_id,
    //                 'batch_no' => 'No Batch', // Modify batch_no to distinguish
    //                 'batch_balance' => @$product->quantity-$batches->sum('batch_balance') , // Example of modifying balance
    //             ];
    //         }));

    //     }
    //     else
    //     {
    //         $batches = collect([
    //             [
    //                 'product_id' => $id,
    //                 'batch_no' => 'No Batch', // Modify batch_no to distinguish
    //                 'batch_balance' => @$product->quantity ?? 0, // Example of modifying balance
    //             ]
    //         ]);
    //     }

    //     return response()->json($batches);
    // }


    public function getbatchesbyid($id)
    {
        $product = product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $batches = BatchProduct::where('product_id', $product->id)->where('quantity', '>', 0)
            ->with('batch')
            ->get();

        if ($batches->count() > 0) {
            $batches = $batches->map(function ($batch) {
                return [
                    'product_id'    => $batch->product_id,
                    'batch_no'      => $batch->batch->batch_no ?? null,
                    'batch_balance' => $batch->quantity ?? 0,
                ];
            });
        }

        return response()->json($batches);
    }

    public function assigningbatchestoold()
    {
        $products     =    product::where('quantity', '>', 0)->select('id', 'quantity')->get();

        $test = [];
        foreach ($products as $product) {
            $total_sum = StockLog::where('product_id', $product->id)
                ->whereNotNull('batch_no') // Ensure batch_no is not null
                // Ensure batch_balance is not null
                ->where('batch_balance', '>', 0) // Ensure batch_balance is greater than 0
                ->select('product_id', 'batch_no', 'batch_balance')
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('stock_log')
                        ->whereNotNull('batch_no') // Ensure batch_no is not null in subquery
                        ->groupBy('product_id', 'batch_no');
                })
                ->get()->sum('batch_balance');
            $reaming = $product->quantity - $total_sum;


            $lat_product = product::where('id', $product->id)->first();

            $lat_product->quantity = $lat_product->quantity - $reaming;
            $lat_product->save();
            $s = stockLog::create([
                'product_id' => $product->id,
                'voucher_no' => 'default',
                'ref_no' => 'Stock Out for giving batch_no to old products .',
                'quantity' => $reaming,
                'opening_balance' => $product->quantity,
                'remaining_stock' => $lat_product->quantity,
                'type' => 2,
                'supplier_inv_no' => "default",

            ]);

            $s = stockLog::create([
                'product_id' => $product->id,
                'voucher_no' => 'default',
                'ref_no' => 'Stock In for giving batch_no to old products .',
                'quantity' => $reaming,
                'opening_balance' => $lat_product->quantity,
                'remaining_stock' => $lat_product->quantity + $reaming,
                'type' => 1,
                'supplier_inv_no' => "default",
                'batch_balance' => $reaming,
                'batch_no' => "P" . $product->id . rand('100000', '999999'),

            ]);
            $lat_product->quantity = $lat_product->quantity + $reaming;
            $lat_product->save();
        }
    }
    public function update_tally_status(Request $request, $id)
    {
        // Check if the ID is provided
        if (empty($id)) {
            return redirect('/invoice')->with('danger', 'Invalid Invoice ID.');
        }

        // Check if the record exists in the database
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return redirect('/invoice')->with('danger', 'Invoice not found.');
        }

        // Update the tally_status
        $invoice->tally_status = $request->tallystatus;
        $invoice->save();

        return redirect('/invoice')->with('success', 'Tally Status updated successfully.');
    }

    /**
     * uk18 from request (modal): [1,2,3] same as Small Hardware bill. Empty => default to all three.
     * On some GET stacks $request->input('uk18') is empty even with uk18[]=1&uk18[]=2 in the URL;
     * re-parse the raw query string so the buyer filter is reliable.
     *
     * @return array<int, int>
     */
    protected function normalizeMonthEndUk18FromRequest(Request $request): array
    {
        $raw = $this->rawUk18ArrayFromRequest($request);
        $out = [];
        foreach ($raw as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $i = (int) $v;
            if (in_array($i, [1, 2, 3], true)) {
                $out[$i] = $i;
            }
        }
        $out = array_values($out);
        if ($out === []) {
            $out = [1, 2, 3];
        }
        sort($out);

        return $out;
    }

    /**
     * Canonical key for buyer scope (sorted unique IDs like "1,3").
     */
    protected function monthEndBuyerKey(array $buyerIds): string
    {
        $ids = array_values(array_unique(array_map('intval', $buyerIds)));
        sort($ids);

        return implode(',', $ids);
    }

    /**
     * @return array<int, int>
     */
    protected function monthEndBuyerIdsFromStored($stored): array
    {
        if (is_array($stored)) {
            $arr = $stored;
        } elseif (is_string($stored)) {
            $trimmed = trim($stored);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $arr = $decoded;
            } else {
                $arr = array_filter(array_map('trim', explode(',', $trimmed)));
            }
        } else {
            return [];
        }

        $out = [];
        foreach ($arr as $v) {
            $i = (int) $v;
            if (in_array($i, [1, 2, 3], true)) {
                $out[$i] = $i;
            }
        }
        $out = array_values($out);
        sort($out);

        return $out;
    }

    protected function monthEndBuyerColumnsAvailable(): bool
    {
        return \Schema::hasColumn('purchase_order_consumables', 'monthend_buyer_ids');
    }

    /**
     * @return array<int, int>
     */
    protected function monthEndAlreadyGeneratedBuyerIds(string $invoiceNo, int $supplierId): array
    {
        if (! $this->monthEndBuyerColumnsAvailable()) {
            return [];
        }

        $rows = purchaseOrderConsumable::query()
            ->where('ref_supplier', $invoiceNo)
            ->where('supplier_id', $supplierId)
            ->where('type', 1)
            ->pluck('monthend_buyer_ids')
            ->all();

        $seen = [];
        foreach ($rows as $raw) {
            foreach ($this->monthEndBuyerIdsFromStored($raw) as $bid) {
                $seen[$bid] = $bid;
            }
        }
        $out = array_values($seen);
        sort($out);

        return $out;
    }

    /**
     * Selected buyers minus already-generated buyers for this invoice+supplier.
     *
     * @return array<int, int>
     */
    protected function monthEndNewBuyerIdsForInvoiceSupplier(string $invoiceNo, int $supplierId, array $selectedBuyerIds): array
    {
        $selected = array_values(array_unique(array_map('intval', $selectedBuyerIds)));
        sort($selected);
        if ($selected === []) {
            return [];
        }
        if (! $this->monthEndBuyerColumnsAvailable()) {
            return $selected;
        }

        $already = $this->monthEndAlreadyGeneratedBuyerIds($invoiceNo, $supplierId);
        if ($already === []) {
            return $selected;
        }

        return array_values(array_diff($selected, $already));
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function rawUk18ArrayFromRequest(Request $request): array
    {
        $raw = $request->input('uk18', null);
        if (is_array($raw) && $raw !== []) {
            return $raw;
        }
        if (is_scalar($raw) && (string) $raw !== '') {
            return [(string) $raw];
        }

        $qUk = $request->query('uk18');
        if (is_array($qUk) && $qUk !== []) {
            return $qUk;
        }
        if (is_scalar($qUk) && (string) $qUk !== '') {
            return [(string) $qUk];
        }

        $qs = $request->getQueryString();
        if (is_string($qs) && $qs !== '') {
            parse_str($qs, $parsed);
            if (isset($parsed['uk18']) && is_array($parsed['uk18'])) {
                return $parsed['uk18'];
            }
            if (isset($parsed['uk18']) && is_scalar($parsed['uk18']) && (string) $parsed['uk18'] !== '') {
                return [(string) $parsed['uk18']];
            }
        }

        return [];
    }

    /**
     * 1=UK-18, 2=Non UK-18, 3=Common. Resolves TINYINT, numeric string, or legacy JSON in DB.
     */
    protected function monthEndpoBuyerCodeFromConsumable(consumable $consumable): ?int
    {
        if (! \Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
            return null;
        }

        $attrs = $consumable->getAttributes();
        $raw = $attrs['monthEndpo_buyer'] ?? null;
        if (($raw === null || $raw === '') && $consumable->offsetExists('monthEndpo_buyer')) {
            $raw = $consumable->getRawOriginal('monthEndpo_buyer');
        }
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            $i = (int) $raw;

            return in_array($i, [1, 2, 3], true) ? $i : null;
        }
        if (is_string($raw) && is_numeric(str_replace(' ', '', $raw)) && ! str_starts_with(trim($raw), '[') && ! str_starts_with(trim($raw), '{')) {
            $i = (int) round((float) trim($raw));

            return in_array($i, [1, 2, 3], true) ? $i : null;
        }
        if (is_string($raw) && (str_starts_with(trim($raw), '[') || is_numeric(trim($raw)))) {
            $d = json_decode($raw, true);
            if (is_int($d) && in_array($d, [1, 2, 3], true)) {
                return $d;
            }
            if (is_array($d) && $d !== []) {
                $first = (int) (array_values($d)[0] ?? 0);
                if (in_array($first, [1, 2, 3], true)) {
                    return $first;
                }
            }
        }

        return null;
    }

    protected function isMonthEndManagedConsumable(consumable $consumable): bool
    {
        $attrs = $consumable->getAttributes();
        $supplierRaw = $attrs['monthEndpo_supplier'] ?? null;
        if ((int) $supplierRaw <= 0) {
            return false;
        }

        return $this->monthEndpoBuyerCodeFromConsumable($consumable) !== null;
    }

    /**
     * Only include lines where the consumable has a set monthEndpo_buyer (1, 2, 3) that is
     * in the modal’s selected Buyer list. Unset/null never matches — set the master in DB.
     * Together with the supplier filter: monthEndpo_supplier = chosen supplier and
     * monthEndpo_buyer ∈ {selected uk18 values} (e.g. 1+3 → 1 and 3 only, not 2).
     */
    protected function consumableMatchesModalUk18(consumable $consumable, array $uk18Set): bool
    {
        $b = $this->monthEndpoBuyerCodeFromConsumable($consumable);
        if ($b === null) {
            return false;
        }

        return in_array($b, $uk18Set, true);
    }

    /**
     * One row per sales-invoice line × Wf, same as preview. Sorted per supplier: box, product, consumable.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function buildMonthEndLinesBySupplier(
        int $invoiceId,
        array $uk18Set,
        ?int $supplierFilterId
    ): array {
        $linesBySupplier = [];
        $invoiceTable = invoiceTable::where('invoice_id', $invoiceId)->orderBy('box', 'ASC')->get();

        foreach ($invoiceTable as $invt) {
            $product = product::find($invt->product_id);
            if (!$product) {
                continue;
            }
            $wfList = WfConsumable::where('product_id', $product->id)->get();
            foreach ($wfList as $wf) {
                $consumable = consumable::find($wf->consumables_id);
                if (!$consumable || !$consumable->monthEndpo_supplier) {
                    continue;
                }
                if (! $this->consumableMatchesModalUk18($consumable, $uk18Set)) {
                    continue;
                }
                $supplierId = (int) $consumable->monthEndpo_supplier;
                if (!empty($supplierFilterId) && (int) $supplierFilterId !== $supplierId) {
                    continue;
                }
                $furnitureQty = (float) ($invt->quantity ?? 0);
                $perFurnitureWf = (float) ($wf->qty ?? 0);
                $qty = $furnitureQty * $perFurnitureWf;
                $rate = (float) ($consumable->rate ?? 0);
                $gstPercent = (float) ($consumable->gst ?? 0);
                $amount = round($qty * $rate, 2);
                $gstAmount = ConsumableMonthEndInvoiceSupport::lineGstAmount($amount, $gstPercent);
                if (!isset($linesBySupplier[$supplierId])) {
                    $linesBySupplier[$supplierId] = [];
                }
                $linesBySupplier[$supplierId][] = [
                    'consumable' => $consumable,
                    'wf'         => $wf,
                    'product'    => $product,
                    'invt'       => $invt,
                    'box'        => (int) ($invt->box ?? 0),
                    'product_code' => (string) ($product->code ?? ''),
                    'product_name' => (string) ($product->name ?? ''),
                    'descriptionBox' => (string) ($invt->descriptionBox ?? ''),
                    'name'       => (string) ($consumable->name ?? ''),
                    'furniture_qty'   => $furnitureQty,
                    'per_furniture_wf'=> $perFurnitureWf,
                    'qty'        => $qty,
                    'rate'       => $rate,
                    'gst_percent'=> $gstPercent,
                    'amount'     => $amount,
                    'gst_amount' => $gstAmount,
                    'total'      => round($amount + $gstAmount, 2),
                    'unit'       => optional(UnitType::find($consumable->unit_type_id))->name ?? '',
                ];
            }
        }
        foreach ($linesBySupplier as $sid => $lines) {
            usort($lines, function ($a, $b) {
                $boxA = (int) ($a['box'] ?? 0);
                $boxB = (int) ($b['box'] ?? 0);
                if ($boxA !== $boxB) {
                    return $boxA <=> $boxB;
                }
                $c = strcmp((string) ($a['product_code'] ?? ''), (string) ($b['product_code'] ?? ''));
                if ($c !== 0) {
                    return $c;
                }

                return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });
            $linesBySupplier[$sid] = $lines;
        }

        return $linesBySupplier;
    }

    /**
     * Poc / challan line description: furniture, optional invoice line note, fur qty, wf.
     */
    protected function monthEndPocLineDescription(array $row): string
    {
        $pc = trim((string) ($row['product_code'] ?? ''));
        $pn = trim((string) ($row['product_name'] ?? ''));
        if ($pc !== '' && $pn !== '') {
            $furn = $pc . ' - ' . $pn;
        } else {
            $furn = $pc !== '' ? $pc : $pn;
        }
        $lines = [];
        if ($furn !== '') {
            $lines[] = 'Furniture: ' . $furn;
        }
        if (trim((string) ($row['descriptionBox'] ?? '')) !== '') {
            $lines[] = (string) $row['descriptionBox'];
        }
        $lines[] = 'Fur. qty: ' . (float) ($row['furniture_qty'] ?? 0);
        $lines[] = 'Wf / fur.: ' . (float) ($row['per_furniture_wf'] ?? 0);

        return implode("\n", $lines);
    }

    protected function monthEndConsumablePoPayterms(): string
    {
        return '30-45 Days';
    }

    protected function monthEndConsumablePoDeliveryRemarks(): string
    {
        return "1. Goods must be delivered to our Factory Address.\n"
            . "2. Invoice and E waybill should be attached at the time of delivery.\n"
            . '3. All rates including freight charges.';
    }

    /** Stored on {@see purchaseOrderConsumable::$remarks} to distinguish hardware month-end POs from regular month-end. */
    protected function hardwareMonthendPoRemarks(): string
    {
        return 'Hardware MonthEnd PO';
    }

    /** Remarks persisted for hardware month-end PO (leading line keeps duplicate detection stable). */
    protected function hardwareMonthendPoStoredRemarks(): string
    {
        return $this->hardwareMonthendPoRemarks() . "\n\n" . $this->monthEndConsumablePoDeliveryRemarks();
    }

    protected function isHardwareMonthendConsumable(consumable $consumable): bool
    {
        if (! \Schema::hasColumn('consumables', 'hardware_monthend_po_product')) {
            return false;
        }

        return (int) ($consumable->hardware_monthend_po_product ?? 0) === 1;
    }

    protected function hardwareMonthendPoExists(string $invoiceNo, int $supplierId): bool
    {
        $prefix = $this->hardwareMonthendPoRemarks();

        return purchaseOrderConsumable::query()
            ->where('ref_supplier', $invoiceNo)
            ->where('supplier_id', $supplierId)
            ->where('type', 1)
            ->where('remarks', 'like', $prefix . '%')
            ->exists();
    }

    /**
     * Like {@see buildMonthEndLinesBySupplier} but only consumables flagged hardware month-end,
     * no buyer (uk18) filter — uses wf_consumable × invoice lines × monthEndpo_supplier.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function buildHardwareMonthendLinesBySupplier(int $invoiceId, ?int $supplierFilterId): array
    {
        $linesBySupplier = [];
        $invoiceTable = invoiceTable::where('invoice_id', $invoiceId)->orderBy('box', 'ASC')->get();

        foreach ($invoiceTable as $invt) {
            $product = product::find($invt->product_id);
            if (! $product) {
                continue;
            }
            $wfList = WfConsumable::where('product_id', $product->id)->get();
            foreach ($wfList as $wf) {
                $consumable = consumable::find($wf->consumables_id);
                if (! $consumable || ! $consumable->monthEndpo_supplier) {
                    continue;
                }
                if (! $this->isHardwareMonthendConsumable($consumable)) {
                    continue;
                }
                $supplierId = (int) $consumable->monthEndpo_supplier;
                if (! empty($supplierFilterId) && (int) $supplierFilterId !== $supplierId) {
                    continue;
                }
                $furnitureQty = (float) ($invt->quantity ?? 0);
                $perFurnitureWf = (float) ($wf->qty ?? 0);
                $qty = $furnitureQty * $perFurnitureWf;
                $rate = (float) ($consumable->rate ?? 0);
                $gstPercent = (float) ($consumable->gst ?? 0);
                $amount = round($qty * $rate, 2);
                $gstAmount = ConsumableMonthEndInvoiceSupport::lineGstAmount($amount, $gstPercent);
                if (! isset($linesBySupplier[$supplierId])) {
                    $linesBySupplier[$supplierId] = [];
                }
                $linesBySupplier[$supplierId][] = [
                    'consumable' => $consumable,
                    'wf' => $wf,
                    'product' => $product,
                    'invt' => $invt,
                    'box' => (int) ($invt->box ?? 0),
                    'product_code' => (string) ($product->code ?? ''),
                    'product_name' => (string) ($product->name ?? ''),
                    'descriptionBox' => (string) ($invt->descriptionBox ?? ''),
                    'name' => (string) ($consumable->name ?? ''),
                    'furniture_qty' => $furnitureQty,
                    'per_furniture_wf' => $perFurnitureWf,
                    'qty' => $qty,
                    'rate' => $rate,
                    'gst_percent' => $gstPercent,
                    'amount' => $amount,
                    'gst_amount' => $gstAmount,
                    'total' => round($amount + $gstAmount, 2),
                    'unit' => optional(UnitType::find($consumable->unit_type_id))->name ?? '',
                ];
            }
        }
        foreach ($linesBySupplier as $sid => $lines) {
            usort($lines, function ($a, $b) {
                $boxA = (int) ($a['box'] ?? 0);
                $boxB = (int) ($b['box'] ?? 0);
                if ($boxA !== $boxB) {
                    return $boxA <=> $boxB;
                }
                $c = strcmp((string) ($a['product_code'] ?? ''), (string) ($b['product_code'] ?? ''));
                if ($c !== 0) {
                    return $c;
                }

                return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });
            $linesBySupplier[$sid] = $lines;
        }

        return $linesBySupplier;
    }

    public function viewmonthendpo()
    {
        $purchaseOrder = purchaseOrder::where('address_option', 100)->orderBy('created_at', 'desc')->get();
        return view('purchaseOrder/monthend', ['purchaseOrder' => $purchaseOrder]);
    }

    public function invoice_canada()
    {
        $invoice = invoice::where('buyerorderno', 'NOT LIKE', "%UK-18%")->orderBy('id', 'DESC')->get();
        $invoice_canada = invoicecanada::get();

        $hardwareSuppliers = hardwareSuppliers::get();
        $contractor     =   contractor::get();
        $upcontractor       =   upholstreyContractor::get();
        $companyDetails = settingcanada::first();
        $smhsuppliers = smhsupplier::get();
        $suppliers = supplier::get();
        /*  echo "<pre>";
        print_r($invoice);die;*/
        return view('invoice/index_canada', ['invoice' => $invoice, 'hardwareSuppliers' => $hardwareSuppliers, 'contractor' => $contractor, 'upcontractor' => $upcontractor, 'companyDetails' => $companyDetails, 'smhsuppliers' => $smhsuppliers, 'suppliers' => $suppliers, 'invoice_canada' => $invoice_canada]);
    }

    public function create_canada(Request $request)
    {
        // return $request->invoice_id;
        $packing = packingList::get();
        $invoice = invoice::find($request->invoice_id);
        $product = product::all();
        $buyer = buyer::where('is_canada', 1)->get();
        $invoiceTable = invoiceTable::where('invoice_id', $request->invoice_id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;

        return view('invoice/create_canada', ['buyer' => $buyer, 'consignee' => $consignee, 'packing' => $packing, 'invoice' => $invoice, 'invoiceTable' => $invoiceTable]);
    }

    public function invoice_california()
    {
        $invoice = invoice::where('buyerorderno', 'NOT LIKE', "%UK-18%")->orderBy('id', 'DESC')->get();
        $invoice_california = invoicecalifornia::get();

        $hardwareSuppliers = hardwareSuppliers::get();
        $contractor     =   contractor::get();
        $upcontractor       =   upholstreyContractor::get();
        $companyDetails = settingcalifornia::first();
        $smhsuppliers = smhsupplier::get();
        $suppliers = supplier::get();
        /*  echo "<pre>";
        print_r($invoice);die;*/
        return view('invoice/index_california', ['invoice' => $invoice, 'hardwareSuppliers' => $hardwareSuppliers, 'contractor' => $contractor, 'upcontractor' => $upcontractor, 'companyDetails' => $companyDetails, 'smhsuppliers' => $smhsuppliers, 'suppliers' => $suppliers, 'invoice_california' => $invoice_california]);
    }

    public function create_california(Request $request)
    {
        // return $request->invoice_id;
        $packing = packingList::get();
        $invoice = invoice::find($request->invoice_id);
        $product = product::all();
        $buyer = buyer::where('is_california', 1)->get();
        $invoiceTable = invoiceTable::where('invoice_id', $request->invoice_id)->orderBy('box', 'ASC')->get();
        $consignee = $buyer;

        return view('invoice/create_california', ['buyer' => $buyer, 'consignee' => $consignee, 'packing' => $packing, 'invoice' => $invoice, 'invoiceTable' => $invoiceTable]);
    }

    public function modal_canada($id)
    {
        $invoice = invoicecanada::find($id)->where('id', $id)->first();
        $companyDetails = settingcanada::first();
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTableCanada::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::where('id', 2)->first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $packing_list = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
        $packing_list_products = packingListProduct::where('packinglist_id', $packing_list->id ?? '')->get();

        return view('invoice/modal_canada', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'packing_list_products' => $packing_list_products]);
    }

    public function modal_california($id)
    {
        $invoice = invoicecalifornia::find($id)->where('id', $id)->first();
        $companyDetails = settingcalifornia::first();
        /* echo "<pre>";
        print_r($companyDetails);die;*/
        $invoiceTable = invoiceTableCalifornia::where('invoice_id', $id)->orderBy('box', 'ASC')->get();
        $certificate = certificate::where('id', 2)->first();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $packing_list = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
        $packing_list_products = packingListProduct::where('packinglist_id', $packing_list->id ?? '')->get();

        return view('invoice/modal_california', ['invoice' => $invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate' => $certificate, 'packing_list_products' => $packing_list_products]);
    }

    public function store_eu(Request $request, $invoice_id)
    {
        // return $request->all();
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $q = invoiceeu::create([
            'consignee_id' => $request['consignee'],
            'buyer_id' => $request['buyer_id'],
            'date' => $request['date'],
            'buyerorderno' => strtoupper($request['buyerorderno']),
            'containerno' => strtoupper($request['containerno']),
            'vehicleno' => $request['vehicleno'],
            'totalbox' => $request['totalbox'],
            'pkgs' => $request['pkgs'],
            'currency' => $request['currency'],
            'fob' => $request['fob'],
            'payterms' => $request['payterms'],
            'shipmentby' => $request['shipmentby'],
            'desgoods' => $request['desgoods'],
            'carriage' => $request['carriage'],
            'receipt' => $request['receipt'],
            'shipment' => $request['shipment'],
            'postloading' => $request['postloading'],
            'discharge' => $request['discharge'],
            'destination' => $request['destination'],
            'totalquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'totalgrosswt' => $request['grosswt'],
            'totalamount' => $request['totalamount'],
            'conrate' => $request['cunrate'],
            'rateamount' => $request['rateamount'],
            'invoiceno' => strtoupper($request['invoiceno']),
            'invoicetype' => $request['invoicetype'],
            'ewaybillno' => strtoupper($request['ewaybillno']),
            'declaration' => $request['declaration'],
            'shipping_charges' => $request['shipping_charges'],
            'packing_charges' => $request['packing_charges'],
            'discount' => $request['discount'],
            'additional_info' => $request['additional_info'],
            'bill_to' => $request['bill_to'],
            'ship_to' => $request['ship_to'],
            'deposit_date' => date('Y-m-d', strtotime($request['deposit_date'])),
            'deposit' => $request['deposit'],
            'delivery_term' => $request['delivery_term'],
            'agent_name' => $request['agent_name'],
            'vat' => $request['vat'],
            'container_size' => $request['container_size']
        ]);

        foreach ($request['inv'] as $inv) {
            invoiceTableEu::create([
                'product_id' => $inv['product'],
                'invoice_id' => $q->id,
                'quantity' => $inv['quantity'],
                'rate' => $inv['rate'],
                'amount' => $inv['amount'],
                'weight' => $inv['weight'],
                'subtotalnetwt' => $inv['subtotalnetwt'],
                'grosswt' => $inv['grosswt'],
                'subtotalgrosswt' => $inv['subTotalGrossWT'],
                'box' => $inv['box'],
                'endbox' => $inv['endBox'],
                'subtotalbox' => $inv['subTotalBox'],
                'qtybox' => $inv['qtybox'],
                'remqty' => $inv['quantity'],
                'descriptionBox' => $inv['descriptionBox']
            ]);
        }

        if ($request['buyerorderno'] != "") {
            $packing = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();
            if (isset($packing->id)) {
                $packing->status = 1;
                $packing->save();
            }
        }
        // invexport::create([
        //                                 'realisation_date' => NULL,
        //                                 'realisation_fc' => NULL,
        //                                 'invoice_id' => $q->id,
        //                                 'rate' => NULL,
        //                                 'bank_reference' => NULL
        //                             ]);

        return redirect('/invoices/invoice-eu')->with('success', 'Invoice was added successfully.');
    }



    public function store_canada(Request $request, $invoice_id)
    {
        // return $request->all();
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $q = invoicecanada::create([
            'consignee_id' => $request['consignee'],
            'buyer_id' => $request['buyer_id'],
            'date' => $request['date'],
            'buyerorderno' => strtoupper($request['buyerorderno']),
            'containerno' => strtoupper($request['containerno']),
            'vehicleno' => $request['vehicleno'],
            'totalbox' => $request['totalbox'],
            'pkgs' => $request['pkgs'],
            'currency' => $request['currency'],
            'fob' => $request['fob'],
            'payterms' => $request['payterms'],
            'shipmentby' => $request['shipmentby'],
            'desgoods' => $request['desgoods'],
            'carriage' => $request['carriage'],
            'receipt' => $request['receipt'],
            'shipment' => $request['shipment'],
            'postloading' => $request['postloading'],
            'discharge' => $request['discharge'],
            'destination' => $request['destination'],
            'totalquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'totalgrosswt' => $request['grosswt'],
            'totalamount' => $request['totalamount'],
            'conrate' => $request['cunrate'],
            'rateamount' => $request['rateamount'],
            'invoiceno' => strtoupper($request['invoiceno']),
            'invoicetype' => $request['invoicetype'],
            'ewaybillno' => strtoupper($request['ewaybillno']),
            'declaration' => $request['declaration'],
            'shipping_charges' => $request['shipping_charges'],
            'packing_charges' => $request['packing_charges'],
            'discount' => $request['discount'],
            'additional_info' => $request['additional_info'],
            'bill_to' => $request['bill_to'],
            'ship_to' => $request['ship_to'],
            'deposit_date' => date('Y-m-d', strtotime($request['deposit_date'])),
            'deposit' => $request['deposit'],
            'delivery_term' => $request['delivery_term'],
            'agent_name' => $request['agent_name'],
            'vat' => $request['vat'],
            'container_size' => $request['container_size']
        ]);

        foreach ($request['inv'] as $inv) {
            invoiceTableCanada::create([
                'product_id' => $inv['product'],
                'invoice_id' => $q->id,
                'quantity' => $inv['quantity'],
                'rate' => $inv['rate'],
                'amount' => $inv['amount'],
                'weight' => $inv['weight'],
                'subtotalnetwt' => $inv['subtotalnetwt'],
                'grosswt' => $inv['grosswt'],
                'subtotalgrosswt' => $inv['subTotalGrossWT'],
                'box' => $inv['box'],
                'endbox' => $inv['endBox'],
                'subtotalbox' => $inv['subTotalBox'],
                'qtybox' => $inv['qtybox'],
                'remqty' => $inv['quantity'],
                'descriptionBox' => $inv['descriptionBox']
            ]);
        }

        if ($request['buyerorderno'] != "") {
            $packing = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();
            if (isset($packing->id)) {
                $packing->status = 1;
                $packing->save();
            }
        }
        // invexport::create([
        //                                 'realisation_date' => NULL,
        //                                 'realisation_fc' => NULL,
        //                                 'invoice_id' => $q->id,
        //                                 'rate' => NULL,
        //                                 'bank_reference' => NULL
        //                             ]);

        return redirect('/invoices/invoice-canada')->with('success', 'Invoice was added successfully.');
    }




    public function updateinvoicecanada(Request $request, $id)
    {
        $invoice = invoicecanada::where('id', $id)->first();
        $invoiceProduct = invoiceTableCanada::where('invoice_id', $id)->get();
        //dd($request);
        //if ($invoice && $invoice->status!=1) {
        if (!isset($request['shipping_charges'])) {
            $request['shipping_charges'] = 0;
        }
        if (!isset($request['packing_charges'])) {
            $request['packing_charges'] = 0;
        }
        if (!isset($request['discount'])) {
            $request['discount'] = 0;
        }
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $invoice->consignee_id = $request['consignee'];
        $invoice->buyer_id = $request['buyer_id'];
        $invoice->date = $request['date'];
        $invoice->buyerorderno = strtoupper($request['buyerorderno']);
        $invoice->containerno = strtoupper($request['containerno']);
        $invoice->vehicleno = $request['vehicleno'];
        $invoice->totalbox = $request['totalbox'];
        $invoice->pkgs = $request['pkgs'];
        $invoice->currency = $request['currency'];
        $invoice->fob = $request['fob'];
        $invoice->payterms = $request['payterms'];
        $invoice->shipmentby = $request['shipmentby'];
        $invoice->desgoods = $request['desgoods'];
        $invoice->carriage = $request['carriage'];
        $invoice->receipt = $request['receipt'];
        $invoice->shipment = $request['shipment'];
        $invoice->postloading = $request['postloading'];
        $invoice->discharge = $request['discharge'];
        $invoice->destination = $request['destination'];
        $invoice->totalquantity = $request['tquantity'];
        $invoice->totalwt = $request['totalwt'];
        $invoice->totalgrosswt = $request['grosswt'];
        $invoice->totalamount = $request['totalamount'];
        $invoice->conrate = $request['cunrate'];
        $invoice->rateamount = $request['rateamount'];
        $invoice->invoiceno = $request['invoiceno'];
        $invoice->invoicetype = $request['invoicetype'];
        $invoice->exportstatus = $request['exportstatus'];
        $invoice->ewaybillno = $request['ewaybillno'];
        $invoice->declaration = $request['declaration'];
        $invoice->shipping_charges = $request['shipping_charges'];
        $invoice->packing_charges = $request['packing_charges'];
        $invoice->discount = $request['discount'];
        $invoice->additional_info = $request['additional_info'];
        $invoice->bill_to = $request['bill_to'];
        $invoice->ship_to = $request['ship_to'];
        $invoice->deposit_date = date('Y-m-d', strtotime($request['deposit_date']));
        $invoice->deposit = $request['deposit'];
        $invoice->delivery_term = $request['delivery_term'];
        $invoice->agent_name = $request['agent_name'];
        $invoice->vat = $request['vat'];
        $invoice->container_size = $request['container_size'];

        foreach ($invoiceProduct as $invoiceProduct) {
            $invoiceProduct->delete();
        }

        foreach ($request['inv'] as $inv) {
            if (!isset($inv['gstamount'])) {
                $inv['gstamount'] = 0;
            }
            if (!isset($inv['consumed'])) {
                $inv['consumed'] = '';
            }
            if ($inv['consumed'] == '') {
                invoiceTableCanada::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            } else {
                invoiceTableCanada::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'] - $inv['consumed'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            }
        }

        $x = invoiceTableCanada::where('invoice_id', $id)->where('remqty', '>', 0)->first();
        if (isset($x)) {
            $invoice->status = 0;
            $invoice->update();
        }


        if ($invoice->save()) {
            return back()->with('success', 'Invoice updated successfully.');
        } else {
            return back()->with('danger', 'Error occurred while saving invoice.');
        }
        //} else {
        //  return redirect('/invoice')->with('danger', 'invoice was not found.');
        //}
    }




    public function delete_canada(Request $require, $id)
    {
        $invoice = invoicecanada::where('id', $id)->first();

        $invoiceTable = invoiceTableCanada::where('invoice_id', $id)->get();

        if ($invoiceTable) {
            foreach ($invoiceTable as $key => $value) {
                $invoiceTable[$key]->delete();
            }
        }

        if ($invoice) {
            if ($invoice->delete()) {
                return redirect('/invoices/invoice-canada')->with('success', 'Invoice deleted successfully.');
            } else {
                return redirect('/invoices/invoice-canada')->with('danger', 'Invoice was not found.');
            }
        }
    }




    public function store_california(Request $request, $invoice_id)
    {
        // return $request->all();
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $q = invoicecalifornia::create([
            'consignee_id' => $request['consignee'],
            'buyer_id' => $request['buyer_id'],
            'date' => $request['date'],
            'buyerorderno' => strtoupper($request['buyerorderno']),
            'containerno' => strtoupper($request['containerno']),
            'vehicleno' => $request['vehicleno'],
            'totalbox' => $request['totalbox'],
            'pkgs' => $request['pkgs'],
            'currency' => $request['currency'],
            'fob' => $request['fob'],
            'payterms' => $request['payterms'],
            'shipmentby' => $request['shipmentby'],
            'desgoods' => $request['desgoods'],
            'carriage' => $request['carriage'],
            'receipt' => $request['receipt'],
            'shipment' => $request['shipment'],
            'postloading' => $request['postloading'],
            'discharge' => $request['discharge'],
            'destination' => $request['destination'],
            'totalquantity' => $request['tquantity'],
            'totalwt' => $request['totalwt'],
            'totalgrosswt' => $request['grosswt'],
            'totalamount' => $request['totalamount'],
            'conrate' => $request['cunrate'],
            'rateamount' => $request['rateamount'],
            'invoiceno' => strtoupper($request['invoiceno']),
            'invoicetype' => $request['invoicetype'],
            'ewaybillno' => strtoupper($request['ewaybillno']),
            'declaration' => $request['declaration'],
            'shipping_charges' => $request['shipping_charges'],
            'packing_charges' => $request['packing_charges'],
            'discount' => $request['discount'],
            'additional_info' => $request['additional_info'],
            'bill_to' => $request['bill_to'],
            'ship_to' => $request['ship_to'],
            'deposit_date' => date('Y-m-d', strtotime($request['deposit_date'])),
            'deposit' => $request['deposit'],
            'delivery_term' => $request['delivery_term'],
            'agent_name' => $request['agent_name'],
            'vat' => $request['vat'],
            'container_size' => $request['container_size']
        ]);

        foreach ($request['inv'] as $inv) {
            invoiceTableCalifornia::create([
                'product_id' => $inv['product'],
                'invoice_id' => $q->id,
                'quantity' => $inv['quantity'],
                'rate' => $inv['rate'],
                'amount' => $inv['amount'],
                'weight' => $inv['weight'],
                'subtotalnetwt' => $inv['subtotalnetwt'],
                'grosswt' => $inv['grosswt'],
                'subtotalgrosswt' => $inv['subTotalGrossWT'],
                'box' => $inv['box'],
                'endbox' => $inv['endBox'],
                'subtotalbox' => $inv['subTotalBox'],
                'qtybox' => $inv['qtybox'],
                'remqty' => $inv['quantity'],
                'descriptionBox' => $inv['descriptionBox']
            ]);
        }

        if ($request['buyerorderno'] != "") {
            $packing = packingList::where('buyer_order_no', strtoupper($request['buyerorderno']))->first();
            if (isset($packing->id)) {
                $packing->status = 1;
                $packing->save();
            }
        }
        // invexport::create([
        //                                 'realisation_date' => NULL,
        //                                 'realisation_fc' => NULL,
        //                                 'invoice_id' => $q->id,
        //                                 'rate' => NULL,
        //                                 'bank_reference' => NULL
        //                             ]);

        return redirect('/invoices/invoice-california')->with('success', 'Invoice was added successfully.');
    }



    public function updateinvoicecalifornia(Request $request, $id)
    {
        $invoice = invoicecalifornia::where('id', $id)->first();
        $invoiceProduct = invoiceTableCalifornia::where('invoice_id', $id)->get();
        //dd($request);
        //if ($invoice && $invoice->status!=1) {
        if (!isset($request['shipping_charges'])) {
            $request['shipping_charges'] = 0;
        }
        if (!isset($request['packing_charges'])) {
            $request['packing_charges'] = 0;
        }
        if (!isset($request['discount'])) {
            $request['discount'] = 0;
        }
        $consignee = buyer::find($request['consignee']);
        $buyer = buyer::find($request['buyer_id']);
        $request['bill_to'] = $buyer->c_name . "\n" . $buyer->address1 . "\n" . $buyer->address2 . "\n" . $buyer->city . "\n" . $buyer->state . " " . $buyer->postcode . "\n" . $buyer->country;
        $request['ship_to'] = $consignee->c_name . "\n" . $consignee->address1 . "\n" . $consignee->address2 . "\n" . $consignee->city . "\n" . $consignee->state . " " . $consignee->postcode . "\n" . $consignee->country;
        $invoice->consignee_id = $request['consignee'];
        $invoice->buyer_id = $request['buyer_id'];
        $invoice->date = $request['date'];
        $invoice->buyerorderno = strtoupper($request['buyerorderno']);
        $invoice->containerno = strtoupper($request['containerno']);
        $invoice->vehicleno = $request['vehicleno'];
        $invoice->totalbox = $request['totalbox'];
        $invoice->pkgs = $request['pkgs'];
        $invoice->currency = $request['currency'];
        $invoice->fob = $request['fob'];
        $invoice->payterms = $request['payterms'];
        $invoice->shipmentby = $request['shipmentby'];
        $invoice->desgoods = $request['desgoods'];
        $invoice->carriage = $request['carriage'];
        $invoice->receipt = $request['receipt'];
        $invoice->shipment = $request['shipment'];
        $invoice->postloading = $request['postloading'];
        $invoice->discharge = $request['discharge'];
        $invoice->destination = $request['destination'];
        $invoice->totalquantity = $request['tquantity'];
        $invoice->totalwt = $request['totalwt'];
        $invoice->totalgrosswt = $request['grosswt'];
        $invoice->totalamount = $request['totalamount'];
        $invoice->conrate = $request['cunrate'];
        $invoice->rateamount = $request['rateamount'];
        $invoice->invoiceno = $request['invoiceno'];
        $invoice->invoicetype = $request['invoicetype'];
        $invoice->exportstatus = $request['exportstatus'];
        $invoice->ewaybillno = $request['ewaybillno'];
        $invoice->declaration = $request['declaration'];
        $invoice->shipping_charges = $request['shipping_charges'];
        $invoice->packing_charges = $request['packing_charges'];
        $invoice->discount = $request['discount'];
        $invoice->additional_info = $request['additional_info'];
        $invoice->bill_to = $request['bill_to'];
        $invoice->ship_to = $request['ship_to'];
        $invoice->deposit_date = date('Y-m-d', strtotime($request['deposit_date']));
        $invoice->deposit = $request['deposit'];
        $invoice->delivery_term = $request['delivery_term'];
        $invoice->agent_name = $request['agent_name'];
        $invoice->vat = $request['vat'];
        $invoice->container_size = $request['container_size'];

        foreach ($invoiceProduct as $invoiceProduct) {
            $invoiceProduct->delete();
        }

        foreach ($request['inv'] as $inv) {
            if (!isset($inv['gstamount'])) {
                $inv['gstamount'] = 0;
            }
            if (!isset($inv['consumed'])) {
                $inv['consumed'] = '';
            }
            if ($inv['consumed'] == '') {
                invoiceTableCanada::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            } else {
                invoiceTableCalifornia::create([
                    'product_id' => $inv['product'],
                    'invoice_id' => $id,
                    'quantity' => $inv['quantity'],
                    'remqty' => $inv['quantity'] - $inv['consumed'],
                    'rate' => $inv['rate'],
                    'amount' => $inv['amount'],
                    'weight' => $inv['weight'],
                    'subtotalnetwt' => $inv['subtotalnetwt'],
                    'grosswt' => $inv['grosswt'],
                    'subtotalgrosswt' => $inv['subTotalGrossWT'],
                    'box' => $inv['box'],
                    'endbox' => $inv['endBox'],
                    'subtotalbox' => $inv['subTotalBox'],
                    'qtybox' => $inv['qtybox'],
                    'descriptionBox' => $inv['descriptionBox']
                ]);
            }
        }

        $x = invoiceTableCalifornia::where('invoice_id', $id)->where('remqty', '>', 0)->first();
        if (isset($x)) {
            $invoice->status = 0;
            $invoice->update();
        }


        if ($invoice->save()) {
            return back()->with('success', 'Invoice updated successfully.');
        } else {
            return back()->with('danger', 'Error occurred while saving invoice.');
        }
        //} else {
        //  return redirect('/invoice')->with('danger', 'invoice was not found.');
        //}
    }


    public function delete_california(Request $require, $id)
    {
        $invoice = invoicecalifornia::where('id', $id)->first();


        $invoiceTable = invoiceTableCalifornia::where('invoice_id', $id)->get();

        if ($invoiceTable) {
            foreach ($invoiceTable as $key => $value) {
                $invoiceTable[$key]->delete();
            }
        }

        if ($invoice) {
            if ($invoice->delete()) {
                return redirect('/invoices/invoice-california')->with('success', 'Invoice deleted successfully.');
            } else {
                return redirect('/invoices/invoice-california')->with('danger', 'Invoice was not found.');
            }
        }
    }

    //     public function discharge()
    //     {
    //         $InvoiceDischarge = InvoiceDischarge::all(); 
    //         return view('invoice/discharge',compact('InvoiceDischarge'));
    //     }
    //     public function dischargeCreate()
    //     {
    //         return view('invoice/dischargeCreate');
    //     }
    //    public function dischargeStore(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|unique:invoice_discharge,name',
    //     ]);

    //     $discharge = InvoiceDischarge::create([
    //         'name' => $validated['name'],
    //     ]);

    //     return redirect()->back()->with('success', 'Invoice discharge added successfully.');
    // }

    // public function dischargeDelete(Request $request ,$id){
    //     $invoiceDis = InvoiceDischarge::find($id);

    //     if (!$invoiceDis) {
    //         return redirect()->back()->with('error', 'Invoice discharge not found.');
    //     }

    //     $invoiceDis->delete();
    //     return redirect()->back()->with('success', 'Invoice discharge deleted successfully.');
    // }



    public function challan()
    {

        $challan = Challan::orderby('created_at', 'desc')->get();
        $product = product::get();

        return view('invoice/challan', ['challan' => $challan, 'product' => $product]);
    }

    public function challanModal($id)
    {
        $challan = Challan::where('id', $id)->with(['supplier', 'purchaseOrder'])->first();
        if (! $challan) {
            abort(404);
        }

        $purchaseOrder = $challan->purchaseOrder;

        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $challanProduct = ChallanProduct::where('challan_id', $id)->with(['product', 'consumable'])->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('invoice/challanModal', ['challan' => $challan, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $challanProduct, 'purchaseOrder' => $purchaseOrder, 'fileMap1' => $fileMap1]);
    }



    /**
     * Generate month-end purchase orders from consumable data. This is the follow-on to the
     * "Small Hardware Bill" workflow: same idea (choose supplier + buyer checkboxes) but line
     * items are driven only by wf_consumable and consumable fields (monthEndpo_supplier,
     * monthEndpo_buyer, rates). The legacy smallhardwares / small_hardware_products tables
     * are not read here; they still power the old Small Hardware bill (POST /invoice/smallhardwarebill).
     */
    public function monthEndPOcreate(Request $request)
    {
        $supplierFilterId = $request->input('supplier_id');
        $singleInvoiceId  = $request->input('invoice_id');
        $uk18Set = $this->normalizeMonthEndUk18FromRequest($request);

        $invoice_ids_csv = trim((string) $singleInvoiceId ?? '', ',');
        if ($invoice_ids_csv === '') {
            return redirect()->back()->with('error', 'No invoice selected.');
        }

        $invoiceIds = collect(explode(',', $invoice_ids_csv))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $createdInvoices = [];
        $skippedInvoices = [];

        try {
            DB::transaction(function () use ($invoiceIds, $uk18Set, $supplierFilterId, &$createdInvoices, &$skippedInvoices) {
                $companyDetails = setting::query()->lockForUpdate()->first();
                if (! $companyDetails) {
                    throw new \RuntimeException('Settings row not found for month-end PO number tracking.');
                }

                $hasMonthEndCounter = \Schema::hasColumn('settings', 'monthend_po_no');
                $poCounter = $hasMonthEndCounter
                    ? (int) ($companyDetails->monthend_po_no ?? 1)
                    : (int) (purchaseOrderConsumable::where('pono', 'like', 'M/%')->count() + 1);
                if ($poCounter < 1) {
                    $poCounter = 1;
                }
                $startCounter = $poCounter;

                foreach ($invoiceIds as $id) {
                    $invoice = invoice::find($id);
                    if (! $invoice) {
                        continue;
                    }

                    $baseInvoiceDate = optional($invoice)->date ?? now();
                    $po_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(5)->format('Y-m-d');
                    $challan_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(4)->format('Y-m-d');

                    $filter = $supplierFilterId !== null && $supplierFilterId !== '' ? (int) $supplierFilterId : null;
                    $candidateLinesBySupplier = $this->buildMonthEndLinesBySupplier((int) $id, $uk18Set, $filter);

                    if (empty($candidateLinesBySupplier)) {
                        $skippedInvoices[] = $invoice->invoiceno;

                        continue;
                    }
                    $generatedForInvoice = false;
                    foreach (array_keys($candidateLinesBySupplier) as $supplierId) {
                        $supplierId = (int) $supplierId;
                        $newBuyerIds = $this->monthEndNewBuyerIdsForInvoiceSupplier((string) $invoice->invoiceno, $supplierId, $uk18Set);
                        if ($newBuyerIds === []) {
                            continue;
                        }
                        $supplierScoped = $this->buildMonthEndLinesBySupplier((int) $id, $newBuyerIds, $supplierId);
                        $lines = $supplierScoped[$supplierId] ?? [];
                        if ($lines === []) {
                            continue;
                        }

                        $monthendPono = 'M/' . $poCounter++;
                        $purchaseOrder = purchaseOrderConsumable::create(array_merge([
                            'pono'           => $monthendPono,
                            'supplier_id'    => $supplierId,
                            'podate'         => $po_date,
                            'del_date'       => $challan_date,
                            'ref_supplier'   => $invoice->invoiceno,
                            'buyer_orderno'  => $invoice->buyerorderno,
                            'payterms'       => $this->monthEndConsumablePoPayterms(),
                            'remarks'        => $this->monthEndConsumablePoDeliveryRemarks(),
                            'address_option' => 100,
                            'type'           => 1,
                            'monthend_buyer_ids' => $this->monthEndBuyerColumnsAvailable() ? json_encode($newBuyerIds) : null,
                            'monthend_buyer_key' => (\Schema::hasColumn('purchase_order_consumables', 'monthend_buyer_key') ? $this->monthEndBuyerKey($newBuyerIds) : null),
                        ], SendToSupplierPo::createAttributes(100, $monthendPono, 'purchase_order_consumables')));

                        $challan = Challan::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'challan_number'    => $invoice->invoiceno,
                            'supplier_id'       => $supplierId,
                            'vehicle_no'        => null,
                            'challan_date'      => $challan_date,
                        ]);

                        $subTotal = 0.0;
                        $totalgst = 0.0;
                        $tamountPre = 0.0;
                        $tquantity = 0.0;
                        $secondsOffset = 0;

                        foreach ($lines as $row) {
                            $qty = (float) $row['qty'];
                            $consumable = $row['consumable'];
                            $wf = $row['wf'];
                            $consumables_id = (int) $consumable->id;
                            $rate = (float) $row['rate'];
                            $gst = (float) $row['gst_percent'];
                            $amount = round((float) $row['amount'], 2);
                            $gstAmount = ConsumableMonthEndInvoiceSupport::lineGstAmount($amount, $gst);

                            pocTable::create([
                                'consumable_id' => $consumables_id,
                                'poid'          => $purchaseOrder->id,
                                'quantity'      => $qty,
                                'unit'          => $row['unit'] ?? '',
                                'remqty'        => $qty,
                                'gstslab'       => $gst,
                                'gstamount'     => $gstAmount,
                                'rate'          => $rate,
                                'amount'        => $amount,
                                'description'   => $this->monthEndPocLineDescription($row),
                            ]);

                            ChallanProduct::create([
                                'consumable_id'     => $consumables_id,
                                'product_id'        => $wf->product_id,
                                'challan_id'        => $challan->id,
                                'purchase_order_id' => $purchaseOrder->id,
                                'rate'              => $rate,
                                'gstslab'           => $gst,
                                'amount'            => $amount,
                                'total'             => round($amount + $gstAmount, 2),
                                'gst'               => $gstAmount,
                                'quantity'          => $qty,
                            ]);

                            $subTotal += $amount;
                            $totalgst += $gstAmount;
                            $tamountPre += $amount;
                            $tquantity += $qty;

                            $nowTime = \Carbon\Carbon::now()->format('H:i:s');
                            $logTimestamp = \Carbon\Carbon::parse($challan_date . ' ' . $nowTime)->addSeconds($secondsOffset);
                            $secondsOffset += 5;

                            $consumableFresh = consumable::where('id', $consumables_id)->first();
                            if (! $consumableFresh) {
                                throw new \RuntimeException("Consumable {$consumables_id} not found for stock log.");
                            }

                            $previousLog = stockLogConsumable::where('consumable_id', $consumables_id)
                                ->where('created_at', '<=', $logTimestamp)
                                ->orderBy('created_at', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            $open_balance = $previousLog ? (float) $previousLog->remaining_stock : (float) $consumableFresh->quantity;
                            $remaining_stock = $open_balance + $qty;

                            $inLogData = [
                                'consumable_id'   => $consumables_id,
                                'product_id'      => $wf->product_id,
                                'voucher_no'      => $invoice->invoiceno,
                                'quantity'        => $qty,
                                'opening_balance' => $open_balance,
                                'remaining_stock' => $remaining_stock,
                                'type'            => 1,
                                'supplier_name'   => $purchaseOrder->supplier->c_name ?? 'N/A',
                                'supplier_id'     => (int) ($purchaseOrder->supplier_id ?? 0) ?: null,
                                'batch_balance'   => $qty,
                                'remark'          => 'MonthEnd PO Stock In',
                                'created_at'      => $logTimestamp,
                                'updated_at'      => $logTimestamp,
                            ];
                            if (\Schema::hasColumn('stock_log_consumable', 'invoice_id')) {
                                $inLogData['invoice_id'] = (int) $invoice->id;
                            }
                            stockLogConsumable::create($inLogData);

                            $consumableFresh->increment('quantity', $qty);

                            $currentStock = $remaining_stock;
                            $futureLogs = stockLogConsumable::where('created_at', '>', $logTimestamp)
                                ->where('consumable_id', $consumables_id)
                                ->orderBy('created_at', 'asc')
                                ->orderBy('id', 'asc')
                                ->get();
                            foreach ($futureLogs as $stockLogCon) {
                                $stockLogCon->opening_balance = $currentStock;
                                $stockLogCon->remaining_stock = $stockLogCon->type == 1
                                    ? $stockLogCon->opening_balance + $stockLogCon->quantity
                                    : $stockLogCon->opening_balance - $stockLogCon->quantity;
                                $currentStock = $stockLogCon->remaining_stock;
                                $stockLogCon->save();
                            }

                            $outTimestamp = \Carbon\Carbon::parse($baseInvoiceDate . ' ' . $nowTime)->addSeconds($secondsOffset);
                            $secondsOffset += 5;

                            $outPreviousLog = stockLogConsumable::where('consumable_id', $consumables_id)
                                ->where('created_at', '<=', $outTimestamp)
                                ->orderBy('created_at', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            $outOpenBalance = $outPreviousLog ? (float) $outPreviousLog->remaining_stock : (float) $consumableFresh->quantity;
                            $outQty = min($qty, max(0.0, $outOpenBalance));
                            if ($outQty > 0) {
                                $outRemainingStock = $outOpenBalance - $outQty;
                                $outLogData = [
                                    'consumable_id'   => $consumables_id,
                                    'product_id'      => $wf->product_id,
                                    'voucher_no'      => $invoice->invoiceno,
                                    'ref_no'          => 'MonthEnd Invoice Stock Out',
                                    'quantity'        => $outQty,
                                    'opening_balance' => $outOpenBalance,
                                    'remaining_stock' => $outRemainingStock,
                                    'type'            => 2,
                                    'supplier_name'   => $purchaseOrder->supplier->c_name ?? 'N/A',
                                    'supplier_id'     => (int) ($purchaseOrder->supplier_id ?? 0) ?: null,
                                    'remark'          => 'MonthEnd PO Stock Out',
                                    'created_at'      => $outTimestamp,
                                    'updated_at'      => $outTimestamp,
                                ];
                                if (\Schema::hasColumn('stock_log_consumable', 'invoice_id')) {
                                    $outLogData['invoice_id'] = (int) $invoice->id;
                                }
                                stockLogConsumable::create($outLogData);

                                $consumableFresh->decrement('quantity', $outQty);

                                $currentStock = $outRemainingStock;
                                $futureLogs = stockLogConsumable::where('created_at', '>', $outTimestamp)
                                    ->where('consumable_id', $consumables_id)
                                    ->orderBy('created_at', 'asc')
                                    ->orderBy('id', 'asc')
                                    ->get();
                                foreach ($futureLogs as $stockLogCon) {
                                    $stockLogCon->opening_balance = $currentStock;
                                    $stockLogCon->remaining_stock = $stockLogCon->type == 1
                                        ? $stockLogCon->opening_balance + $stockLogCon->quantity
                                        : $stockLogCon->opening_balance - $stockLogCon->quantity;
                                    $currentStock = $stockLogCon->remaining_stock;
                                    $stockLogCon->save();
                                }
                            }
                        }

                        $purchaseOrder->update([
                            'subTotal'  => $subTotal,
                            'tquantity' => $tquantity,
                            'tamount'   => $tamountPre + $totalgst,
                            'remqty'    => $tquantity,
                            'tgst'      => $totalgst,
                            'status'    => 0,
                        ]);

                        $challan->update([
                            'subTotal'  => $subTotal,
                            'tquantity' => $tquantity,
                            'tamount'   => $tamountPre + $totalgst,
                            'tgst'      => $totalgst,
                        ]);
                        $generatedForInvoice = true;
                    }
                    if ($generatedForInvoice) {
                        $createdInvoices[] = $invoice->invoiceno;
                    } else {
                        $skippedInvoices[] = $invoice->invoiceno;
                    }
                }

                if ($hasMonthEndCounter && $poCounter > $startCounter) {
                    $companyDetails->monthend_po_no = $poCounter;
                    $companyDetails->save();
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect('/invoice')->with('error', 'Month-End PO was not created: ' . $e->getMessage());
        }

        return redirect('/invoice')->with(
            'success',
            'Month-End PO created successfully.' .
                (count($skippedInvoices) > 0 ? ' ⚠️ No mapping for: ' . implode(', ', $skippedInvoices) : '')
        );
    }


    /**
     * Preview for {@see monthEndPOcreate}: consumable / Wf only — not small_hardware_products.
     */
    public function monthEndPO(Request $request)
    {
        $supplierFilterId = $request->input('supplier_id'); // optional filter
        $singleInvoiceId  = $request->input('invoice_id');  // CSV or single id
        $uk18Set = $this->normalizeMonthEndUk18FromRequest($request);
        $monthEndUk18 = $uk18Set; // for preview hiddens

        // Normalize invoice IDs
        $invoice_ids_csv = trim((string) $singleInvoiceId ?? '', ',');
        if ($invoice_ids_csv === '') {
            return redirect()->back()->with('error', 'No invoice selected.');
        }

        $invoice_ids = collect(explode(',', $invoice_ids_csv))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        $companyDetailsForCounter = setting::first();
        $hasMonthEndCounter = \Schema::hasColumn('settings', 'monthend_po_no');
        $poCounter = $hasMonthEndCounter
            ? (int) ($companyDetailsForCounter->monthend_po_no ?? 1)
            : (int) (purchaseOrderConsumable::where('pono', 'like', 'M/%')->count() + 1);
        if ($poCounter < 1) {
            $poCounter = 1;
        }
        $previewData = [];
        $skippedInvoices = [];

        foreach ($invoice_ids as $id) {
            $invoice = invoice::find($id);
            if (!$invoice) continue;

            $baseInvoiceDate = optional($invoice)->date ?? now();
            $po_date      = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(5)->format('Y-m-d');
            $challan_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(4)->format('Y-m-d');
            $candidateLinesBySupplier = $this->buildMonthEndLinesBySupplier(
                (int) $id,
                $uk18Set,
                !empty($supplierFilterId) ? (int) $supplierFilterId : null
            );
            $supplierGroups = [];
            foreach (array_keys($candidateLinesBySupplier) as $supplierId) {
                $supplierId = (int) $supplierId;
                $newBuyerIds = $this->monthEndNewBuyerIdsForInvoiceSupplier((string) $invoice->invoiceno, $supplierId, $uk18Set);
                if ($newBuyerIds === []) {
                    continue;
                }
                $supplierScoped = $this->buildMonthEndLinesBySupplier((int) $id, $newBuyerIds, $supplierId);
                $lines = $supplierScoped[$supplierId] ?? [];
                if ($lines === []) {
                    continue;
                }
                if (!isset($supplierGroups[$supplierId])) {
                    $supplierGroups[$supplierId]['po'] = [
                        'pono'           => 'M/' . $poCounter++,
                        'supplier_id'    => $supplierId,
                        'podate'         => date('Y-m-d', strtotime($po_date ?? now())),
                        'del_date'       => date('Y-m-d', strtotime($challan_date ?? now())),
                        'ref_supplier'   => $invoice->invoiceno,
                        'buyer_orderno'  => $invoice->buyerorderno,
                        'payterms'       => $this->monthEndConsumablePoPayterms(),
                        'remarks'        => $this->monthEndConsumablePoDeliveryRemarks(),
                        'address_option' => 100,
                        'type'           => 1,
                        'monthend_buyer_ids' => $newBuyerIds,
                        'monthend_buyer_key' => $this->monthEndBuyerKey($newBuyerIds),
                    ];
                    $supplierGroups[$supplierId]['items'] = [];
                }
                foreach ($lines as $row) {
                    $c = $row['consumable'];
                    $supplierGroups[$supplierId]['items'][] = [
                        'consumable_id'   => $c->id,
                        'name'            => $c->name,
                        'product_code'    => $row['product_code'],
                        'product_name'    => $row['product_name'],
                        'descriptionBox'  => $row['descriptionBox'],
                        'box'             => $row['box'],
                        'furniture_qty'   => $row['furniture_qty'],
                        'per_furniture_wf'=> $row['per_furniture_wf'],
                        'qty'             => $row['qty'],
                        'unit'            => $row['unit'],
                        'rate'            => $row['rate'],
                        'gst_percent'     => $row['gst_percent'],
                        'gst_amount'      => $row['gst_amount'],
                        'amount'          => $row['amount'],
                        'total'           => $row['total'],
                    ];
                }
            }

            if (!empty($supplierGroups)) {
                $previewData[$invoice->invoiceno] = [
                    'invoice'        => $invoice,
                    'supplierGroups' => $supplierGroups,
                ];
            } else {
                $skippedInvoices[] = $invoice->invoiceno;
            }
        }

        $companyDetails = setting::first();
        $fileMap1 = [];
        try {
            $files1 = Storage::disk('s3')->files('stock');
            foreach ($files1 as $file) {
                $filename = basename($file);
                $fileMap1[$filename] = Storage::disk('s3')->url($file);
            }
        } catch (\Throwable $e) {
            $fileMap1 = [];
        }

        return view('invoice.preview', compact('previewData', 'skippedInvoices', 'monthEndUk18', 'companyDetails', 'fileMap1'));
    }

    /**
     * Preview for hardware-flagged consumables only — no buyer selection.
     */
    public function hardwareMonthEndPO(Request $request)
    {
        $supplierFilterId = $request->input('supplier_id');
        $singleInvoiceId = $request->input('invoice_id');
        $invoice_ids_csv = trim((string) $singleInvoiceId ?? '', ',');
        if ($invoice_ids_csv === '') {
            return redirect()->back()->with('error', 'No invoice selected.');
        }

        $invoice_ids = collect(explode(',', $invoice_ids_csv))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        $companyDetailsForCounter = setting::first();
        $hasMonthEndCounter = \Schema::hasColumn('settings', 'monthend_po_no');
        $poCounter = $hasMonthEndCounter
            ? (int) ($companyDetailsForCounter->monthend_po_no ?? 1)
            : (int) (purchaseOrderConsumable::where('pono', 'like', 'M/%')->count() + 1);
        if ($poCounter < 1) {
            $poCounter = 1;
        }
        $previewData = [];
        $skippedInvoices = [];

        foreach ($invoice_ids as $id) {
            $invoice = invoice::find($id);
            if (! $invoice) {
                continue;
            }

            $baseInvoiceDate = optional($invoice)->date ?? now();
            $po_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(5)->format('Y-m-d');
            $challan_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(4)->format('Y-m-d');
            $candidateLinesBySupplier = $this->buildHardwareMonthendLinesBySupplier(
                (int) $id,
                ! empty($supplierFilterId) ? (int) $supplierFilterId : null
            );
            $supplierGroups = [];
            foreach (array_keys($candidateLinesBySupplier) as $supplierId) {
                $supplierId = (int) $supplierId;
                if ($this->hardwareMonthendPoExists((string) $invoice->invoiceno, $supplierId)) {
                    continue;
                }
                $lines = $candidateLinesBySupplier[$supplierId] ?? [];
                if ($lines === []) {
                    continue;
                }
                if (! isset($supplierGroups[$supplierId])) {
                    $supplierGroups[$supplierId]['po'] = [
                        'pono' => 'M/' . $poCounter++,
                        'supplier_id' => $supplierId,
                        'podate' => date('Y-m-d', strtotime($po_date ?? now())),
                        'del_date' => date('Y-m-d', strtotime($challan_date ?? now())),
                        'ref_supplier' => $invoice->invoiceno,
                        'buyer_orderno' => $invoice->buyerorderno,
                        'payterms' => $this->monthEndConsumablePoPayterms(),
                        'remarks' => $this->hardwareMonthendPoStoredRemarks(),
                        'address_option' => 100,
                        'type' => 1,
                    ];
                    $supplierGroups[$supplierId]['items'] = [];
                }
                foreach ($lines as $row) {
                    $c = $row['consumable'];
                    $supplierGroups[$supplierId]['items'][] = [
                        'consumable_id' => $c->id,
                        'name' => $c->name,
                        'product_code' => $row['product_code'],
                        'product_name' => $row['product_name'],
                        'descriptionBox' => $row['descriptionBox'],
                        'box' => $row['box'],
                        'furniture_qty' => $row['furniture_qty'],
                        'per_furniture_wf' => $row['per_furniture_wf'],
                        'qty' => $row['qty'],
                        'unit' => $row['unit'],
                        'rate' => $row['rate'],
                        'gst_percent' => $row['gst_percent'],
                        'gst_amount' => $row['gst_amount'],
                        'amount' => $row['amount'],
                        'total' => $row['total'],
                    ];
                }
            }

            if (! empty($supplierGroups)) {
                $previewData[$invoice->invoiceno] = [
                    'invoice' => $invoice,
                    'supplierGroups' => $supplierGroups,
                ];
            } else {
                $skippedInvoices[] = $invoice->invoiceno;
            }
        }

        $companyDetails = setting::first();
        $fileMap1 = [];
        try {
            $files1 = Storage::disk('s3')->files('stock');
            foreach ($files1 as $file) {
                $filename = basename($file);
                $fileMap1[$filename] = Storage::disk('s3')->url($file);
            }
        } catch (\Throwable $e) {
            $fileMap1 = [];
        }

        return view('invoice.preview_hardware_monthend', compact('previewData', 'skippedInvoices', 'companyDetails', 'fileMap1'));
    }

    /**
     * Persist hardware month-end POs (same tables as {@see monthEndPOcreate}, no buyer logic).
     */
    public function hardwareMonthEndPOcreate(Request $request)
    {
        $supplierFilterId = $request->input('supplier_id');
        $singleInvoiceId = $request->input('invoice_id');
        $invoice_ids_csv = trim((string) $singleInvoiceId ?? '', ',');
        if ($invoice_ids_csv === '') {
            return redirect()->back()->with('error', 'No invoice selected.');
        }

        $invoiceIds = collect(explode(',', $invoice_ids_csv))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $createdInvoices = [];
        $skippedInvoices = [];

        try {
            DB::transaction(function () use ($invoiceIds, $supplierFilterId, &$createdInvoices, &$skippedInvoices) {
                $companyDetails = setting::query()->lockForUpdate()->first();
                if (! $companyDetails) {
                    throw new \RuntimeException('Settings row not found for month-end PO number tracking.');
                }

                $hasMonthEndCounter = \Schema::hasColumn('settings', 'monthend_po_no');
                $poCounter = $hasMonthEndCounter
                    ? (int) ($companyDetails->monthend_po_no ?? 1)
                    : (int) (purchaseOrderConsumable::where('pono', 'like', 'M/%')->count() + 1);
                if ($poCounter < 1) {
                    $poCounter = 1;
                }
                $startCounter = $poCounter;

                foreach ($invoiceIds as $id) {
                    $invoice = invoice::find($id);
                    if (! $invoice) {
                        continue;
                    }

                    $baseInvoiceDate = optional($invoice)->date ?? now();
                    $po_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(5)->format('Y-m-d');
                    $challan_date = \Carbon\Carbon::parse($baseInvoiceDate)->subDays(4)->format('Y-m-d');

                    $filter = $supplierFilterId !== null && $supplierFilterId !== '' ? (int) $supplierFilterId : null;
                    $candidateLinesBySupplier = $this->buildHardwareMonthendLinesBySupplier((int) $id, $filter);

                    if (empty($candidateLinesBySupplier)) {
                        $skippedInvoices[] = $invoice->invoiceno;

                        continue;
                    }
                    $generatedForInvoice = false;
                    foreach (array_keys($candidateLinesBySupplier) as $supplierId) {
                        $supplierId = (int) $supplierId;
                        if ($this->hardwareMonthendPoExists((string) $invoice->invoiceno, $supplierId)) {
                            continue;
                        }
                        $lines = $candidateLinesBySupplier[$supplierId] ?? [];
                        if ($lines === []) {
                            continue;
                        }

                        $createPo = [
                            'pono' => 'M/' . $poCounter++,
                            'supplier_id' => $supplierId,
                            'podate' => $po_date,
                            'del_date' => $challan_date,
                            'ref_supplier' => $invoice->invoiceno,
                            'buyer_orderno' => $invoice->buyerorderno,
                            'payterms' => $this->monthEndConsumablePoPayterms(),
                            'remarks' => $this->hardwareMonthendPoStoredRemarks(),
                            'address_option' => 100,
                            'type' => 1,
                        ];
                        if ($this->monthEndBuyerColumnsAvailable()) {
                            $createPo['monthend_buyer_ids'] = null;
                        }
                        if (\Schema::hasColumn('purchase_order_consumables', 'monthend_buyer_key')) {
                            $createPo['monthend_buyer_key'] = null;
                        }
                        $purchaseOrder = purchaseOrderConsumable::create(array_merge(
                            $createPo,
                            SendToSupplierPo::createAttributes(100, $createPo['pono'], 'purchase_order_consumables')
                        ));

                        $challan = Challan::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'challan_number' => $invoice->invoiceno,
                            'supplier_id' => $supplierId,
                            'vehicle_no' => null,
                            'challan_date' => $challan_date,
                        ]);

                        $subTotal = 0.0;
                        $totalgst = 0.0;
                        $tamountPre = 0.0;
                        $tquantity = 0.0;
                        $secondsOffset = 0;

                        foreach ($lines as $row) {
                            $qty = (float) $row['qty'];
                            $consumable = $row['consumable'];
                            $wf = $row['wf'];
                            $consumables_id = (int) $consumable->id;
                            $rate = (float) $row['rate'];
                            $gst = (float) $row['gst_percent'];
                            $amount = round((float) $row['amount'], 2);
                            $gstAmount = ConsumableMonthEndInvoiceSupport::lineGstAmount($amount, $gst);

                            pocTable::create([
                                'consumable_id' => $consumables_id,
                                'poid' => $purchaseOrder->id,
                                'quantity' => $qty,
                                'unit' => $row['unit'] ?? '',
                                'remqty' => $qty,
                                'gstslab' => $gst,
                                'gstamount' => $gstAmount,
                                'rate' => $rate,
                                'amount' => $amount,
                                'description' => $this->monthEndPocLineDescription($row),
                            ]);

                            ChallanProduct::create([
                                'consumable_id' => $consumables_id,
                                'product_id' => $wf->product_id,
                                'challan_id' => $challan->id,
                                'purchase_order_id' => $purchaseOrder->id,
                                'rate' => $rate,
                                'gstslab' => $gst,
                                'amount' => $amount,
                                'total' => round($amount + $gstAmount, 2),
                                'gst' => $gstAmount,
                                'quantity' => $qty,
                            ]);

                            $subTotal += $amount;
                            $totalgst += $gstAmount;
                            $tamountPre += $amount;
                            $tquantity += $qty;

                            $nowTime = \Carbon\Carbon::now()->format('H:i:s');
                            $logTimestamp = \Carbon\Carbon::parse($challan_date . ' ' . $nowTime)->addSeconds($secondsOffset);
                            $secondsOffset += 5;

                            $consumableFresh = consumable::where('id', $consumables_id)->first();
                            if (! $consumableFresh) {
                                throw new \RuntimeException("Consumable {$consumables_id} not found for stock log.");
                            }

                            $previousLog = stockLogConsumable::where('consumable_id', $consumables_id)
                                ->where('created_at', '<=', $logTimestamp)
                                ->orderBy('created_at', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            $open_balance = $previousLog ? (float) $previousLog->remaining_stock : (float) $consumableFresh->quantity;
                            $remaining_stock = $open_balance + $qty;

                            $inLogData = [
                                'consumable_id' => $consumables_id,
                                'product_id' => $wf->product_id,
                                'voucher_no' => $invoice->invoiceno,
                                'quantity' => $qty,
                                'opening_balance' => $open_balance,
                                'remaining_stock' => $remaining_stock,
                                'type' => 1,
                                'supplier_name' => $purchaseOrder->supplier->c_name ?? 'N/A',
                                'supplier_id' => (int) ($purchaseOrder->supplier_id ?? 0) ?: null,
                                'batch_balance' => $qty,
                                'remark' => 'MonthEnd PO Stock In',
                                'created_at' => $logTimestamp,
                                'updated_at' => $logTimestamp,
                            ];
                            if (\Schema::hasColumn('stock_log_consumable', 'invoice_id')) {
                                $inLogData['invoice_id'] = (int) $invoice->id;
                            }
                            stockLogConsumable::create($inLogData);

                            $consumableFresh->increment('quantity', $qty);

                            $currentStock = $remaining_stock;
                            $futureLogs = stockLogConsumable::where('created_at', '>', $logTimestamp)
                                ->where('consumable_id', $consumables_id)
                                ->orderBy('created_at', 'asc')
                                ->orderBy('id', 'asc')
                                ->get();
                            foreach ($futureLogs as $stockLogCon) {
                                $stockLogCon->opening_balance = $currentStock;
                                $stockLogCon->remaining_stock = $stockLogCon->type == 1
                                    ? $stockLogCon->opening_balance + $stockLogCon->quantity
                                    : $stockLogCon->opening_balance - $stockLogCon->quantity;
                                $currentStock = $stockLogCon->remaining_stock;
                                $stockLogCon->save();
                            }

                            $outTimestamp = \Carbon\Carbon::parse($baseInvoiceDate . ' ' . $nowTime)->addSeconds($secondsOffset);
                            $secondsOffset += 5;

                            $outPreviousLog = stockLogConsumable::where('consumable_id', $consumables_id)
                                ->where('created_at', '<=', $outTimestamp)
                                ->orderBy('created_at', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            $outOpenBalance = $outPreviousLog ? (float) $outPreviousLog->remaining_stock : (float) $consumableFresh->quantity;
                            $outQty = min($qty, max(0.0, $outOpenBalance));
                            if ($outQty > 0) {
                                $outRemainingStock = $outOpenBalance - $outQty;
                                $outLogData = [
                                    'consumable_id' => $consumables_id,
                                    'product_id' => $wf->product_id,
                                    'voucher_no' => $invoice->invoiceno,
                                    'ref_no' => 'MonthEnd Invoice Stock Out',
                                    'quantity' => $outQty,
                                    'opening_balance' => $outOpenBalance,
                                    'remaining_stock' => $outRemainingStock,
                                    'type' => 2,
                                    'supplier_name' => $purchaseOrder->supplier->c_name ?? 'N/A',
                                    'supplier_id' => (int) ($purchaseOrder->supplier_id ?? 0) ?: null,
                                    'remark' => 'MonthEnd PO Stock Out',
                                    'created_at' => $outTimestamp,
                                    'updated_at' => $outTimestamp,
                                ];
                                if (\Schema::hasColumn('stock_log_consumable', 'invoice_id')) {
                                    $outLogData['invoice_id'] = (int) $invoice->id;
                                }
                                stockLogConsumable::create($outLogData);

                                $consumableFresh->decrement('quantity', $outQty);

                                $currentStock = $outRemainingStock;
                                $futureLogs = stockLogConsumable::where('created_at', '>', $outTimestamp)
                                    ->where('consumable_id', $consumables_id)
                                    ->orderBy('created_at', 'asc')
                                    ->orderBy('id', 'asc')
                                    ->get();
                                foreach ($futureLogs as $stockLogCon) {
                                    $stockLogCon->opening_balance = $currentStock;
                                    $stockLogCon->remaining_stock = $stockLogCon->type == 1
                                        ? $stockLogCon->opening_balance + $stockLogCon->quantity
                                        : $stockLogCon->opening_balance - $stockLogCon->quantity;
                                    $currentStock = $stockLogCon->remaining_stock;
                                    $stockLogCon->save();
                                }
                            }
                        }

                        $purchaseOrder->update([
                            'subTotal' => $subTotal,
                            'tquantity' => $tquantity,
                            'tamount' => $tamountPre + $totalgst,
                            'remqty' => $tquantity,
                            'tgst' => $totalgst,
                            'status' => 0,
                        ]);

                        $challan->update([
                            'subTotal' => $subTotal,
                            'tquantity' => $tquantity,
                            'tamount' => $tamountPre + $totalgst,
                            'tgst' => $totalgst,
                        ]);
                        $generatedForInvoice = true;
                    }
                    if ($generatedForInvoice) {
                        $createdInvoices[] = $invoice->invoiceno;
                    } else {
                        $skippedInvoices[] = $invoice->invoiceno;
                    }
                }

                if ($hasMonthEndCounter && $poCounter > $startCounter) {
                    $companyDetails->monthend_po_no = $poCounter;
                    $companyDetails->save();
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect('/invoice')->with('error', 'Hardware Month-End PO was not created: ' . $e->getMessage());
        }

        return redirect('/invoice')->with(
            'success',
            'Hardware Month-End PO created successfully.' .
                (count($skippedInvoices) > 0 ? ' ⚠️ Skipped / no new PO for: ' . implode(', ', $skippedInvoices) : '')
        );
    }

    public function viewConsumbalemonthendpo()
    {
        $purchaseOrders = purchaseOrderConsumable::where('address_option', 100)->orderBy('created_at', 'desc')->get();
        return view('purchaseOrder/ConsumableMonthend', ['purchaseOrders' => $purchaseOrders]);
    }



    /**
     * Fills the Month End modal supplier+invoice list from distinct consumable monthEndpo_supplier
     * (via Wf) — not from small_hardware_products. Buyer filtering for lines happens on preview/accept.
     */
    public function monthendsupplier(Request $request)
    {
        $uk18Set = $this->normalizeMonthEndUk18FromRequest($request);
        // Expect: invoice_id as CSV (e.g., "12,15,22,")
        $invoice_ids_csv = trim((string) $request->invoice_id ?? '', ',');
        if ($invoice_ids_csv === '') {
            return response()->json([
                'items' => [],
                'skippedInvoices' => [],
                'message' => 'No invoice IDs provided.',
            ]);
        }

        $invoice_ids = collect(explode(',', $invoice_ids_csv))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        $items = [];            // [{invoice_id, invoice_no, supplier_id, supplier_name}]
        $skippedInvoices = [];  // invoices we could not process (missing, no mappings, etc.)

        foreach ($invoice_ids as $invId) {
            /** @var \App\Models\invoice|null $invoice */
            $invoice = invoice::find($invId);
            if (!$invoice) {
                $skippedInvoices[] = $invId;
                continue;
            }

            // 1) products from invoiceTable (per invoice)
            $invProducts = invoiceTable::where('invoice_id', $invId)
                ->select('product_id')
                ->distinct()
                ->pluck('product_id')
                ->filter()
                ->values();

            if ($invProducts->isEmpty()) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            // 2) consumables via WfConsumable per product_id (distinct)
            $consumableIds = WfConsumable::whereIn('product_id', $invProducts)
                ->select('consumables_id')
                ->distinct()
                ->pluck('consumables_id')
                ->filter()
                ->values();

            if ($consumableIds->isEmpty()) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            // 3) distinct monthEndpo_supplier from those consumables (skip null/0)
            $supplierIds = consumable::whereIn('id', $consumableIds)
                ->whereNotNull('monthEndpo_supplier')
                ->where('monthEndpo_supplier', '!=', 0)
                ->pluck('monthEndpo_supplier')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            if ($supplierIds->isEmpty()) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            // 4) buyer-aware eligibility:
            // allow supplier again if selected buyers still have ungenerated entries.
            $eligibleSupplierIds = [];
            $buyerMetaBySupplier = [];
            foreach ($supplierIds as $sid) {
                $sid = (int) $sid;
                $already = $this->monthEndAlreadyGeneratedBuyerIds((string) $invoice->invoiceno, $sid);
                $newBuyers = $this->monthEndNewBuyerIdsForInvoiceSupplier((string) $invoice->invoiceno, $sid, $uk18Set);
                if ($newBuyers === []) {
                    continue;
                }
                $eligibleSupplierIds[] = $sid;
                $buyerMetaBySupplier[$sid] = [
                    'already_generated_buyers' => $already,
                    'new_buyers' => $newBuyers,
                ];
            }
            if ($eligibleSupplierIds === []) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            // 5) build items rows for modal list (only eligible suppliers)
            $suppliers = supplier::whereIn('id', $eligibleSupplierIds)->get(['id', 'c_name']);
            foreach ($suppliers as $sup) {
                $meta = $buyerMetaBySupplier[(int) $sup->id] ?? ['already_generated_buyers' => [], 'new_buyers' => $uk18Set];
                $items[] = [
                    'invoice_id'    => $invoice->id,
                    'invoice_no'    => $invoice->invoiceno,
                    'supplier_id'   => $sup->id,
                    'supplier_name' => $sup->c_name,
                    'already_generated_buyers' => $meta['already_generated_buyers'],
                    'new_buyers' => $meta['new_buyers'],
                ];
            }
        }

        // Optional: sort nicely
        usort(
            $items,
            fn($a, $b) =>
            [$a['supplier_name'], $a['invoice_no']] <=> [$b['supplier_name'], $b['invoice_no']]
        );

        return response()->json([
            'items' => $items,
            'skippedInvoices' => $skippedInvoices,
        ]);
    }

    /**
     * Supplier + invoice pairs for hardware month-end modal (hardware-flagged consumables only).
     */
    public function hardwareMonthendsupplier(Request $request)
    {
        $invoice_ids_csv = trim((string) ($request->invoice_id ?? ''), ',');
        if ($invoice_ids_csv === '') {
            return response()->json([
                'items' => [],
                'skippedInvoices' => [],
                'message' => 'No invoice IDs provided.',
            ]);
        }

        $invoice_ids = collect(explode(',', $invoice_ids_csv))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        $items = [];
        $skippedInvoices = [];

        foreach ($invoice_ids as $invId) {
            $invoice = invoice::find($invId);
            if (! $invoice) {
                $skippedInvoices[] = $invId;
                continue;
            }

            $invProducts = invoiceTable::where('invoice_id', $invId)
                ->select('product_id')
                ->distinct()
                ->pluck('product_id')
                ->filter()
                ->values();

            if ($invProducts->isEmpty()) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            $consumableIds = WfConsumable::whereIn('product_id', $invProducts)
                ->select('consumables_id')
                ->distinct()
                ->pluck('consumables_id')
                ->filter()
                ->values();

            if ($consumableIds->isEmpty()) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            $consumableQuery = consumable::whereIn('id', $consumableIds)
                ->whereNotNull('monthEndpo_supplier')
                ->where('monthEndpo_supplier', '!=', 0);
            if (\Schema::hasColumn('consumables', 'hardware_monthend_po_product')) {
                $consumableQuery->where('hardware_monthend_po_product', 1);
            } else {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            $supplierIds = $consumableQuery
                ->pluck('monthEndpo_supplier')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();

            if ($supplierIds->isEmpty()) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            $eligibleSupplierIds = [];
            foreach ($supplierIds as $sid) {
                $sid = (int) $sid;
                if ($this->hardwareMonthendPoExists((string) $invoice->invoiceno, $sid)) {
                    continue;
                }
                $eligibleSupplierIds[] = $sid;
            }

            if ($eligibleSupplierIds === []) {
                $skippedInvoices[] = $invoice->invoiceno ?? $invId;
                continue;
            }

            $suppliers = supplier::whereIn('id', $eligibleSupplierIds)->get(['id', 'c_name']);
            foreach ($suppliers as $sup) {
                $items[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoiceno,
                    'supplier_id' => $sup->id,
                    'supplier_name' => $sup->c_name,
                ];
            }
        }

        usort(
            $items,
            fn ($a, $b) => [$a['supplier_name'], $a['invoice_no']] <=> [$b['supplier_name'], $b['invoice_no']]
        );

        return response()->json([
            'items' => $items,
            'skippedInvoices' => $skippedInvoices,
        ]);
    }
}
