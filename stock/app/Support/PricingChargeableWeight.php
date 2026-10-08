<?php

namespace App\Support;

/**
 * Chargeable weight for base courier rate band (max of volumetric vs box weight).
 */
final class PricingChargeableWeight
{
    private const KG_TO_LBS = 2.20462;

    /**
     * Weight passed to changeCourierRate: lbs for US/Canada, kg for all other destinations.
     *
     * @param  array<string, mixed>  $pricingData
     */
    public static function forBaseCourierRate(array $pricingData, string $destinationCountry, float $volumetricWeight): float
    {
        $country = strtolower(trim($destinationCountry));
        $volCeiled = (float) ceil($volumetricWeight);
        $boxKg = PricingBoxWeight::resolveKg($pricingData);

        if (in_array($country, ['us', 'canada'], true)) {
            $boxLbs = (float) ceil($boxKg * self::KG_TO_LBS);

            return max($volCeiled, $boxLbs);
        }

        $boxKgCeiled = (float) ceil($boxKg);

        return max($volCeiled, $boxKgCeiled);
    }
}
