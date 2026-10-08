<?php

namespace App\Services;

use App\courier;
use App\pricingTable;
use App\product;
use App\setting;
use App\Support\DestinationPricingPolicy;
use App\Support\PricingChargeableWeight;
use App\tempProduct;
use App\Http\Controllers\courierController;

/**
 * Recalculate courier / delivery costs for a pricing row (matches courierController bulk update).
 */
final class PricingCourierRecalcService
{
    /**
     * @return array{courierCost: float, deliveryCost: float, newDelCost: int, chargeableWeight: float}|null
     */
    public static function recalculateForRow(pricingTable $pricingRow): ?array
    {
        if (empty($pricingRow->courierType)) {
            return null;
        }

        $row = $pricingRow->toArray();
        $destNorm = strtolower(trim((string) ($row['destination'] ?? '')));
        if ($destNorm === '') {
            return null;
        }

        $courier = courier::where('name', $pricingRow->courierType)
            ->whereRaw('LOWER(TRIM(country)) = ?', [$destNorm])
            ->first();

        if (! $courier) {
            return null;
        }

        $firstSetting = setting::first();
        $getSettingData = $firstSetting ? $firstSetting->toArray() : [];
        $controller = app(courierController::class);

        $volumetricWeight = $controller->changeVolumetricUnit($row, $getSettingData, $destNorm);
        $chargeableWeight = PricingChargeableWeight::forBaseCourierRate($row, $destNorm, (float) $volumetricWeight);
        $courierFinalRate = (float) $controller->changeCourierRate($courier, $chargeableWeight);
        $courierCost = $courierFinalRate;

        $blockCountryUpper = UsCourierRuleService::normalizeCountry((string) $courier->country);
        $blockApplied = false;

        if (UsCourierRuleService::tablesExist() && UsCourierRuleService::supportsBlockEngine($blockCountryUpper)) {
            $payload = UsCourierRuleService::buildPayload((int) $courier->id, $blockCountryUpper);
            if ($payload !== null) {
                $blockApplied = true;
                $boxWtKg = self::resolveBoxWtKg($row);
                $volWtLbs = (float) ceil((float) self::computeVolWtLbs($row, $getSettingData, $destNorm));
                $engine = $payload['engine'] ?? '';
                if ($engine === 'us_blocks_v1') {
                    $volKgForMetrics = (float) ceil((float) $controller->changeVolumetricUnit($row, $getSettingData, 'uk'));
                    $courierCost = CourierBlockEngineCalculator::finalCost(
                        $courierFinalRate,
                        $payload,
                        (float) ($row['boxwidth'] ?? 0),
                        (float) ($row['boxheight'] ?? 0),
                        (float) ($row['boxdepth'] ?? 0),
                        $volKgForMetrics,
                        $volWtLbs,
                        $boxWtKg
                    );
                } elseif ($engine === 'uk_blocks_v1') {
                    $volKgForMetrics = (float) ceil((float) $controller->changeVolumetricUnit($row, $getSettingData, $destNorm));
                    $courierCost = CourierBlockEngineCalculator::finalCost(
                        $courierFinalRate,
                        $payload,
                        (float) ($row['boxwidth'] ?? 0),
                        (float) ($row['boxheight'] ?? 0),
                        (float) ($row['boxdepth'] ?? 0),
                        $volKgForMetrics,
                        0.0,
                        $boxWtKg
                    );
                }
            }
        }

        if (! $blockApplied && ! empty($courier->custom_condition)) {
            $rowCalc = self::rowWithVolForLegacySurcharge($row, $getSettingData, $destNorm, $controller);
            $courierCost = (float) $controller->calCulateSurchargeCustomCondition(
                $courier->custom_condition,
                $courierFinalRate,
                $rowCalc,
                $volumetricWeight,
                $destNorm
            );
        }

        if ($destNorm === 'uk' && $courierCost > 100) {
            $courierCost = 115.0;
        }

        $isIndia = DestinationPricingPolicy::isIndia($destNorm);
        $deliveryBase = $isIndia
            ? (float) ($pricingRow->indiaFinalCost ?? 0)
            : (float) $pricingRow->finalPrice;
        $deliveryCost = round($deliveryBase + $courierCost, 2);
        $adjustment = $isIndia ? 0.0 : (float) ($pricingRow->adjustment ?? 0);
        $newDelCost = (int) round($deliveryCost + ($deliveryCost * $adjustment / 100));

        return [
            'courierCost' => round($courierCost, 2),
            'deliveryCost' => $deliveryCost,
            'newDelCost' => $newDelCost,
            'chargeableWeight' => $chargeableWeight,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $settingData
     */
    private static function rowWithVolForLegacySurcharge(array $row, array $settingData, string $destForVol, courierController $controller): array
    {
        $destNorm = strtolower(trim($destForVol));
        $rowCalc = $row;
        if (in_array($destNorm, ['us', 'canada'], true)) {
            $rowCalc['volWt'] = (float) ($row['volWt'] ?? 0);
        } else {
            $rowCalc['volWt'] = (float) ceil((float) $controller->changeVolumetricUnit($row, $settingData, $destNorm));
        }

        return $rowCalc;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function resolveBoxWtKg(array $row): float
    {
        $boxWtKg = (float) ($row['boxWt'] ?? 0);
        if ($boxWtKg > 0) {
            return $boxWtKg;
        }
        if (empty($row['product_id'])) {
            return 0.0;
        }
        $productType = (int) ($row['productType'] ?? 1);
        $prod = $productType === 1 ? product::find($row['product_id']) : tempProduct::find($row['product_id']);
        if ($prod && isset($prod->gross_weight)) {
            return (float) $prod->gross_weight;
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $settingData
     */
    private static function computeVolWtLbs(array $row, array $settingData, string $country): float
    {
        $width = (float) ($row['boxwidth'] ?? 0);
        $height = (float) ($row['boxheight'] ?? 0);
        $depth = (float) ($row['boxdepth'] ?? 0);
        if ($width <= 0 || $height <= 0 || $depth <= 0) {
            return (float) ($row['volWt_lbs'] ?? 0);
        }
        $country = strtolower(trim($country));
        $divisor = 166.0;
        if ($country === 'us') {
            $divisor = (float) ($settingData['us_volumetric_weight_lbs'] ?? 166) ?: 166;
        } elseif ($country === 'canada') {
            $divisor = (float) ($settingData['canada_volumetric_weight_lbs'] ?? 166) ?: 166;
        } else {
            $divisor = (float) ($settingData['volumetric_weight_lbs'] ?? 166) ?: 166;
        }
        $wIn = (int) ceil($width / 2.54);
        $hIn = (int) ceil($height / 2.54);
        $dIn = (int) ceil($depth / 2.54);

        return ($wIn * $hIn * $dIn) / $divisor;
    }
}
