<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-band courier overage pricing (highest-band-only).
 *
 * Bands live in courier_weight_rate_tiers. The lowest band start is the fixed
 * included weight; when chargeable weight exceeds it, the ENTIRE overage from
 * that fixed weight is charged at the rate of the highest band the weight
 * falls into (not progressive per band).
 *
 * A courier with a single open-ended band behaves exactly like the legacy
 * fixed_rate_weight / rate_per_kg pair.
 */
final class CourierWeightRateBands
{
    private static ?bool $tableExists = null;

    /** @var array<int, array<int, array{from: float, to: float|null, rate: float}>> */
    private static array $cache = [];

    public static function tableExists(): bool
    {
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::hasTable('courier_weight_rate_tiers');
            } catch (\Throwable $e) {
                self::$tableExists = false;
            }
        }

        return self::$tableExists;
    }

    /**
     * Active bands for a courier, sorted by weight_from asc.
     * Falls back to a single legacy band (fixed_rate_weight / rate_per_kg) when
     * no tiers exist for the courier.
     *
     * @return array<int, array{from: float, to: float|null, rate: float}>
     */
    public static function forCourier($courierData): array
    {
        if (! $courierData) {
            return [];
        }

        $courierId = (int) ($courierData->id ?? 0);
        if ($courierId > 0 && isset(self::$cache[$courierId])) {
            return self::$cache[$courierId];
        }

        $bands = [];
        if (self::tableExists() && ! empty($courierData->id)) {
            $rows = DB::table('courier_weight_rate_tiers')
                ->where('courier_id', $courierData->id)
                ->where('is_active', 1)
                ->orderBy('weight_from')
                ->get(['weight_from', 'weight_to', 'rate_per_unit']);
            foreach ($rows as $row) {
                $bands[] = [
                    'from' => (float) $row->weight_from,
                    'to' => $row->weight_to !== null ? (float) $row->weight_to : null,
                    'rate' => (float) $row->rate_per_unit,
                ];
            }
        }

        if ($bands === []) {
            $legacyRate = $courierData->rate_per_kg ?? null;
            if ($legacyRate !== null && $legacyRate !== '') {
                $bands[] = [
                    'from' => (float) ($courierData->fixed_rate_weight ?? 0),
                    'to' => null,
                    'rate' => (float) $legacyRate,
                ];
            }
        }

        if ($courierId > 0) {
            self::$cache[$courierId] = $bands;
        }

        return $bands;
    }

    public static function flushCache(): void
    {
        self::$cache = [];
        self::$tableExists = null;
    }

    /**
     * Highest-band-only overage charge.
     * overage = chargeable - lowest band from; rate = highest matching band.
     *
     * @param  array<int, array{from: float, to: float|null, rate: float}>  $bands
     */
    public static function overageCharge(array $bands, float $chargeableWeight): float
    {
        if ($bands === []) {
            return 0.0;
        }

        $fixedWeight = min(array_column($bands, 'from'));
        if ($chargeableWeight <= $fixedWeight) {
            return 0.0;
        }

        // "Over X till Y" bands: a band applies when weight > from. Of all
        // applying bands, the one with the highest start wins ("highest band
        // only") and its rate covers the entire overage from the fixed weight.
        $selected = null;
        foreach ($bands as $band) {
            if ($chargeableWeight <= $band['from']) {
                continue;
            }
            if ($selected === null || $band['from'] > $selected['from']) {
                $selected = $band;
            }
        }

        if ($selected === null) {
            return 0.0;
        }

        return ($chargeableWeight - $fixedWeight) * $selected['rate'];
    }

    /**
     * Base courier cost: rate + highest-band-only overage, then fuel percent.
     * Mirrors the legacy changeCourierRate() signature/behaviour.
     */
    public static function baseRateWithFuel($courierData, float $chargeableWeight): float
    {
        if (! $courierData) {
            return 0.0;
        }

        $rate = (float) $courierData->rate;
        $rate += self::overageCharge(self::forCourier($courierData), $chargeableWeight);

        $fuelCharge = ((float) $courierData->fuel_charge_percent * $rate) / 100;

        return $rate + $fuelCharge;
    }
}
