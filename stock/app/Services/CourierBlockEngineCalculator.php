<?php

namespace App\Services;

/**
 * Server-side evaluation of courier block rules — mirrors pricing/view.blade.php
 * (calculateCountryBlockEngineSurcharge, evaluateUSOneBlock, tier helpers).
 */
final class CourierBlockEngineCalculator
{
    public static function finalCost(
        float $baseCourier,
        array $payload,
        float $boxWidthCm,
        float $boxHeightCm,
        float $boxDepthCm,
        float $volKg,
        float $volLbs,
        float $boxWtKg
    ): float {
        $engine = $payload['engine'] ?? '';
        if ($engine === 'us_blocks_v1') {
            $metrics = self::computeUsMetrics($boxWidthCm, $boxHeightCm, $boxDepthCm, $volKg, $volLbs, $boxWtKg);
            $total = self::sumMatchedBlocks($payload, $metrics);

            return round($baseCourier + $total, 2);
        }
        if ($engine === 'uk_blocks_v1') {
            $metrics = self::computeUkMetrics($boxWidthCm, $boxHeightCm, $boxDepthCm, $volKg, $boxWtKg);
            $total = self::sumMatchedBlocks($payload, $metrics);

            return round($baseCourier + $total, 2);
        }

        return round($baseCourier, 2);
    }

    private static function sumMatchedBlocks(array $payload, array $metrics): float
    {
        $blocks = $payload['blocks'] ?? [];
        usort($blocks, static function ($a, $b) {
            return ($a['block_priority'] ?? 0) <=> ($b['block_priority'] ?? 0);
        });
        $total = 0.0;
        foreach ($blocks as $block) {
            $total += self::evaluateOneBlock($block, $metrics);
        }

        return $total;
    }

    private static function computeUsMetrics(
        float $bw,
        float $bh,
        float $bd,
        float $volKg,
        float $volLbs,
        float $boxWtKg
    ): array {
        $L = $bw / 2.54;
        $W = $bh / 2.54;
        $D = $bd / 2.54;
        $arr = [$L, $W, $D];
        rsort($arr);
        $lengthLong = $arr[0];
        $mid = $arr[1];
        $small = $arr[2];
        $boxWeightLbs = $boxWtKg * 2.20462;

        return [
            'oneSideMax' => $lengthLong,
            'boxLIn' => $L,
            'boxWIn' => $W,
            'boxDIn' => $D,
            'lengthLong' => $lengthLong,
            'girth' => 2 * ($mid + $small) + $lengthLong,
            'lengthPlusGirth' => $lengthLong + 2 * ($mid + $small),
            'kg' => $volKg,
            'lbs' => $volLbs,
            'boxWeightLbs' => $boxWeightLbs,
            'boxWeightKg' => $boxWtKg,
            'boxWeightUnit' => 'lb',
        ];
    }

    private static function computeUkMetrics(float $bw, float $bh, float $bd, float $volKg, float $boxWtKg): array
    {
        $L = $bw;
        $W = $bh;
        $D = $bd;
        $arr = [$L, $W, $D];
        rsort($arr);
        $lengthLong = $arr[0];
        $mid = $arr[1];
        $small = $arr[2];

        return [
            'oneSideMax' => $lengthLong,
            'boxLIn' => $L,
            'boxWIn' => $W,
            'boxDIn' => $D,
            'lengthLong' => $lengthLong,
            'girth' => 2 * ($mid + $small) + $lengthLong,
            'lengthPlusGirth' => $lengthLong + 2 * ($mid + $small),
            'kg' => $volKg,
            'lbs' => 0.0,
            'boxWeightLbs' => $boxWtKg * 2.20462,
            'boxWeightKg' => $boxWtKg,
            'boxWeightUnit' => 'kg',
        ];
    }

    private static function metricForAttribute(?string $key, array $m): ?float
    {
        $k = strtolower(str_replace('-', '_', (string) $key));
        if ($k === '1_side') {
            $k = '1_sides';
        }
        switch ($k) {
            case '1_sides':
                return $m['oneSideMax'];
            case '2_sides':
            case '3_sides':
                return null;
            case 'length':
                return $m['lengthLong'];
            case 'girth':
                return $m['girth'];
            case 'length_plus_girth':
                return $m['lengthPlusGirth'];
            case 'kg':
                return $m['kg'];
            case 'lbs':
                return $m['lbs'];
            case 'gross_lbs':
                return $m['boxWeightLbs'];
            case 'box_weight':
            case 'boxweight':
                if (($m['boxWeightUnit'] ?? '') === 'kg') {
                    return (float) $m['boxWeightKg'];
                }

                return $m['boxWeightLbs'];
            default:
                return null;
        }
    }

    private static function tierMatches(string $op, ?float $vmin, ?float $vmax, float $v): bool
    {
        $op = strtolower($op);
        if ($op === 'always') {
            return true;
        }
        $minn = $vmin;
        $maxx = $vmax;
        switch ($op) {
            case '>':
                return $minn !== null && $v > $minn;
            case '>=':
                return $minn !== null && $v >= $minn;
            case '<':
                return $minn !== null && $v < $minn;
            case '<=':
                return $minn !== null && $v <= $minn;
            case '=':
                return $minn !== null && abs($v - $minn) < 1e-6;
            case 'between':
                return $minn !== null && $maxx !== null && $v >= $minn && $v <= $maxx;
            default:
                return false;
        }
    }

    private static function tierThresholdKey(array $t): float
    {
        $op = strtolower((string) ($t['operator'] ?? ''));
        if ($op === 'always') {
            return -INF;
        }
        if ($op === 'between') {
            $x = isset($t['value_max']) && $t['value_max'] !== null ? (float) $t['value_max'] : null;
            $n = isset($t['value_min']) && $t['value_min'] !== null ? (float) $t['value_min'] : 0.0;

            return $x !== null ? $x : $n;
        }

        return isset($t['value_min']) && $t['value_min'] !== null ? (float) $t['value_min'] : 0.0;
    }

    /** @param  array<int, array<string, mixed>>  $tiers */
    private static function bestSurchargeFromTiers(array $tiers, float $metricVal): ?float
    {
        if ($tiers === []) {
            return null;
        }
        $matched = [];
        foreach ($tiers as $t) {
            $op = (string) ($t['operator'] ?? '');
            $vmin = isset($t['value_min']) && $t['value_min'] !== null ? (float) $t['value_min'] : null;
            $vmax = isset($t['value_max']) && $t['value_max'] !== null ? (float) $t['value_max'] : null;
            if (self::tierMatches($op, $vmin, $vmax, $metricVal)) {
                $matched[] = $t;
            }
        }
        if ($matched === []) {
            return null;
        }
        $best = $matched[0];
        $bestK = self::tierThresholdKey($best);
        for ($j = 1; $j < count($matched); $j++) {
            $k2 = self::tierThresholdKey($matched[$j]);
            if ($k2 > $bestK) {
                $best = $matched[$j];
                $bestK = $k2;
            }
        }

        return (float) ($best['surcharge'] ?? 0);
    }

    private static function bothEdgesSatisfyTier(string $op, ?float $vmin, ?float $vmax, float $a, float $b): bool
    {
        $op = strtolower($op);
        if ($op === 'always') {
            return true;
        }

        return self::tierMatches($op, $vmin, $vmax, $a) && self::tierMatches($op, $vmin, $vmax, $b);
    }

    private static function anyTwoSidesMatchTier(float $L, float $W, float $D, array $tier): bool
    {
        $op = (string) ($tier['operator'] ?? '');
        $vmin = isset($tier['value_min']) && $tier['value_min'] !== null ? (float) $tier['value_min'] : null;
        $vmax = isset($tier['value_max']) && $tier['value_max'] !== null ? (float) $tier['value_max'] : null;

        return self::bothEdgesSatisfyTier($op, $vmin, $vmax, $L, $W)
            || self::bothEdgesSatisfyTier($op, $vmin, $vmax, $W, $D)
            || self::bothEdgesSatisfyTier($op, $vmin, $vmax, $D, $L);
    }

    private static function allThreeSidesMatchTier(float $L, float $W, float $D, array $tier): bool
    {
        $op = strtolower((string) ($tier['operator'] ?? ''));
        if ($op === 'always') {
            return true;
        }
        $vmin = isset($tier['value_min']) && $tier['value_min'] !== null ? (float) $tier['value_min'] : null;
        $vmax = isset($tier['value_max']) && $tier['value_max'] !== null ? (float) $tier['value_max'] : null;

        return self::tierMatches($op, $vmin, $vmax, $L)
            && self::tierMatches($op, $vmin, $vmax, $W)
            && self::tierMatches($op, $vmin, $vmax, $D);
    }

    /** @param  array<int, array<string, mixed>>  $tiers */
    private static function bestSurchargeFromTiersMultiSide(string $mode, float $L, float $W, float $D, array $tiers): ?float
    {
        if ($tiers === []) {
            return null;
        }
        $matched = [];
        foreach ($tiers as $t) {
            $ok = $mode === '2_sides'
                ? self::anyTwoSidesMatchTier($L, $W, $D, $t)
                : self::allThreeSidesMatchTier($L, $W, $D, $t);
            if ($ok) {
                $matched[] = $t;
            }
        }
        if ($matched === []) {
            return null;
        }
        $best = $matched[0];
        $bestK = self::tierThresholdKey($best);
        for ($j = 1; $j < count($matched); $j++) {
            $k2 = self::tierThresholdKey($matched[$j]);
            if ($k2 > $bestK) {
                $best = $matched[$j];
                $bestK = $k2;
            }
        }

        return (float) ($best['surcharge'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $block
     * @return float block surcharge subtotal
     */
    private static function evaluateOneBlock(array $block, array $metrics): float
    {
        $Li = $metrics['boxLIn'];
        $Wi = $metrics['boxWIn'];
        $Di = $metrics['boxDIn'];
        $conds = $block['conditions'] ?? [];
        usort($conds, static function ($a, $b) {
            return ($a['condition_priority'] ?? 0) <=> ($b['condition_priority'] ?? 0);
        });
        $op = strtoupper((string) ($block['condition_operator'] ?? 'OR'));

        $surchargeForCondition = function (array $c) use ($Li, $Wi, $Di, $metrics): ?float {
            $ak = strtolower(str_replace('-', '_', (string) ($c['attribute_key'] ?? '')));
            if ($ak === '1_side') {
                $ak = '1_sides';
            }
            $tiers = is_array($c['tiers'] ?? null) ? $c['tiers'] : [];
            if ($ak === '2_sides') {
                return self::bestSurchargeFromTiersMultiSide('2_sides', $Li, $Wi, $Di, $tiers);
            }
            if ($ak === '3_sides') {
                return self::bestSurchargeFromTiersMultiSide('3_sides', $Li, $Wi, $Di, $tiers);
            }
            $mv = self::metricForAttribute($c['attribute_key'] ?? '', $metrics);
            if ($mv === null) {
                return null;
            }

            return self::bestSurchargeFromTiers($tiers, $mv);
        };

        if ($op === 'AND') {
            $sum = 0.0;
            foreach ($conds as $c) {
                $s = $surchargeForCondition($c);
                if ($s === null) {
                    return 0.0;
                }
                $sum += $s;
            }

            return $sum;
        }

        foreach ($conds as $c) {
            $s2 = $surchargeForCondition($c);
            if ($s2 === null) {
                continue;
            }

            return $s2;
        }

        return 0.0;
    }
}
