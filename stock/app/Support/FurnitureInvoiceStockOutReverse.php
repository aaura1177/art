<?php

namespace App\Support;

use App\Batch;
use App\BatchProduct;
use App\ManualStockoutPendingConsumable;
use App\StockoutRequestBackup;
use App\consumable;
use App\invoice;
use App\invoiceTable;
use App\packingList;
use App\packingListProduct;
use App\product;
use App\productLocations;
use App\stockLog;
use App\stockLogConsumable;
use App\stockout;
use App\stockoutTable;
use App\stockoutTableConsumable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reverse a furniture invoice stock-out (batch, unique ref, product qty, logs), then optionally cancel.
 */
final class FurnitureInvoiceStockOutReverse
{
    /**
     * @return array<string, mixed>
     */
    public function reverseThenCancel(invoice $invoice, bool $cancel = true): array
    {
        if ((int) $invoice->is_canceled === 1) {
            throw new RuntimeException('Invoice is already canceled.');
        }

        $summary = [
            'invoice_id' => (int) $invoice->id,
            'invoiceno' => (string) $invoice->invoiceno,
            'stockout_ids' => [],
            'furniture_logs_deleted' => 0,
            'consumable_logs_deleted' => 0,
            'batch_qty_restored' => 0,
            'unique_ref_qty_restored' => 0,
            'canceled' => false,
        ];

        $stockouts = stockout::where('invoice_id', $invoice->id)->orderBy('id')->get();
        $summary['stockout_ids'] = $stockouts->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($stockouts as $stockoutRow) {
            $part = $this->reverseOneStockOut($invoice, $stockoutRow);
            $summary['furniture_logs_deleted'] += $part['furniture_logs_deleted'];
            $summary['consumable_logs_deleted'] += $part['consumable_logs_deleted'];
            $summary['batch_qty_restored'] += $part['batch_qty_restored'];
            $summary['unique_ref_qty_restored'] += $part['unique_ref_qty_restored'];
        }

        $this->deleteStockoutRequestBackup((int) $invoice->id);

        $invoice->refresh();
        $statusRemQty = 0.0;
        foreach (invoiceTable::where('invoice_id', $invoice->id)->get() as $line) {
            $statusRemQty += (float) $line->remqty;
        }
        $invoice->status = $statusRemQty == 0.0 ? 1 : 0;
        $invoice->save();

        if ($cancel) {
            $invoice->refresh();
            if (! $invoice->canBeCanceled()) {
                throw new RuntimeException(
                    'Stock-out reverse finished but invoice still cannot be canceled (stock-out remains or remaining qty is not full).'
                );
            }
            $invoice->is_canceled = 1;
            $invoice->save();
            $summary['canceled'] = true;
        }

        return $summary;
    }

    /**
     * @return array{furniture_logs_deleted:int,consumable_logs_deleted:int,batch_qty_restored:int,unique_ref_qty_restored:int}
     */
    protected function reverseOneStockOut(invoice $invoice, stockout $stockoutRow): array
    {
        $stockId = (int) $stockoutRow->id;
        $voucher = (string) $invoice->invoiceno;
        $productIdsTouched = [];
        $consumableIdsTouched = [];
        $batchQtyRestored = 0;
        $uniqueRefRestored = 0;

        $furnitureLogs = stockLog::query()
            ->where('voucher_no', $voucher)
            ->where('type', 2)
            ->where('entity_id', $stockId)
            ->orderBy('id')
            ->get();

        $snapshotsByProduct = $this->packingSnapshotsByProduct($invoice);

        foreach ($furnitureLogs as $log) {
            $productId = (int) $log->product_id;
            $qty = (int) $log->quantity;
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $productIdsTouched[$productId] = true;

            $product = product::where('id', $productId)->lockForUpdate()->first();
            if (! $product) {
                throw new RuntimeException('Product ' . $productId . ' not found while reversing stock-out.');
            }
            $product->quantity = (float) $product->quantity + $qty;
            $product->save();

            $batchNo = trim((string) ($log->batch_no ?? ''));
            if ($batchNo !== '') {
                $batch = Batch::where('batch_no', $batchNo)->lockForUpdate()->first();
                if (! $batch) {
                    throw new RuntimeException('Batch ' . $batchNo . ' not found while reversing stock-out.');
                }
                $batchProduct = BatchProduct::where('batch_id', $batch->id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
                if (! $batchProduct) {
                    throw new RuntimeException(
                        'Batch product missing for batch ' . $batchNo . ' product ' . $productId . '.'
                    );
                }
                $batchProduct->quantity = (int) $batchProduct->quantity + $qty;
                $batchProduct->save();
                $batch->quantity = (int) $batch->quantity + $qty;
                $batch->save();
                $batchQtyRestored += $qty;

                $snap = $snapshotsByProduct[$productId] ?? [];
                $added = $this->addBackUniqueRefFromStockLog($log, $snap);
                if ($added < $qty) {
                    throw new RuntimeException(
                        'Unique ref add-back short for product ' . $productId
                        . ' batch ' . $batchNo . ' (added ' . $added . ' of ' . $qty . ').'
                    );
                }
                $uniqueRefRestored += $added;
            }
        }

        foreach (stockoutTable::where('stock_id', $stockId)->get() as $line) {
            $productId = (int) $line->product_id;
            $qty = (int) $line->receiveqty;
            $productIdsTouched[$productId] = true;

            $invLine = invoiceTable::where('invoice_id', $invoice->id)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();
            if ($invLine) {
                $invLine->remqty = min((float) $invLine->quantity, (float) $invLine->remqty + $qty);
                $invLine->save();
            }

            $location = trim((string) ($line->location ?? ''));
            if ($location !== '') {
                $plocation = productLocations::where('product_id', $productId)
                    ->where('location', $location)
                    ->lockForUpdate()
                    ->first();
                if ($plocation) {
                    $plocation->quantity = (float) $plocation->quantity + $qty;
                    $plocation->save();
                }
            }
        }

        $furnitureDeleted = stockLog::query()
            ->where('voucher_no', $voucher)
            ->where('type', 2)
            ->where('entity_id', $stockId)
            ->delete();

        foreach (array_keys($productIdsTouched) as $productId) {
            $this->rebuildFurnitureStockLogs((int) $productId);
        }

        foreach (stockoutTableConsumable::where('stock_id', $stockId)->get() as $cLine) {
            $consumableId = (int) $cLine->consumable_id;
            $qty = (float) $cLine->receiveqty;
            if ($consumableId <= 0 || $qty <= 0) {
                continue;
            }
            $consumableIdsTouched[$consumableId] = true;
            $consumable = consumable::where('id', $consumableId)->lockForUpdate()->first();
            if ($consumable) {
                $consumable->quantity = (float) $consumable->quantity + $qty;
                $consumable->save();
            }
        }

        $consumableDeleted = stockLogConsumable::query()
            ->where('voucher_no', $voucher)
            ->where('type', 2)
            ->delete();

        foreach (array_keys($consumableIdsTouched) as $consumableId) {
            $this->rebuildConsumableStockLogs((int) $consumableId);
        }

        stockoutTableConsumable::where('stock_id', $stockId)->delete();
        ManualStockoutPendingConsumable::where(function ($q) use ($stockId, $invoice) {
            $q->where('stock_id', $stockId)->orWhere('invoice_id', $invoice->id);
        })->delete();
        stockoutTable::where('stock_id', $stockId)->delete();
        $stockoutRow->delete();

        return [
            'furniture_logs_deleted' => (int) $furnitureDeleted,
            'consumable_logs_deleted' => (int) $consumableDeleted,
            'batch_qty_restored' => $batchQtyRestored,
            'unique_ref_qty_restored' => $uniqueRefRestored,
        ];
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function packingSnapshotsByProduct(invoice $invoice): array
    {
        $packing = packingList::where('buyer_order_no', $invoice->buyerorderno)->first();
        if (! $packing) {
            return [];
        }

        $byProduct = [];
        foreach (packingListProduct::where('packinglist_id', $packing->id)->get() as $plp) {
            $productId = (int) $plp->product_id;
            $snap = $plp->ref_source_snapshot ?? [];
            if (is_string($snap)) {
                $decoded = json_decode($snap, true);
                $snap = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($snap) || $snap === []) {
                continue;
            }
            $byProduct[$productId] = $snap;
        }

        return $byProduct;
    }

    /**
     * Restore unique-ref remaining qty for one stock_log OUT row.
     *
     * @param  array<int, array<string, mixed>>  $refSourceSnapshot
     */
    protected function addBackUniqueRefFromStockLog(stockLog $log, array $refSourceSnapshot): int
    {
        $productId = (int) $log->product_id;
        $batchNo = trim((string) ($log->batch_no ?? ''));
        $qtyToAddBack = (int) $log->quantity;
        if ($productId <= 0 || $batchNo === '' || $qtyToAddBack <= 0) {
            return 0;
        }

        $added = 0;
        $pieces = $this->parseReferenceNumberUsedQtys((string) ($log->reference_number ?? ''));
        if ($pieces !== []) {
            foreach ($pieces as $piece) {
                $urId = $this->uniqueRefIdForLogLabel($piece['label'], $batchNo, $productId, $refSourceSnapshot);
                if ($urId <= 0) {
                    continue;
                }
                $added += $this->addBackToUniqueRef($urId, $productId, $piece['used'], true);
            }
        }

        if ($added < $qtyToAddBack) {
            $added += $this->addBackUniqueRef(
                $productId,
                $batchNo,
                $refSourceSnapshot,
                $qtyToAddBack - $added
            );
        }

        return $added;
    }

    /**
     * @return array<int, array{label:string,used:int}>
     */
    protected function parseReferenceNumberUsedQtys(string $referenceNumber): array
    {
        $referenceNumber = trim($referenceNumber);
        if ($referenceNumber === '') {
            return [];
        }

        $out = [];
        if (! preg_match_all('/\s*(.+?)\((\d+)\/(\d+)\)\s*(?:,|$)/', $referenceNumber, $matches, PREG_SET_ORDER)) {
            return [];
        }
        foreach ($matches as $m) {
            $used = (int) $m[2];
            if ($used <= 0) {
                continue;
            }
            $out[] = [
                'label' => trim((string) $m[1]),
                'used' => $used,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $refSourceSnapshot
     */
    protected function uniqueRefIdForLogLabel(
        string $label,
        string $batchNo,
        int $productId,
        array $refSourceSnapshot
    ): int {
        $label = trim($label);
        $noSi = strcasecmp($label, 'no supplierinvoice') === 0;

        foreach ($this->snapshotUrIdsForBatch($refSourceSnapshot, $batchNo) as $urId) {
            $ur = DB::table('unique_referencenumber')->where('id', $urId)->first();
            if (! $ur) {
                continue;
            }
            $sid = (int) ($ur->supplier_invoice_id ?? 0);
            if ($noSi) {
                if ($sid <= 0) {
                    return (int) $urId;
                }
                continue;
            }
            if ($sid <= 0) {
                continue;
            }
            $siNo = trim((string) DB::table('supplier_invoices')->where('id', $sid)->value('supplier_invoice_number'));
            if ($siNo !== '' && strcasecmp($siNo, $label) === 0) {
                return (int) $urId;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $refSourceSnapshot
     * @return array<int, int>
     */
    protected function snapshotUrIdsForBatch(array $refSourceSnapshot, string $batchNo): array
    {
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

        $seen = [];
        $ordered = [];
        foreach ($urIds as $u) {
            if (isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $ordered[] = $u;
        }

        return $ordered;
    }

    /**
     * Restore remaining qty onto one unique ref. When $allowZeroOriginal is true, product originalqty=0
     * does not block (stock-out deducts remaining_qty even if originalqty is 0).
     */
    protected function addBackToUniqueRef(int $urId, int $productId, int $qtyToAdd, bool $allowZeroOriginal): int
    {
        if ($urId <= 0 || $productId <= 0 || $qtyToAdd <= 0) {
            return 0;
        }

        $ur = DB::table('unique_referencenumber')->where('id', $urId)->lockForUpdate()->first();
        if (! $ur) {
            return 0;
        }

        $urp = DB::table('unique_referencenumber_product')
            ->where('unique_referencenumber_id', $urId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
        if (! $urp) {
            return 0;
        }

        $origP = (int) ($urp->originalqty ?? 0);
        $remP = (int) ($urp->remaining_qty ?? 0);
        if ($origP > 0) {
            $capP = max(0, $origP - $remP);
        } elseif ($allowZeroOriginal) {
            $capP = $qtyToAdd;
        } else {
            $capP = 0;
        }
        if ($capP <= 0) {
            return 0;
        }

        $origH = (int) ($ur->original_qty ?? 0);
        $remH = (int) ($ur->remqty ?? 0);
        $capH = max(0, $origH - $remH);

        $toAdd = min($qtyToAdd, $capP);
        if ($capH > 0) {
            $toAdd = min($toAdd, $capH);
        }
        if ($toAdd <= 0) {
            return 0;
        }

        $urpUpdate = [
            'remaining_qty' => $remP + $toAdd,
            'updated_at' => now(),
        ];
        if ($origP <= 0) {
            $urpUpdate['originalqty'] = $remP + $toAdd;
        }

        DB::table('unique_referencenumber_product')
            ->where('id', (int) $urp->id)
            ->update($urpUpdate);
        DB::table('unique_referencenumber')
            ->where('id', $urId)
            ->update([
                'remqty' => $remH + $toAdd,
                'updated_at' => now(),
            ]);

        return $toAdd;
    }

    /**
     * Two-pass snapshot restore: fill originalqty caps first (reverse selection), then leftover onto orig=0 rows.
     *
     * @param  array<int, array<string, mixed>>  $refSourceSnapshot
     */
    protected function addBackUniqueRef(int $productId, string $batchNo, array $refSourceSnapshot, int $qtyToAddBack): int
    {
        if ($productId <= 0 || $batchNo === '' || $qtyToAddBack <= 0) {
            return 0;
        }

        $ordered = array_reverse($this->snapshotUrIdsForBatch($refSourceSnapshot, $batchNo));
        if ($ordered === []) {
            return 0;
        }

        $added = 0;
        $remaining = $qtyToAddBack;

        foreach ($ordered as $urId) {
            if ($remaining <= 0) {
                break;
            }
            $got = $this->addBackToUniqueRef((int) $urId, $productId, $remaining, false);
            $added += $got;
            $remaining -= $got;
        }

        foreach ($ordered as $urId) {
            if ($remaining <= 0) {
                break;
            }
            $got = $this->addBackToUniqueRef((int) $urId, $productId, $remaining, true);
            $added += $got;
            $remaining -= $got;
        }

        return $added;
    }

    protected function rebuildFurnitureStockLogs(int $productId): void
    {
        $logs = stockLog::where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        if ($logs->isEmpty()) {
            return;
        }

        $current = (float) ($logs->first()->opening_balance ?? 0);
        foreach ($logs as $log) {
            $log->opening_balance = $current;
            $log->remaining_stock = (int) $log->type === 1
                ? $current + (float) $log->quantity
                : $current - (float) $log->quantity;
            $current = (float) $log->remaining_stock;
            $log->save();
        }
    }

    protected function rebuildConsumableStockLogs(int $consumableId): void
    {
        $logs = stockLogConsumable::where('consumable_id', $consumableId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        if ($logs->isEmpty()) {
            return;
        }

        $current = (float) ($logs->first()->opening_balance ?? 0);
        foreach ($logs as $log) {
            $log->opening_balance = $current;
            $log->remaining_stock = (int) $log->type === 1
                ? $current + (float) $log->quantity
                : $current - (float) $log->quantity;
            $current = (float) $log->remaining_stock;
            $log->save();
        }
    }

    protected function deleteStockoutRequestBackup(int $invoiceId): void
    {
        $backup = StockoutRequestBackup::where('invoice_id', $invoiceId)->first();
        if (! $backup) {
            return;
        }
        $backup->products()->delete();
        $backup->delete();
    }
}
