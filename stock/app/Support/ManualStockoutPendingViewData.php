<?php

namespace App\Support;

use App\ManualStockoutPendingCarton;
use App\ManualStockoutPendingConsumable;
use App\packaging;
use Illuminate\Support\Collection;

class ManualStockoutPendingViewData
{
    public static function consumableStillNeedFromReason(string $reason): ?float
    {
        if (preg_match('/still need:?\s*([\d.]+)/i', $reason, $m)) {
            return (float) $m[1];
        }
        if (str_contains($reason, 'Consumable record missing') && preg_match('/required:\s*([\d.]+)/', $reason, $m)) {
            return (float) $m[1];
        }
        // stockstore pre-check: "Container consumable shortage (Required: X, Available: Y)"
        if (preg_match('/Container consumable shortage \(Required:\s*([\d.]+),\s*Available:\s*([\d.]+)\)/i', $reason, $m)) {
            return max(0.0, (float) $m[1] - (float) $m[2]);
        }

        return null;
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function cartonShortFromReason(string $reason): array
    {
        $s1 = 0.0;
        $s2 = 0.0;
        if (preg_match('/Box 1 short by ([\d.]+)/i', $reason, $m)) {
            $s1 = (float) $m[1];
        }
        if (preg_match('/Box 2 short by ([\d.]+)/i', $reason, $m)) {
            $s2 = (float) $m[1];
        }

        return [$s1, $s2];
    }

    public static function consumableRowsForView(): Collection
    {
        $pendingConsumables = ManualStockoutPendingConsumable::query()
            ->where('status', 0)
            ->with(['invoice.buyer', 'consumable', 'product', 'stockout'])
            ->orderByDesc('id')
            ->get();

        return $pendingConsumables->map(function (ManualStockoutPendingConsumable $p) {
            $still = self::consumableStillNeedFromReason((string) $p->reason);
            $cons = $p->consumable;
            $avail = $cons ? (float) ($cons->quantity ?? 0) : null;

            return [
                'pending' => $p,
                'still_need' => $still,
                'available' => $avail,
            ];
        });
    }

    public static function cartonRowsForView(): Collection
    {
        $pendingCartons = ManualStockoutPendingCarton::query()
            ->where('status', 0)
            ->with(['invoice.buyer', 'product', 'stockout'])
            ->orderByDesc('id')
            ->get();

        return $pendingCartons->map(function (ManualStockoutPendingCarton $p) {
            $s1 = $p->short_qty_box1 !== null ? (float) $p->short_qty_box1 : null;
            $s2 = $p->short_qty_box2 !== null ? (float) $p->short_qty_box2 : null;
            if (($s1 === null || $s1 <= 0) && ($s2 === null || $s2 <= 0)) {
                [$r1, $r2] = self::cartonShortFromReason((string) $p->reason);
                if ($s1 === null || $s1 <= 0) {
                    $s1 = $r1;
                }
                if ($s2 === null || $s2 <= 0) {
                    $s2 = $r2;
                }
            }
            $pack = $p->product_id ? packaging::where('product_id', $p->product_id)->first() : null;

            return [
                'pending' => $p,
                'short_box1' => (float) ($s1 ?? 0),
                'short_box2' => (float) ($s2 ?? 0),
                'avail_box1' => $pack ? (float) ($pack->box_1_qty ?? 0) : null,
                'avail_box2' => $pack ? (float) ($pack->box_2_qty ?? 0) : null,
            ];
        });
    }
}
