<?php

namespace App\Support;

/**
 * Volumetric weight for pricing rows: US/Canada in lbs, all other destinations in kg.
 */
final class PricingVolumetricWeight
{
    public static function isLbsDestination(string $destination): bool
    {
        return in_array(strtolower(trim($destination)), ['us', 'canada'], true);
    }

    /**
     * Raw volumetric before ceil (lbs for US/Canada, kg for others).
     *
     * @param  array<string, mixed>  $pricingData
     * @param  array<string, mixed>  $settingData
     */
    public static function raw(array $pricingData, array $settingData, string $destination): float
    {
        $country = strtolower(trim($destination));
        $width = (float) ($pricingData['boxwidth'] ?? 0);
        $height = (float) ($pricingData['boxheight'] ?? 0);
        $depth = (float) ($pricingData['boxdepth'] ?? 0);

        if ($width <= 0 || $height <= 0 || $depth <= 0) {
            return 0.0;
        }

        if ($country === 'us' || $country === 'canada') {
            $volumetricUnit = ($country === 'us')
                ? (float) ($settingData['us_volumetric_weight_lbs'] ?? 166)
                : (float) ($settingData['canada_volumetric_weight_lbs'] ?? 166);
            if ($volumetricUnit <= 0) {
                $volumetricUnit = 166;
            }
            $wIn = (int) ceil($width / 2.54);
            $hIn = (int) ceil($height / 2.54);
            $dIn = (int) ceil($depth / 2.54);

            return ($wIn * $hIn * $dIn) / $volumetricUnit;
        }

        if ($country === 'eu') {
            $volumetricUnit = (float) ($settingData['eu_volumetric_weight_kg'] ?? 5000);
        } elseif ($country === 'india') {
            $volumetricUnit = (float) ($settingData['india_volumetric_weight_kg'] ?? 5000);
        } elseif ($country === 'australia') {
            $volumetricUnit = (float) ($settingData['australia_volumetric_weight_kg'] ?? 5000);
        } elseif ($country === 'california') {
            $volumetricUnit = (float) ($settingData['california_volumetric_weight_kg'] ?? 5000);
        } elseif ($country === 'uk') {
            $volumetricUnit = (float) ($settingData['uk_volumetric_weight_kg'] ?? 5000);
        } else {
            $volumetricUnit = (float) ($settingData['volWt'] ?? 5000);
        }
        if ($volumetricUnit <= 0) {
            $volumetricUnit = 5000;
        }

        return ($width * $height * $depth) / $volumetricUnit;
    }

    /**
     * Stored volWt: ceil(raw) — matches pricing UI roundUpVolWt.
     *
     * @param  array<string, mixed>  $pricingData
     * @param  array<string, mixed>  $settingData
     */
    public static function forDestination(array $pricingData, array $settingData, string $destination): float
    {
        $raw = self::raw($pricingData, $settingData, $destination);
        if ($raw <= 0) {
            return 0.0;
        }

        return (float) ceil($raw);
    }
}
