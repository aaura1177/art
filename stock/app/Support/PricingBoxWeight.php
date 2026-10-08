<?php

namespace App\Support;

use App\product;
use App\tempProduct;

class PricingBoxWeight
{
    private const KG_TO_LBS = 2.20462;

    /**
     * Soft box weight in kg: saved pricing.boxWt if > 0, else product gross_weight.
     */
    public static function resolveKg(array $pricingData): float
    {
        $boxWtKg = (float) ($pricingData['boxWt'] ?? 0);
        if ($boxWtKg > 0) {
            return $boxWtKg;
        }

        $productId = $pricingData['product_id'] ?? null;
        if (! $productId) {
            return 0.0;
        }

        $productType = (int) ($pricingData['productType'] ?? 1);
        $prod = $productType === 1 ? product::find($productId) : tempProduct::find($productId);
        if ($prod && isset($prod->gross_weight) && $prod->gross_weight !== null && $prod->gross_weight !== '') {
            return (float) $prod->gross_weight;
        }

        return 0.0;
    }

    /**
     * Weight for legacy box surcharge tiers: kg (UK etc.) or lbs (US / Canada).
     */
    public static function resolveForBoxCondition(array $pricingData, ?string $destinationCountry = null): float
    {
        $kg = self::resolveKg($pricingData);
        $country = strtolower(trim((string) ($destinationCountry ?? ($pricingData['destination'] ?? ''))));

        if (in_array($country, ['us', 'canada'], true)) {
            return $kg * self::KG_TO_LBS;
        }

        return $kg;
    }

    public static function boxConditionUnit(?string $destinationCountry = null): string
    {
        $country = strtolower(trim((string) $destinationCountry));

        return in_array($country, ['us', 'canada'], true) ? 'lbs' : 'kg';
    }
}
