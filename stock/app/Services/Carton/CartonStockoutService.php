<?php

namespace App\Services\Carton;

use App\invoice;
use App\packaging;
use App\StockLogCarton;
use App\StockOutCartonSwapSource;
use App\StockOutTableCarton;
use App\stockout;
use RuntimeException;

class CartonStockoutService
{
    /**
     * Strict carton stockout for one invoice product line (after swaps applied to target packaging).
     *
     * @param  array<int, array{source_product_id: int, swap_priority: int, box1: float, box2: float, carton_swapping_ids: int[]}>  $swapAuditRows
     */
    public function deductForLine(
        invoice $inv,
        stockout $stock,
        int $productId,
        float $need1,
        float $need2,
        float $remQty,
        ?string $location,
        array $swapAuditRows,
        bool $append = false
    ): StockOutTableCarton {
        if ($need1 <= 0 && $need2 <= 0) {
            throw new RuntimeException('Carton need is zero for product ID ' . $productId . '.');
        }

        $packaging = packaging::query()
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $packaging) {
            throw new RuntimeException('Packaging record missing for product ID ' . $productId . '.');
        }

        $avail1 = (float) ($packaging->box_1_qty ?? 0);
        $avail2 = (float) ($packaging->box_2_qty ?? 0);

        if ($need1 > $avail1 + 0.00001) {
            throw new RuntimeException(
                'Carton shortage box1 for product ID ' . $productId . " (need {$need1}, available {$avail1})."
            );
        }
        if ($need2 > $avail2 + 0.00001) {
            throw new RuntimeException(
                'Carton shortage box2 for product ID ' . $productId . " (need {$need2}, available {$avail2})."
            );
        }

        $open1 = $avail1;
        $open2 = $avail2;

        $pc = \App\ProductCarton::query()->where('product_id', $productId)->first();
        $per1 = $pc ? (float) ($pc->quantity1 ?? 0) : 0.0;
        $per2 = $pc ? (float) ($pc->quantity2 ?? 0) : 0.0;

        $existing = StockOutTableCarton::query()
            ->where('stock_id', (int) $stock->id)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($append && $existing) {
            $existing->receiveqty = (float) ($existing->receiveqty ?? 0) + $need1;
            $existing->receiveqty2 = (float) ($existing->receiveqty2 ?? 0) + $need2;
            $existing->orderqty = $per1 * $remQty;
            $existing->orderqty2 = $per2 * $remQty;
            if ($location !== null && $location !== '') {
                $existing->location = $location;
            }
            $existing->save();
            $cartonRow = $existing;
        } else {
            if ($existing) {
                StockOutTableCarton::query()
                    ->where('id', $existing->id)
                    ->delete();
            }

            $cartonRow = StockOutTableCarton::create([
                'product_id' => $productId,
                'stock_id' => (int) $stock->id,
                'orderqty' => $per1 * $remQty,
                'orderqty2' => $per2 * $remQty,
                'receiveqty' => $need1,
                'receiveqty2' => $need2,
                'remainingqty' => 0,
                'remainingqty2' => 0,
                'location' => $location,
            ]);
        }

        $packaging->box_1_qty = $avail1 - $need1;
        $packaging->box_2_qty = $avail2 - $need2;
        $packaging->save();

        StockLogCarton::create([
            'product_id' => $productId,
            'voucher_no' => $inv->invoiceno,
            'ref_no' => 'Stock Out - Buyer Order No. - ' . $inv->buyerorderno,
            'quantity' => $need1,
            'quantity2' => $need2,
            'opening_balance' => $open1,
            'opening_balance2' => $open2,
            'remaining_stock' => (float) ($packaging->box_1_qty ?? 0),
            'remaining_stock2' => (float) ($packaging->box_2_qty ?? 0),
            'type' => 2,
        ]);

        foreach ($swapAuditRows as $audit) {
            $csId = isset($audit['carton_swapping_ids'][0]) ? (int) $audit['carton_swapping_ids'][0] : null;

            StockOutCartonSwapSource::create([
                'stockout_carton_id' => (int) $cartonRow->id,
                'stock_id' => (int) $stock->id,
                'invoice_id' => (int) $inv->id,
                'target_product_id' => $productId,
                'source_product_id' => (int) $audit['source_product_id'],
                'swap_priority' => (int) $audit['swap_priority'],
                'box1_qty' => (float) ($audit['box1'] ?? 0),
                'box2_qty' => (float) ($audit['box2'] ?? 0),
                'carton_swapping_id' => $csId,
            ]);
        }

        return $cartonRow;
    }
}
