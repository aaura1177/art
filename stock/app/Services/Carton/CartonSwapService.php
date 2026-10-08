<?php

namespace App\Services\Carton;

use App\cartonSwapping;
use App\packaging;
use App\ProductCarton;
use App\StockLogCarton;
use RuntimeException;

class CartonSwapService
{
    /**
     * Move box1/box2 qty from source product packaging to target product packaging.
     *
     * @return array{carton_swapping_ids: int[], box1: float, box2: float}
     */
    public function transferBetweenProducts(
        int $sourceProductId,
        int $targetProductId,
        float $box1Qty,
        float $box2Qty,
        string $voucherNo,
        string $refOut = 'Carton Swap Out',
        string $refIn = 'Carton Swap In'
    ): array {
        $box1Qty = max(0.0, $box1Qty);
        $box2Qty = max(0.0, $box2Qty);

        if ($box1Qty <= 0 && $box2Qty <= 0) {
            return ['carton_swapping_ids' => [], 'box1' => 0.0, 'box2' => 0.0];
        }

        if ($sourceProductId === $targetProductId) {
            throw new RuntimeException('Swap source and target product cannot be the same.');
        }

        $fromPack = packaging::query()
            ->where('product_id', $sourceProductId)
            ->lockForUpdate()
            ->first();
        $toPack = packaging::query()
            ->where('product_id', $targetProductId)
            ->lockForUpdate()
            ->first();

        if (! $fromPack) {
            throw new RuntimeException('Packaging not found for swap source product ID ' . $sourceProductId . '.');
        }
        if (! $toPack) {
            throw new RuntimeException('Packaging not found for swap target product ID ' . $targetProductId . '.');
        }

        if ($box1Qty > (float) ($fromPack->box_1_qty ?? 0)) {
            throw new RuntimeException('Insufficient box1 on swap source product ID ' . $sourceProductId . '.');
        }
        if ($box2Qty > (float) ($fromPack->box_2_qty ?? 0)) {
            throw new RuntimeException('Insufficient box2 on swap source product ID ' . $sourceProductId . '.');
        }

        $voucher = strtoupper(trim($voucherNo));
        $csIds = [];

        if ($box1Qty > 0) {
            $csIds[] = $this->applySingleBoxTransfer($fromPack, $toPack, 'box_1_qty', $box1Qty, $voucher, $refOut, $refIn);
        }
        if ($box2Qty > 0) {
            $csIds[] = $this->applySingleBoxTransfer($fromPack, $toPack, 'box_2_qty', $box2Qty, $voucher, $refOut, $refIn);
        }

        return ['carton_swapping_ids' => $csIds, 'box1' => $box1Qty, 'box2' => $box2Qty];
    }

    /**
     * Mirror proportional carton stock when furniture is swapped OUT -> IN.
     * Swaps only what is available if OUT packaging has less than proportional need.
     */
    public function mirrorFurnitureSwapCarton(int $productOutId, int $productInId, int $swapQty, string $invoiceNo): void
    {
        if ($swapQty <= 0 || $productOutId === $productInId) {
            return;
        }

        $pcOut = ProductCarton::query()->where('product_id', $productOutId)->first();
        if (! $pcOut) {
            return;
        }

        $prop1 = (float) ($pcOut->quantity1 ?? 0) * $swapQty;
        $prop2 = (float) ($pcOut->quantity2 ?? 0) * $swapQty;
        if ($prop1 <= 0 && $prop2 <= 0) {
            return;
        }

        $packOut = packaging::query()->where('product_id', $productOutId)->lockForUpdate()->first();
        if (! $packOut) {
            return;
        }

        $packIn = packaging::query()->where('product_id', $productInId)->lockForUpdate()->first();
        if (! $packIn) {
            return;
        }

        $actual1 = min($prop1, (float) ($packOut->box_1_qty ?? 0));
        $actual2 = min($prop2, (float) ($packOut->box_2_qty ?? 0));
        if ($actual1 <= 0 && $actual2 <= 0) {
            return;
        }

        $this->transferBetweenProducts(
            $productOutId,
            $productInId,
            $actual1,
            $actual2,
            $invoiceNo,
            'Furniture Swap Carton Out',
            'Furniture Swap Carton In'
        );
    }

    private function applySingleBoxTransfer(
        packaging $fromPack,
        packaging $toPack,
        string $cartonField,
        float $qty,
        string $voucher,
        string $refOut,
        string $refIn
    ): int {
        $openOutBox1 = (float) ($fromPack->box_1_qty ?? 0);
        $openOutBox2 = (float) ($fromPack->box_2_qty ?? 0);
        $openInBox1 = (float) ($toPack->box_1_qty ?? 0);
        $openInBox2 = (float) ($toPack->box_2_qty ?? 0);

        $cs = cartonSwapping::create([
            'invoice_no' => $voucher,
            'packaging_out_id' => (int) $fromPack->id,
            'packaging_in_id' => (int) $toPack->id,
            'carton_type' => $cartonField,
            'quantity' => $qty,
        ]);

        $fromPack->{$cartonField} = (float) ($fromPack->{$cartonField} ?? 0) - $qty;
        $fromPack->save();

        $toPack->{$cartonField} = (float) ($toPack->{$cartonField} ?? 0) + $qty;
        $toPack->save();

        $isBox1 = $cartonField === 'box_1_qty';

        StockLogCarton::create([
            'product_id' => (int) $fromPack->product_id,
            'voucher_no' => $voucher,
            'ref_no' => $refOut,
            'quantity' => $isBox1 ? $qty : 0,
            'quantity2' => $isBox1 ? 0 : $qty,
            'opening_balance' => $openOutBox1,
            'opening_balance2' => $openOutBox2,
            'remaining_stock' => (float) ($fromPack->box_1_qty ?? 0),
            'remaining_stock2' => (float) ($fromPack->box_2_qty ?? 0),
            'type' => 2,
        ]);

        StockLogCarton::create([
            'product_id' => (int) $toPack->product_id,
            'voucher_no' => $voucher,
            'ref_no' => $refIn,
            'quantity' => $isBox1 ? $qty : 0,
            'quantity2' => $isBox1 ? 0 : $qty,
            'opening_balance' => $openInBox1,
            'opening_balance2' => $openInBox2,
            'remaining_stock' => (float) ($toPack->box_1_qty ?? 0),
            'remaining_stock2' => (float) ($toPack->box_2_qty ?? 0),
            'type' => 1,
        ]);

        return (int) $cs->id;
    }
}
