<?php

namespace App\Support;

use App\stockout;

class ManualCartonProductGap
{
    /**
     * @return array<int, array{
     *   product_id: int,
     *   total_furniture_out: float,
     *   deducted_box1: float,
     *   deducted_box2: float,
     *   location: string,
     * }>
     */
    public static function aggregateFurnitureAndDeductions(stockout $stock): array
    {
        $byProduct = [];

        foreach ($stock->stockoutTable as $st) {
            $pid = (int) $st->product_id;
            $rq = (float) ($st->receiveqty ?? 0);
            if ($rq <= 0) {
                continue;
            }

            if (! isset($byProduct[$pid])) {
                $byProduct[$pid] = [
                    'product_id' => $pid,
                    'total_furniture_out' => 0.0,
                    'deducted_box1' => 0.0,
                    'deducted_box2' => 0.0,
                    'location' => '',
                ];
            }

            $byProduct[$pid]['total_furniture_out'] += $rq;
            if (trim((string) ($st->location ?? '')) !== '') {
                $byProduct[$pid]['location'] = (string) $st->location;
            }
        }

        foreach ($stock->stockoutTableCarton as $cq) {
            $pid = (int) $cq->product_id;
            if (! isset($byProduct[$pid])) {
                continue;
            }
            $byProduct[$pid]['deducted_box1'] += (float) ($cq->receiveqty ?? 0);
            $byProduct[$pid]['deducted_box2'] += (float) ($cq->receiveqty2 ?? 0);
        }

        return $byProduct;
    }

    /**
     * @param  array{total_furniture_out: float, deducted_box1: float, deducted_box2: float}  $base
     * @return array{gap_box1: float, gap_box2: float, needs_qty_config: bool}
     */
    public static function computeGaps(array $base, float $per1, float $per2): array
    {
        $totalFurniture = (float) ($base['total_furniture_out'] ?? 0);
        $deducted1 = (float) ($base['deducted_box1'] ?? 0);
        $deducted2 = (float) ($base['deducted_box2'] ?? 0);

        $totalNeed1 = $per1 * $totalFurniture;
        $totalNeed2 = $per2 * $totalFurniture;

        return [
            'gap_box1' => max(0.0, $totalNeed1 - $deducted1),
            'gap_box2' => max(0.0, $totalNeed2 - $deducted2),
            'needs_qty_config' => (
                $per1 <= 0
                && $per2 <= 0
                && $totalFurniture > 0
                && $deducted1 <= 0.00001
                && $deducted2 <= 0.00001
            ),
        ];
    }

    public static function needsManualCarton(float $gap1, float $gap2, bool $needsQtyConfig): bool
    {
        return $gap1 > 0.00001 || $gap2 > 0.00001 || $needsQtyConfig;
    }

    /**
     * Carton boxes still to deduct for this product (uses submitted per-unit qty).
     */
    public static function incrementalNeed(array $base, float $per1, float $per2): array
    {
        $gaps = self::computeGaps($base, $per1, $per2);

        return [
            'need_box1' => $gaps['gap_box1'],
            'need_box2' => $gaps['gap_box2'],
            'append' => (
                (float) ($base['deducted_box1'] ?? 0) > 0.00001
                || (float) ($base['deducted_box2'] ?? 0) > 0.00001
            ),
        ];
    }
}
