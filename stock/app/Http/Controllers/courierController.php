<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\product;
use App\Services\CourierBlockEngineCalculator;
use App\Support\PricingChargeableWeight;
use App\Support\DestinationPricingPolicy;
use App\Services\UsCourierRuleService;
use App\Support\PricingBoxWeight;
use App\tempProduct;
use Illuminate\Http\Request;
use App\courier;
use App\CourierWeightRateTier;
use App\pricingTable;
use App\setting;
use App\Support\CourierWeightRateBands;
use App\Support\CourierSnapshotWriter;
use App\CourierSnapshot;

class courierController extends Controller
{
    public function index()
    {
    	$courier = courier::all();
      return view('courier/index', ['courier'=>$courier]);
    }

    /**
     * Separate page: per-courier column last-updated + snapshot list links.
     * Does not change add/edit courier screens.
     */
    public function history()
    {
        $couriers = courier::orderBy('country')->orderBy('name')->get();
        $matrix = CourierSnapshotWriter::columnLastUpdatedMatrix($couriers);
        $trackedColumns = CourierSnapshotWriter::TRACKED_COLUMNS;

        return view('courier.history', [
            'matrix' => $matrix,
            'trackedColumns' => $trackedColumns,
        ]);
    }

    /**
     * Snapshot list for one courier.
     */
    public function historyCourier($id)
    {
        $courier = courier::findOrFail($id);
        $snapshots = CourierSnapshot::where('courier_id', $courier->id)
            ->orderByDesc('id')
            ->get();

        $matrixRow = CourierSnapshotWriter::columnLastUpdatedMatrix(collect([$courier]));
        $columnLastUpdated = $matrixRow[0]['columns'] ?? [];

        return view('courier.history_courier', [
            'courier' => $courier,
            'snapshots' => $snapshots,
            'trackedColumns' => CourierSnapshotWriter::TRACKED_COLUMNS,
            'columnLastUpdated' => $columnLastUpdated,
        ]);
    }

    /**
     * One snapshot: what changed (from → to) plus full state at that time.
     */
    public function historySnapshot($id, $snapshotId)
    {
        $courier = courier::findOrFail($id);
        $snapshot = CourierSnapshot::where('courier_id', $courier->id)
            ->where('id', $snapshotId)
            ->firstOrFail();

        return view('courier.history_snapshot', [
            'courier' => $courier,
            'snapshot' => $snapshot,
            'changedFields' => $snapshot->changed_fields,
            'fullState' => $snapshot->snapshot,
            'trackedColumns' => CourierSnapshotWriter::TRACKED_COLUMNS,
        ]);
    }

    public function create()
    {
        $countriesArray = Common::getCountries();
        $measurementsArray = Common::getMeasurements();
        $getMeasurementsUnits = Common::getMeasurementsUnits();
        return view('courier/create', [
            'countriesArray' => $countriesArray,
            'measurementsArray' => $measurementsArray,
            'getMeasurementsUnits' => $getMeasurementsUnits,
            'measurementUnitMap' => Common::getMeasurementUnitMap(),
            'usRuleBlocksArray' => [],
        ]);
    }

  public function store(Request $request)
{
    $customCondition = [];
    $incValue = (int) $request->incValue;

    if ($incValue > 0) {

        for ($i = 0; $i < $incValue; $i++) {

            if (!isset($request->attribute[$i])) {
                continue;
            }

            $customCondition[] = [
                'attribute'      => $request->attribute[$i] ?? null,
                'attribute_val'  => $request->attribute_val[$i] ?? [],
                'popup_price'    => $request->popup_price[$i] ?? [],
            ];
        }
    }

    $is_default = $request->has('is_default') ? 1 : 0;

    $tiers = $this->parseWeightRateTiers($request);
    [$legacyFixedWeight, $legacyRatePerKg] = $this->legacyValuesFromTiers($tiers, $request);

    $c = courier::create([
        'name'                 => $request->name,
        'rate'                 => $request->rate,
        'fixed_rate_weight'    => $legacyFixedWeight,
        'rate_per_kg'          => $legacyRatePerKg,
        'fuel_charge_percent'  => $request->fuel_charge_percent,
        'country'              => $request->country,
        'is_default'           => $is_default,
        'custom_condition'     => json_encode($customCondition),
    ]);

    $this->saveWeightRateTiers((int) $c->id, $tiers);

    UsCourierRuleService::syncBlocksFromRequest(
        (int) $c->id,
        (string) $request->country,
        $request->input('us_rule_blocks_json'),
        $request->input('uk_rule_blocks_json')
    );

    CourierSnapshotWriter::capture($c->fresh(), 'create');

    return redirect('/courier')->with('success', 'Courier added successfully.');
}

public function view($id)
{
    $courier = courier::findOrFail($id);

    $raw = json_decode($courier->custom_condition, true) ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }
    $customCondition = [];
    $toArray = function ($v) {
        if (is_array($v)) {
            return $v;
        }
        if (is_scalar($v) && $v !== '') {
            return [$v];
        }
        return [];
    };

    foreach ($raw as $cond) {
        if (!is_array($cond)) {
            continue;
        }
        // Support old DB format: [0=>attribute, 1=>attribute_val, 2=>popup_price]
        if (isset($cond[0]) || array_key_exists(0, $cond)) {
            $customCondition[] = [
                'attribute'     => $cond[0] ?? null,
                'attribute_val' => $toArray($cond[1] ?? null),
                'popup_price'   => $toArray($cond[2] ?? null),
            ];
        } else {
            // New format: associative keys
            $customCondition[] = [
                'attribute'     => $cond['attribute'] ?? null,
                'attribute_val' => $toArray($cond['attribute_val'] ?? null),
                'popup_price'   => $toArray($cond['popup_price'] ?? null),
            ];
        }
    }

    $cc = strtoupper((string) ($courier->country ?? ''));
    $usRuleBlocksArray = [];
    if (UsCourierRuleService::tablesExist() && UsCourierRuleService::supportsBlockEngine($cc)) {
        $usRuleBlocksArray = UsCourierRuleService::loadBlocksArray((int) $courier->id, $cc);
    }

    return view('courier.view', [
        'courier'              => $courier,
        'countriesArray'       => Common::getCountries(),
        'measurementsArray'    => Common::getMeasurements(),
        'getMeasurementsUnits' => Common::getMeasurementsUnits(),
        'measurementUnitMap'   => Common::getMeasurementUnitMap(),
        'customCondition'      => $customCondition,
        'usRuleBlocksArray'    => $usRuleBlocksArray,
        'weightRateTiers'      => CourierWeightRateBands::forCourier($courier),
    ]);
}

public function update(Request $request, $id)
{
    $customCondition = [];
    $incValue = (int) $request->incValue;

    for ($i = 0; $i < $incValue; $i++) {
        if (!isset($request->attribute[$i])) continue;

        $customCondition[] = [
            'attribute'     => $request->attribute[$i],
            'attribute_val' => $request->attribute_val[$i] ?? [],
            'popup_price'   => $request->popup_price[$i] ?? [],
        ];
    }

    $courier = courier::findOrFail($id);

    $tiers = $this->parseWeightRateTiers($request);
    [$legacyFixedWeight, $legacyRatePerKg] = $this->legacyValuesFromTiers($tiers, $request);

    $courier->update([
        'name'                => $request->name,
        'rate'                => $request->rate,
        'fixed_rate_weight'   => $legacyFixedWeight,
        'rate_per_kg'         => $legacyRatePerKg,
        'fuel_charge_percent' => $request->fuel_charge_percent,
        'country'             => $request->country,
        'is_default'          => $request->has('is_default') ? 1 : 0,
        'custom_condition'    => json_encode($customCondition)
    ]);

    $this->saveWeightRateTiers((int) $courier->id, $tiers);

    UsCourierRuleService::syncBlocksFromRequest(
        (int) $courier->id,
        (string) $request->country,
        $request->input('us_rule_blocks_json'),
        $request->input('uk_rule_blocks_json')
    );

    CourierSnapshotWriter::capture($courier->fresh(), 'update');

    return redirect('/courier')->with('success', 'Courier updated successfully.');
}



    /**
     * Weight-band tier rows from the courier form (tier_weight_from[] / tier_weight_to[] / tier_rate[]).
     * Falls back to legacy single fixed_rate_weight / rate_per_kg inputs when no tier rows were posted.
     *
     * @return array<int, array{from: float, to: float|null, rate: float}>
     */
    private function parseWeightRateTiers(Request $request): array
    {
        $froms = $request->input('tier_weight_from', []);
        $tos = $request->input('tier_weight_to', []);
        $rates = $request->input('tier_rate', []);

        $tiers = [];
        if (is_array($froms)) {
            foreach ($froms as $i => $from) {
                $rate = $rates[$i] ?? null;
                if (($from === null || $from === '') && ($rate === null || $rate === '')) {
                    continue;
                }
                $to = $tos[$i] ?? null;
                $tiers[] = [
                    'from' => (float) $from,
                    'to' => ($to === null || $to === '') ? null : (float) $to,
                    'rate' => (float) $rate,
                ];
            }
        }

        if ($tiers === []) {
            $legacyFrom = $request->input('fixed_rate_weight');
            $legacyRate = $request->input('rate_per_kg');
            if ($legacyFrom !== null || $legacyRate !== null) {
                $tiers[] = [
                    'from' => (float) $legacyFrom,
                    'to' => null,
                    'rate' => (float) $legacyRate,
                ];
            }
        }

        usort($tiers, fn ($a, $b) => $a['from'] <=> $b['from']);

        return $tiers;
    }

    /**
     * Legacy courier columns kept in sync so non-tier-aware consumers keep working:
     * fixed weight = lowest band start; rate = rate of the highest band.
     *
     * @param  array<int, array{from: float, to: float|null, rate: float}>  $tiers
     * @return array{0: float|null, 1: float|null}
     */
    private function legacyValuesFromTiers(array $tiers, Request $request): array
    {
        if ($tiers === []) {
            return [$request->input('fixed_rate_weight'), $request->input('rate_per_kg')];
        }

        $fixedWeight = $tiers[0]['from'];
        $highest = end($tiers);

        return [$fixedWeight, $highest['rate']];
    }

    /**
     * @param  array<int, array{from: float, to: float|null, rate: float}>  $tiers
     */
    private function saveWeightRateTiers(int $courierId, array $tiers): void
    {
        if (! CourierWeightRateBands::tableExists()) {
            return;
        }

        CourierWeightRateTier::where('courier_id', $courierId)->delete();

        foreach ($tiers as $i => $tier) {
            CourierWeightRateTier::create([
                'courier_id' => $courierId,
                'weight_from' => $tier['from'],
                'weight_to' => $tier['to'],
                'rate_per_unit' => $tier['rate'],
                'sort_order' => $i + 1,
                'is_active' => 1,
            ]);
        }

        CourierWeightRateBands::flushCache();
    }

    public function delete($id)
    {
        $courier = courier::find($id);

            if ($courier) {
                UsCourierRuleService::deleteAllRulesForCourier((int) $courier->id);
                if (CourierWeightRateBands::tableExists()) {
                    CourierWeightRateTier::where('courier_id', $courier->id)->delete();
                }
                $courier->delete();
                return redirect('/courier')->with('success', 'Courier was Delete.');
            } else {
                return redirect('courier')->with('danger', 'Failed to Delete.');
            }
    } 
    
    public function updateBulkCourierPrice($id)
    {
        $courier = courier::find($id);
        if (! $courier) {
            return redirect('/courier')->with('danger', 'Courier not found.');
        }

        $courierCountryNorm = strtolower(trim((string) $courier->country));
        $pricingRows = pricingTable::whereRaw('LOWER(TRIM(destination)) = ?', [$courierCountryNorm])->get();

        $firstSetting = setting::first();
        $getSettingData = $firstSetting ? $firstSetting->toArray() : [];
        $blockCountryUpper = UsCourierRuleService::normalizeCountry((string) $courier->country);

        foreach ($pricingRows as $pricingRow) {
            $row = $pricingRow->toArray();
            $destForVol = (string) ($row['destination'] ?? $courier->country);
            $volumetricWeight = $this->changeVolumetricUnit($row, $getSettingData, $destForVol);
            $chargeableWeight = PricingChargeableWeight::forBaseCourierRate($row, $destForVol, (float) $volumetricWeight);
            $courierFinalRate = $this->changeCourierRate($courier, $chargeableWeight);

            $courierCost = (float) $courierFinalRate;

            if (UsCourierRuleService::tablesExist() && UsCourierRuleService::supportsBlockEngine($blockCountryUpper)) {
                $payload = UsCourierRuleService::buildPayload((int) $courier->id, $blockCountryUpper);
                if ($payload !== null) {
                    $boxWtKg = (float) ($row['boxWt'] ?? 0);
                    if ($boxWtKg <= 0 && ! empty($row['product_id'])) {
                        $productType = (int) ($row['productType'] ?? 1);
                        $prod = $productType === 1
                            ? product::find($row['product_id'])
                            : tempProduct::find($row['product_id']);
                        if ($prod && isset($prod->gross_weight)) {
                            $boxWtKg = (float) $prod->gross_weight;
                        }
                    }
                    $volWtLbs = (float) ceil((float) $this->computeVolWtLbsForCourier($row, $getSettingData, $courierCountryNorm));
                    $engine = $payload['engine'] ?? '';
                    if ($engine === 'us_blocks_v1') {
                        $volKgForMetrics = (float) ceil((float) $this->changeVolumetricUnit($row, $getSettingData, 'uk'));
                        $courierCost = CourierBlockEngineCalculator::finalCost(
                            (float) $courierFinalRate,
                            $payload,
                            (float) ($row['boxwidth'] ?? 0),
                            (float) ($row['boxheight'] ?? 0),
                            (float) ($row['boxdepth'] ?? 0),
                            $volKgForMetrics,
                            $volWtLbs,
                            $boxWtKg
                        );
                    } elseif ($engine === 'uk_blocks_v1') {
                        $volKgForMetrics = (float) ceil((float) $this->changeVolumetricUnit($row, $getSettingData, $courierCountryNorm));
                        $courierCost = CourierBlockEngineCalculator::finalCost(
                            (float) $courierFinalRate,
                            $payload,
                            (float) ($row['boxwidth'] ?? 0),
                            (float) ($row['boxheight'] ?? 0),
                            (float) ($row['boxdepth'] ?? 0),
                            $volKgForMetrics,
                            0.0,
                            $boxWtKg
                        );
                    }
                } elseif (! empty($courier->custom_condition)) {
                    $rowCalc = $this->rowWithVolForLegacySurcharge($row, $getSettingData, $destForVol);
                    $courierCost = $this->calCulateSurchargeCustomCondition($courier->custom_condition, $courierFinalRate, $rowCalc, $volumetricWeight, $courierCountryNorm);
                }
            } elseif (! empty($courier->custom_condition)) {
                $rowCalc = $this->rowWithVolForLegacySurcharge($row, $getSettingData, $destForVol);
                $courierCost = $this->calCulateSurchargeCustomCondition($courier->custom_condition, $courierFinalRate, $rowCalc, $volumetricWeight, $courierCountryNorm);
            }

            if ($courierCountryNorm === 'uk' && $courierCost > 100) {
                $courierCost = 115;
            }

            $isIndia = DestinationPricingPolicy::isIndia($row['destination'] ?? null);
            $deliveryBase = $isIndia
                ? floatval($row['indiaFinalCost'] ?? 0)
                : floatval($row['finalPrice'] ?? 0);
            $deliveryCost = $deliveryBase + floatval($courierCost);
            $deliveryCost = floatval(number_format($deliveryCost, 2));
            $adjustment = $isIndia ? 0 : ($row['adjustment'] ?? 0);
            $adjustmentOutput = ($deliveryCost * $adjustment) / 100;
            $finalAdjustOutput = floatval($deliveryCost) + floatval($adjustmentOutput);
            $newDeliveryCost = round($finalAdjustOutput);
            pricingTable::where('id', $row['id'])->update([
                'courierCost' => $courierCost,
                'deliveryCost' => $deliveryCost,
                'newDelCost' => $newDeliveryCost,
                'adjustment' => $isIndia ? 0 : ($row['adjustment'] ?? 0),
            ]);
        }

        return redirect('/courier')->with('success', 'Bulk courier prices updated.');
    }

    /**
     * Volumetric lbs (pricing view): cm → inch edges ceil, in³ ÷ divisor — same as BulkBuyerController::computeVolWtLbs.
     */
    private function computeVolWtLbsForCourier(array $pricingData, array $settingData, string $country): float
    {
        $width = (float) ($pricingData['boxwidth'] ?? 0);
        $height = (float) ($pricingData['boxheight'] ?? 0);
        $depth = (float) ($pricingData['boxdepth'] ?? 0);
        if ($width <= 0 || $height <= 0 || $depth <= 0) {
            return (float) ($pricingData['volWt_lbs'] ?? 0);
        }
        $country = strtolower(trim($country));
        $divisor = 166;
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

    /**
     * Ensure legacy kg rules see a consistent volumetric kg (matches pricing when row volWt is stale).
     */
    private function rowWithVolForLegacySurcharge(array $row, array $getSettingData, string $destForVol): array
    {
        $destNorm = strtolower(trim($destForVol));
        $rowCalc = $row;
        if (in_array($destNorm, ['us', 'canada'], true)) {
            $rowCalc['volWt'] = (float) ($row['volWt'] ?? 0);
        } else {
            $rowCalc['volWt'] = (float) ceil((float) $this->changeVolumetricUnit($row, $getSettingData, $destNorm));
        }

        return $rowCalc;
    }

    /**
     * Base courier charge + weight-band overage (highest-band-only) + fuel %.
     * $volWt must match the courier country: chargeable weight (max vol vs box), lbs or kg by destination.
     */
    function changeCourierRate($courierData, $volWt)
    {
        return CourierWeightRateBands::baseRateWithFuel($courierData, (float) $volWt);
    }

    /**
     * Volumetric weight for pricing row — US/Canada lbs, else kg by destination.
     */
    function changeVolumetricUnit($pricingData, $settingData, $country)
    {
        return \App\Support\PricingVolumetricWeight::raw($pricingData, $settingData, (string) $country);
    }

    /**
     * Legacy custom_condition surcharges — same shapes as pricing/view.blade.php (object JSON) and old [type, val, price] rows.
     */
    function calCulateSurchargeCustomCondition($custom_conditions, $courierCost, $pricingData, $volumetricActualUnit, ?string $destinationCountry = null)
    {
        if (! $custom_conditions) {
            return $courierCost;
        }

        $raw = is_string($custom_conditions) ? $custom_conditions : json_encode($custom_conditions);
        $customConditionsArray = json_decode($raw, true);
        if (! is_array($customConditionsArray) || $customConditionsArray === []) {
            return $courierCost;
        }

        $hasObjectShape = false;
        foreach ($customConditionsArray as $c) {
            if (is_array($c) && isset($c['attribute'])) {
                $hasObjectShape = true;
                break;
            }
        }

        if ($hasObjectShape) {
            return $this->calCulateSurchargeCustomConditionObjectFormat(
                $customConditionsArray,
                $courierCost,
                $pricingData,
                $volumetricActualUnit,
                $destinationCountry
            );
        }

        return $this->calCulateSurchargeCustomConditionLegacyNumericFormat($customConditionsArray, $courierCost, $pricingData, $volumetricActualUnit);
    }

    private function calCulateSurchargeCustomConditionObjectFormat(
        array $customConditionsArray,
        $courierCost,
        $pricingData,
        $volumetricActualUnit,
        ?string $destinationCountry = null
    ): float {
        $boxheight = (float) ($pricingData['boxheight'] ?? 0);
        $boxwidth = (float) ($pricingData['boxwidth'] ?? 0);
        $boxdepth = (float) ($pricingData['boxdepth'] ?? 0);
        $volWt = (float) ($pricingData['volWt'] ?? 0);
        $volWt_lbs = (float) $volumetricActualUnit;

        $boxWt = PricingBoxWeight::resolveForBoxCondition($pricingData, $destinationCountry);

        $calculatedCosts = [];
        $boxSurcharge = 0.0;

        foreach ($customConditionsArray as $condition) {
            if (! is_array($condition) || ! isset($condition['attribute'], $condition['attribute_val'], $condition['popup_price'])) {
                continue;
            }

            $type = $condition['attribute'];
            $vals = is_array($condition['attribute_val']) ? $condition['attribute_val'] : [$condition['attribute_val']];
            $prices = is_array($condition['popup_price']) ? $condition['popup_price'] : [$condition['popup_price']];
            $val = (float) ($vals[0] ?? 0);
            $price = (float) ($prices[0] ?? 0);
            $calc = 0.0;

            if ($type === '1_sides') {
                if ($boxheight > $val || $boxwidth > $val || $boxdepth > $val) {
                    $calc = $price + $courierCost;
                }
            } elseif ($type === '2_sides') {
                if (($boxheight > $val && $boxwidth > $val) || ($boxwidth > $val && $boxdepth > $val) || ($boxdepth > $val && $boxheight > $val)) {
                    $calc = $price + $courierCost;
                }
            } elseif ($type === '3_sides') {
                if ($boxheight > $val && $boxwidth > $val && $boxdepth > $val) {
                    $calc = $price + $courierCost;
                }
            } elseif ($type === 'kg') {
                if ($volWt > $val) {
                    $calc = $price + $courierCost;
                }
            } elseif ($type === 'lbs') {
                if ($volWt_lbs > $val) {
                    $calc = $price + $courierCost;
                }
            } elseif ($type === 'box') {
                $maxMatchPrice = 0.0;
                foreach ($vals as $index => $bVal) {
                    if ($bVal === null || $bVal === '') {
                        continue;
                    }
                    $requiredWeight = (float) $bVal;
                    $bPrice = (float) ($prices[$index] ?? 0);
                    if ($boxWt >= $requiredWeight && $bPrice > $maxMatchPrice) {
                        $maxMatchPrice = $bPrice;
                    }
                }
                if ($maxMatchPrice > 0) {
                    $boxSurcharge = $maxMatchPrice;
                }
            }

            if ($calc > 0) {
                $calculatedCosts[] = $calc;
            }
        }

        $surCharge = count($calculatedCosts) > 0 ? max($calculatedCosts) : (float) $courierCost;

        return $surCharge + $boxSurcharge;
    }

    private function calCulateSurchargeCustomConditionLegacyNumericFormat(array $customConditionsArray, $courierCost, $pricingData, $volumetricActualUnit): float
    {
        $surCharge = 0.0;
        $isslideCondition = true;
        $issKgLbsCondition = true;

        $boxheight = (float) ($pricingData['boxheight'] ?? 0);
        $boxwidth = (float) ($pricingData['boxwidth'] ?? 0);
        $boxdepth = (float) ($pricingData['boxdepth'] ?? 0);
        $volWt = (float) ($pricingData['volWt'] ?? 0);
        $volWt_lbs = (float) $volumetricActualUnit;

        if (! ($boxheight && $boxwidth && $boxdepth)) {
            return (float) $courierCost;
        }

        foreach ($customConditionsArray as $customCondition) {
            if (! is_array($customCondition)) {
                continue;
            }
            if (! array_key_exists(0, $customCondition) || isset($customCondition['attribute'])) {
                continue;
            }

            if ($surCharge > 0) {
                $courierCost = $surCharge;
            }
            $customConditionsMeasurements = (float) ($customCondition[1] ?? 0);
            $customConditionsCost = (float) ($customCondition[2] ?? 0);
            $kind = $customCondition[0] ?? '';

            if ($kind === '1_sides' && $isslideCondition) {
                if ($boxheight > $customConditionsMeasurements || $boxwidth > $customConditionsMeasurements || $boxdepth > $customConditionsMeasurements) {
                    $surCharge = $customConditionsCost + $courierCost;
                    $isslideCondition = false;
                }
            } elseif ($kind === '2_sides' && $isslideCondition) {
                if (($boxheight > $customConditionsMeasurements && $boxwidth > $customConditionsMeasurements) || ($boxwidth > $customConditionsMeasurements && $boxdepth > $customConditionsMeasurements) || ($boxdepth > $customConditionsMeasurements && $boxheight > $customConditionsMeasurements)) {
                    $surCharge = $customConditionsCost + $courierCost;
                    $isslideCondition = false;
                }
            } elseif ($kind === '3_sides' && $isslideCondition) {
                if ($boxheight > $customConditionsMeasurements && $boxwidth > $customConditionsMeasurements && $boxdepth > $customConditionsMeasurements) {
                    $surCharge = $customConditionsCost + $courierCost;
                    $isslideCondition = false;
                }
            }
            if ($kind === 'kg' && $issKgLbsCondition) {
                if ($volWt > $customConditionsMeasurements) {
                    $surCharge = $customConditionsCost + $courierCost;
                    $issKgLbsCondition = false;
                }
            }
            if ($kind === 'lbs' && $issKgLbsCondition) {
                if ($volWt_lbs > $customConditionsMeasurements) {
                    $surCharge = $customConditionsCost + $courierCost;
                    $issKgLbsCondition = false;
                }
            }
        }

        return $surCharge > 0 ? $surCharge : (float) $courierCost;
    }

   
}
