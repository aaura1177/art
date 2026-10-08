<?php

namespace App\Support;

/**
 * Destination-specific pricing rules.
 *
 * India is intentionally isolated here so every non-India destination keeps
 * its existing pricing path unchanged.
 */
final class DestinationPricingPolicy
{
    public static function normalize(?string $destination): string
    {
        return strtolower(trim((string) $destination));
    }

    public static function isIndia(?string $destination): bool
    {
        return self::normalize($destination) === 'india';
    }

    /**
     * Apply the canonical India-only pricing chain to model attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function applyIndia(array $attributes): array
    {
        if (! self::isIndia($attributes['destination'] ?? null)) {
            return $attributes;
        }

        $stack = self::indiaCostStack($attributes);
        $indiaFinalCost = $stack['indiaFinalCost'];
        $courierCost = round((float) ($attributes['courierCost'] ?? 0), 2);
        $deliveryCost = round($indiaFinalCost + $courierCost, 2);

        return array_merge($attributes, $stack, [
            'currency' => '₹',
            'converRate' => 0,
            'fobINCost' => 0,
            'tariff_percent' => 0,
            'final_fob_in_cost' => 0,
            'shippingCost2' => 0,
            'StorageCost' => 0,
            'adminCost2' => 0,
            'qualityAssurance' => 0,
            'landedCost' => $indiaFinalCost,
            'adminProfit' => 0,
            'adminPrice' => $indiaFinalCost,
            'finalPricePer' => 0,
            'finalPrice' => $indiaFinalCost,
            'courierCost' => $courierCost,
            'deliveryCost' => $deliveryCost,
            'adjustment' => 0,
            'newDelCost' => (int) round($deliveryCost),
        ]);
    }

    /**
     * India's own Cost Price / Admin Cost / Final Cost chain. Percentages fall
     * back to the common ones so a row never lands on an empty India stack.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, float>
     */
    public static function indiaCostStack(array $attributes): array
    {
        $indiaCostPrice = self::indiaCostPrice($attributes);
        $adminPercent = self::percent($attributes, 'indiaAdminCostPercent', 'adminCostPercent');
        $profitPercent = self::percent($attributes, 'indiaProfitPercent', 'profitPercent');
        $indiaAdminCost = round($indiaCostPrice * (1 + ($adminPercent / 100)), 2);

        return [
            'indiaShippingCost' => round((float) ($attributes['indiaShippingCost'] ?? 0), 2),
            'indiaCostPrice' => $indiaCostPrice,
            'indiaAdminCostPercent' => $adminPercent,
            'indiaAdminCost' => $indiaAdminCost,
            'indiaProfitPercent' => $profitPercent,
            'indiaFinalCost' => round($indiaAdminCost * (1 + ($profitPercent / 100)), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function percent(array $attributes, string $indiaKey, string $commonKey): float
    {
        if (array_key_exists($indiaKey, $attributes)
            && $attributes[$indiaKey] !== null
            && $attributes[$indiaKey] !== '') {
            return (float) $attributes[$indiaKey];
        }

        return (float) ($attributes[$commonKey] ?? 0);
    }

    /**
     * India owns its own INR input stack. Common Cost Price remains untouched
     * for non-India pricing; replace common shipping with India shipping.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function indiaCostPrice(array $attributes): float
    {
        if (array_key_exists('costPrice', $attributes) && $attributes['costPrice'] !== null && $attributes['costPrice'] !== '') {
            $commonWithoutShipping = max(
                0,
                (float) $attributes['costPrice'] - (float) ($attributes['shippingCost'] ?? 0)
            );

            return round(
                $commonWithoutShipping + (float) ($attributes['indiaShippingCost'] ?? 0),
                2
            );
        }

        $parts = [
            'buyingCost', 'tapestryCost', 'fillerCost', 'labourCost',
            'hardwareCost1', 'hardwareCost2', 'hardwareCost3',
            'hardwareCost4', 'hardwareCost5', 'polishCost',
            'wSPackageCost', 'dSPackageCost',
        ];

        $sum = 0.0;
        foreach ($parts as $part) {
            $sum += (float) ($attributes[$part] ?? 0);
        }

        return round($sum + (float) ($attributes['indiaShippingCost'] ?? 0), 2);
    }
}
