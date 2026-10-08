<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SettingsOption;
use App\pricingTable;
use App\Helpers\Common;
use Exception;
use App\product;
use App\courier;
use App\setting;
use App\Support\PricingChargeableWeight;
use App\Support\DestinationPricingPolicy;
use App\Support\PricingVolumetricWeight;

class UpdatePriceController extends Controller
{
    private static function computeTariffAdjustedFob(int $fobINCost, float $tariffPercent): int
    {
        $adjusted = $fobINCost + (($fobINCost * $tariffPercent) / 100);
        $fixed2 = number_format((float) $adjusted, 2, '.', '');

        return (int) round((float) $fixed2, 0, PHP_ROUND_HALF_UP);
    }

    public function __construct()
    {

        $this->middleware(['auth','2fa']);
    }

    public function updateCountryConversionRate(Request $request)
    {
        $currencyRequest = $request->all();
        $settingKey = $currencyRequest['select_setting'][0];
        
        // Check if it's a setting model field (wspackaging, dspackaging, ishippingcost)
        $settingModelFields = ['wspackaging', 'dspackaging', 'ishippingcost'];
        
        if (in_array($settingKey, $settingModelFields)) {
            // Update in setting model
            $settingModel = setting::get()->first();
            if ($settingModel) {
                $settingModel->$settingKey = $currencyRequest['price_value'];
                $settingModel->save();
            }
        } else {
            // Update in SettingsOption
            SettingsOption::where('setting_key', $settingKey)->update(['setting_value' => $currencyRequest['price_value']]);
        }
        
        $indiaPercentSettings = ['india_admin_cost_percentage', 'india_profit_percentage'];
        // TEMPORARY: seed the India columns on every pricing row, not just India rows.
        if (!empty($currencyRequest['india_columns_all_rows']) && in_array($settingKey, $indiaPercentSettings, true)) {
            $this->updateIndiaColumnsOnAllRows($settingKey, $currencyRequest['price_value']);
        }

        if (!empty($currencyRequest['update_all'])) {
            $checkCountry =  $this->checkCountry($settingKey);
           
            if ($checkCountry) {
                $pricingTableUpdate = pricingTable::whereRaw('LOWER(TRIM(destination)) = ?', [$checkCountry])->get();
            } else {
                // Global/common settings retain their existing calculation path,
                // but India rows own a separate stack and must not be touched.
                $pricingTableUpdate = pricingTable::where(function ($query) {
                    $query->whereNull('destination')
                        ->orWhereRaw('LOWER(TRIM(destination)) <> ?', ['india']);
                })->get();
            }

            $pricingTableUpdateArray = $pricingTableUpdate->toarray();

            for ($i = 0; $i < COUNT($pricingTableUpdateArray); $i++) {
                if (isset($pricingTableUpdateArray[$i])) {
                    $this->allCost($pricingTableUpdateArray[$i], $settingKey, $currencyRequest['price_value'], $checkCountry);
                }
            }
        }
        
        $message = in_array($settingKey, $settingModelFields) 
            ? 'Setting updated successfully.' 
            : 'Currency updated successfully.';
            
        return redirect('setting')->with('success', $message);
    }

    public function allCost($priceDataArray, $select_setting, $price_value, $checkCountry)
    {
        try {
            if(empty($checkCountry)){
                $checkCountry = $priceDataArray['destination'];
            }
            
            $productDropShopVolume = product::find($priceDataArray['product_id']);
            $dropShipVolume = $productDropShopVolume->dropshipvolume;
         
            switch ($select_setting) {
                case 'india_admin_cost_percentage':
                case 'india_profit_percentage':
                    $this->updateIndiaCostSetting($priceDataArray, $select_setting, $price_value);
                    break;

                case 'profit_percentage':
                        $this->updateAllProfitPercentage($priceDataArray, $price_value);
                    break;

                default:
                        $this->updateAllInrConversionRate($priceDataArray, $price_value, $dropShipVolume, $checkCountry, $select_setting);
                    break;
            }

        } catch (Exception $e) {
            // print_r($e->getMessage());
            // die;
        }
    }

    /**
     * Region slug for SettingsOption keys (shipping_cost_{slug}, storage_cost_{slug}) and scoped bulk updates.
     * Lowercase so it matches destination values case-insensitively (UK, CANADA, California, etc.).
     */
    public function checkCountry($keySetting)
    {
        $map = [
            'uk' => ['inr_to_pound_conversion_rate', 'shipping_cost_uk', 'storage_cost_uk'],
            'us' => ['inr_to_dollar_conversion_rate', 'shipping_cost_us', 'storage_cost_us'],
            'eu' => ['inr_to_euro_conversion_rate', 'shipping_cost_eu', 'storage_cost_eu'],
            'canada' => ['inr_to_canadian_conversion_rate', 'shipping_cost_canada', 'storage_cost_canada'],
            'australia' => ['inr_to_australia_conversion_rate', 'shipping_cost_australia', 'storage_cost_australia'],
            'california' => ['shipping_cost_california', 'storage_cost_california'],
            'india' => ['india_admin_cost_percentage', 'india_profit_percentage'],
        ];
        foreach ($map as $slug => $keys) {
            if (in_array($keySetting, $keys, true)) {
                return $slug;
            }
        }

        return false;
    }

    /**
     * TEMPORARY: write the India cost columns on every pricing row so the India
     * block is populated for rows whose destination is not India yet. Non-India
     * destination totals stay on their own formulas and are never touched here.
     */
    private function updateIndiaColumnsOnAllRows(string $settingKey, $value): int
    {
        $percentField = $settingKey === 'india_admin_cost_percentage'
            ? 'indiaAdminCostPercent'
            : 'indiaProfitPercent';
        $updated = 0;

        pricingTable::query()->orderBy('id')->chunkById(200, function ($rows) use ($percentField, $value, &$updated): void {
            foreach ($rows as $row) {
                $attributes = $row->getAttributes();
                $attributes[$percentField] = (float) $value;

                if (DestinationPricingPolicy::isIndia($row->destination)) {
                    $canonical = DestinationPricingPolicy::applyIndia($attributes);
                    $fields = [
                        'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent',
                        'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost',
                        'currency', 'converRate', 'fobINCost', 'tariff_percent',
                        'final_fob_in_cost', 'shippingCost2', 'StorageCost', 'adminCost2',
                        'qualityAssurance', 'landedCost', 'adminProfit', 'adminPrice',
                        'finalPricePer', 'finalPrice', 'courierCost', 'deliveryCost',
                        'adjustment', 'newDelCost',
                    ];
                    $updateData = array_intersect_key($canonical, array_flip($fields));
                } else {
                    $updateData = DestinationPricingPolicy::indiaCostStack($attributes);
                }

                pricingTable::where('id', $row->id)->update($updateData);
                $updated++;
            }
        });

        return $updated;
    }

    private function updateIndiaCostSetting(array $priceDataArray, string $settingKey, $value): bool
    {
        if (! DestinationPricingPolicy::isIndia($priceDataArray['destination'] ?? null)) {
            return true;
        }

        if ($settingKey === 'india_admin_cost_percentage') {
            $priceDataArray['indiaAdminCostPercent'] = (float) $value;
        } else {
            $priceDataArray['indiaProfitPercent'] = (float) $value;
        }

        $canonical = DestinationPricingPolicy::applyIndia($priceDataArray);
        $fields = [
            'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent',
            'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost',
            'currency', 'converRate', 'fobINCost', 'tariff_percent',
            'final_fob_in_cost', 'shippingCost2', 'StorageCost', 'adminCost2',
            'qualityAssurance', 'landedCost', 'adminProfit', 'adminPrice',
            'finalPricePer', 'finalPrice', 'courierCost', 'deliveryCost',
            'adjustment', 'newDelCost',
        ];

        pricingTable::where('id', $priceDataArray['id'])->update(
            array_intersect_key($canonical, array_flip($fields))
        );

        return true;
    }

    public function updateAllProfitPercentage($priceDataArray, $price_value){
        // India destination percentages are row-owned and are not changed by
        // the global non-destination profit setting.
        if (DestinationPricingPolicy::isIndia($priceDataArray['destination'] ?? null)) {
            return true;
        }

        // Final Cost = adminCost + (adminCost * profitCost) /100;
        $profitCost = $price_value; 
        $adminCost = $priceDataArray['adminCost'];

        $subfinalCost = ($adminCost * $profitCost) / 100;
        $finalCost = floatval($adminCost) + floatval($subfinalCost);

        //FOB India Cost = Final Cost / Conversion Rate
        $converRate = $priceDataArray['converRate'] ?? 0;  //database or setting
        $fobINCost = floatval($finalCost) / floatval($converRate);

        $updatePriceData = ['finalCost' => $finalCost, 'fobINCost' => round($fobINCost)];
        $result = pricingTable::where('id', $priceDataArray['id'])->update($updatePriceData);
        if($result){
            return true;
        }
    }

    public function updateAllInrConversionRate($priceDataArray, $price_value, $dropShipVolume, $checkCountry, $select_setting){
       
        $productDropShopVolume = product::find($priceDataArray['product_id']);
        $dropShipVolume = $productDropShopVolume->dropshipvolume;

        // India rows are updated only by explicit India setting keys, handled
        // before this method. All common/country settings leave them untouched.
        $destination = $priceDataArray['destination'] ?? $checkCountry;
        if (DestinationPricingPolicy::isIndia((string) $destination)) {
            return true;
        }

        $converRate = $priceDataArray['converRate'] ?? 0; 
        $finalCost = $priceDataArray['finalCost'];
        $adminCost = $priceDataArray['adminCost']; 
        $adminCostPercent = $priceDataArray['adminCostPercent']; 
        $fobINCost  = $priceDataArray['fobINCost']; 

        $landedCostForadminCostPercentage = false;
        $settingOptions = Common::getSettings();
        if (!empty($checkCountry)) {
            $checkCountry = strtolower($checkCountry);
            $settingShippingCost2Core = $settingOptions['shipping_cost_' . $checkCountry];
            $settingShippingCost2 = ($dropShipVolume * $settingShippingCost2Core);
            $settingStorageCostCore = $settingOptions['storage_cost_' . $checkCountry];
            // $settingStorageCost = ($dropShipVolume * $settingStorageCostCore * 1.5);
            $settingStorageCost = ($dropShipVolume * $settingStorageCostCore);

            $landedCostForadminCostPercentage = true;
        }

        if($select_setting == 'admin_cost_percentage'){
            $adminCostPercent = $price_value;
            $costPrice = $priceDataArray['costPrice'];
            $profitCost = $priceDataArray['profitPercent'];

            $subadminCost = ($costPrice * $adminCostPercent) / 100;
            $adminCost = floatval($costPrice) + floatval($subadminCost);
            $subfinalCost = ($adminCost * $profitCost) / 100;
            $finalCost = floatval($adminCost) + floatval($subfinalCost);

            //FOB India Cost = Final Cost / Conversion Rate
            $converRate = $priceDataArray['converRate'] ?? 0;  //database or setting
            $fobINCost = floatval($finalCost) / floatval($converRate);
        }

        $settingShippingCost2Bool = false;
        if (strpos($select_setting, 'shipping_cost_') === 0) {
            $settingShippingCost2 = $price_value;
            $settingShippingCost2 = ($dropShipVolume * $settingShippingCost2);
            $settingShippingCost2Bool = true;
        }


        $settingStorageCostBool = false;
        if (strpos($select_setting, 'storage_cost_') === 0) {
            $settingStorageCost = $price_value;
            // $settingStorageCost = ($dropShipVolume * $settingStorageCost * 1.5);
            $settingStorageCost = ($dropShipVolume * $settingStorageCost);
            $settingStorageCostBool = true;
        }

        $fobINCostBool = false;
        if (strpos($select_setting, 'inr_to_') === 0 && strpos($select_setting, '_conversion_rate') !== false) {
            $converRate = $price_value; //database or setting
            $fobINCost = floatval($finalCost) / floatval($converRate);
            $fobINCost = number_format($fobINCost, 2);
            $fobINCost = round($fobINCost);
            $fobINCostBool = true;
        }

        // Handle packaging and shipping cost updates
        $costPriceUpdated = false;
        $wSPackageCost = $priceDataArray['wSPackageCost'] ?? 0;
        $dSPackageCost = $priceDataArray['dSPackageCost'] ?? 0;
        $shippingCost = $priceDataArray['shippingCost'] ?? 0;
        $wholesaleVolume = $productDropShopVolume->wholesalevolume ?? 0;

        if ($select_setting == 'wspackaging') {
            $wSPackageCost = ($wholesaleVolume * $price_value) / 60;
            $costPriceUpdated = true;
        }

        if ($select_setting == 'dspackaging') {
            $dSPackageCost = ($dropShipVolume * $price_value) / 60;
            $costPriceUpdated = true;
        }

        if ($select_setting == 'ishippingcost') {
            $shippingCost = ($dropShipVolume * $price_value) / 60;
            $costPriceUpdated = true;
        }

        // Recalculate costPrice if packaging/shipping costs changed
        if ($costPriceUpdated) {
            $costPrice = floatval($priceDataArray['costPrice'] ?? 0);
            // Remove old packaging/shipping costs from costPrice
            $oldWSPackageCost = floatval($priceDataArray['wSPackageCost'] ?? 0);
            $oldDSPackageCost = floatval($priceDataArray['dSPackageCost'] ?? 0);
            $oldShippingCost = floatval($priceDataArray['shippingCost'] ?? 0);
            
            $costPrice = $costPrice - $oldWSPackageCost - $oldDSPackageCost - $oldShippingCost;
            // Add new packaging/shipping costs
            $costPrice = $costPrice + floatval($wSPackageCost) + floatval($dSPackageCost) + floatval($shippingCost);

            // Recalculate adminCost and finalCost
            $adminCostPercent = $priceDataArray['adminCostPercent'] ?? 0;
            $profitCost = $priceDataArray['profitPercent'] ?? 0;

            $subadminCost = ($costPrice * $adminCostPercent) / 100;
            $adminCost = floatval($costPrice) + floatval($subadminCost);
            $subfinalCost = ($adminCost * $profitCost) / 100;
            $finalCost = floatval($adminCost) + floatval($subfinalCost);

            // Recalculate FOB India Cost
            $converRate = $priceDataArray['converRate'] ?? 0;
            if ($converRate > 0) {
                $fobINCost = floatval($finalCost) / floatval($converRate);
                $fobINCost = round($fobINCost);
                $fobINCostBool = true;
            }
        }

        // Land Price , Admin Cost Quality Assurance
        $sublanded = $priceDataArray['landedCost'] ?? 0;
        $adminCost2 = $priceDataArray['adminCost2'] ?? 0; //database
        $qualityAssurance = $priceDataArray['qualityAssurance'] ?? 0; //database
        $tariffPercent = (float) ($priceDataArray['tariff_percent'] ?? 0);
        $fobForLanded = (int) round((float) $fobINCost);
        $finalFobInCost = $fobForLanded;
        if (!empty($priceDataArray['buyer_id2'])) {
            if ($fobINCostBool || $costPriceUpdated) {
                $finalFobInCost = self::computeTariffAdjustedFob($fobForLanded, $tariffPercent);
            } else {
                $finalFobInCost = (int) round((float) ($priceDataArray['final_fob_in_cost'] ?? self::computeTariffAdjustedFob($fobForLanded, $tariffPercent)));
            }
            $fobForLanded = $finalFobInCost;
        }

        if ($settingStorageCostBool || $settingShippingCost2Bool || $fobINCostBool || $landedCostForadminCostPercentage || $costPriceUpdated) {
            $sublanded = floatval($settingShippingCost2) + floatval($settingStorageCost) + floatval($adminCost2) + floatval($qualityAssurance) + floatval($fobForLanded);
            $sublanded = number_format($sublanded, 2);
        }

       
        // Admin Price
        $adminProfit = $priceDataArray['adminProfit'] ?? 0; //database or setting
        $output = ($sublanded * $adminProfit) / 100;
        $adminPrice = floatval($sublanded) + floatval($output);
        $adminPrice = number_format($adminPrice, 2);

        // Final Price
        $finalPricePercent = $priceDataArray['finalPricePer'] ?? 0; //database
        $output1 = ($adminPrice * $finalPricePercent) / 100;
        $finalPrice = floatval($adminPrice) + floatval($output1);
        $finalPrice = number_format($finalPrice, 2);

        // Delivered Cost
        $courierCost = $priceDataArray['courierCost'] ?? 0; //database
        $courierCost = $courierCost ?? 0;
        $deliveryCost = floatval($finalPrice) + floatval($courierCost);
        $deliveryCost = floatval(number_format($deliveryCost, 2));


        // Cost Adjustment
        $adjustment = $priceDataArray['adjustment'] ?? 0; //database
        $adjustmentOutput = ($deliveryCost * $adjustment) / 100;
        $finalAdjustOutput = floatval($deliveryCost) + floatval($adjustmentOutput);

        // New Delivered Cost
        $newDeliveryCost = round($finalAdjustOutput);

        $updatePriceData = [
            'adminCostPercent' => $adminCostPercent,
            'adminCost' => $adminCost,
            'finalCost' => $finalCost,
            'converRate' => $converRate,
            'fobINCost' => round($fobINCost),
            'final_fob_in_cost' => $finalFobInCost,
            'shippingCost2' => $settingShippingCost2,
            'StorageCost' => $settingStorageCost,
            'adminCost2' => $adminCost2,
            'qualityAssurance' => $qualityAssurance,
            'landedCost' => $sublanded,
            'adminProfit' => $adminProfit,
            'adminPrice' => $adminPrice,
            'finalPricePer' => $finalPricePercent,
            'finalPrice' => $finalPrice,
            'courierCost' => $courierCost,
            'deliveryCost' => $deliveryCost,
            'newDelCost' => $newDeliveryCost,
        ];

        // Update packaging and shipping costs if changed
        if ($costPriceUpdated) {
            $updatePriceData['wSPackageCost'] = round($wSPackageCost, 2);
            $updatePriceData['dSPackageCost'] = round($dSPackageCost, 2);
            $updatePriceData['shippingCost'] = round($shippingCost, 2);
            
            // Update costPrice
            $costPrice = floatval($priceDataArray['costPrice'] ?? 0);
            $oldWSPackageCost = floatval($priceDataArray['wSPackageCost'] ?? 0);
            $oldDSPackageCost = floatval($priceDataArray['dSPackageCost'] ?? 0);
            $oldShippingCost = floatval($priceDataArray['shippingCost'] ?? 0);
            
            $costPrice = $costPrice - $oldWSPackageCost - $oldDSPackageCost - $oldShippingCost;
            $costPrice = $costPrice + floatval($wSPackageCost) + floatval($dSPackageCost) + floatval($shippingCost);
            $updatePriceData['costPrice'] = round($costPrice, 2);
        }

        $result = pricingTable::where('id', $priceDataArray['id'])->update($updatePriceData);
        if($result){
            return true;
        }
    }

    public function updateAllPricingRecords($setting, ?string $onlyDestination = null)
    {
        $settingData = $setting->toArray();
        $pricingQuery = pricingTable::whereNotNull('destination');
        if ($onlyDestination !== null) {
            $pricingQuery->whereRaw('LOWER(TRIM(destination)) = ?', [
                strtolower(trim($onlyDestination)),
            ]);
        } else {
            $pricingQuery->whereRaw('LOWER(TRIM(destination)) <> ?', ['india']);
        }
        $pricingRecords = $pricingQuery->get();

        foreach ($pricingRecords as $pricing) {
            $destination = strtolower(trim((string) $pricing->destination));
            $boxwidth = floatval($pricing->boxwidth);
            $boxheight = floatval($pricing->boxheight);
            $boxdepth = floatval($pricing->boxdepth);

            if (!$boxwidth || !$boxheight || !$boxdepth) {
                continue;
            }

            $pricingData = [
                'boxwidth' => $boxwidth,
                'boxheight' => $boxheight,
                'boxdepth' => $boxdepth,
            ];
            $volWtStored = PricingVolumetricWeight::forDestination($pricingData, $settingData, $destination);
            if ($volWtStored <= 0) {
                continue;
            }

            $volWtKg = DestinationPricingPolicy::isIndia($destination)
                ? PricingVolumetricWeight::forDestination($pricingData, $settingData, 'india')
                : PricingVolumetricWeight::forDestination($pricingData, $settingData, 'uk');
            $volWtLbs = PricingVolumetricWeight::forDestination($pricingData, $settingData, 'us');

            $updateData = ['volWt' => $volWtStored];

            if ($pricing->courierType) {
                $courier = courier::where('name', $pricing->courierType)->where('country', $pricing->destination)->first();
                if ($courier) {
                    $rowData = $pricing->toArray();
                    $chargeableWeight = PricingChargeableWeight::forBaseCourierRate(
                        $rowData,
                        $destination,
                        $volWtStored
                    );
                    $courierCost = $this->calculateCourierCost($courier, $chargeableWeight, $pricing, $volWtKg, $volWtLbs);
                    $updateData['courierCost'] = $courierCost;

                    $isIndia = DestinationPricingPolicy::isIndia($destination);
                    $deliveryBase = $isIndia
                        ? floatval($pricing->indiaFinalCost ?? 0)
                        : floatval($pricing->finalPrice);
                    $deliveryCost = $deliveryBase + floatval($courierCost);
                    $updateData['deliveryCost'] = round($deliveryCost, 2);

                    $adjustment = $isIndia ? 0.0 : floatval($pricing->adjustment ?? 0);
                    $adjustmentOutput = ($deliveryCost * $adjustment) / 100;
                    $updateData['newDelCost'] = round($deliveryCost + $adjustmentOutput);
                }
            }

            if (DestinationPricingPolicy::isIndia($destination)) {
                $canonical = DestinationPricingPolicy::applyIndia(array_merge($pricing->toArray(), $updateData));
                $fields = [
                    'shippingCost', 'costPrice', 'adminCost', 'finalCost', 'currency',
                    'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent',
                    'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost',
                    'converRate', 'fobINCost', 'tariff_percent', 'final_fob_in_cost',
                    'shippingCost2', 'StorageCost', 'adminCost2', 'qualityAssurance',
                    'landedCost', 'adminProfit', 'adminPrice', 'finalPricePer',
                    'finalPrice', 'courierCost', 'deliveryCost', 'adjustment', 'newDelCost',
                    'volWt',
                ];
                $updateData = array_intersect_key($canonical, array_flip($fields));
            }

            pricingTable::where('id', $pricing->id)->update($updateData);
        }
    }
    
    private function calculateCourierCost($courier, $volWt, $pricing, $volWtKg = 0, $volWtLbs = 0)
    {
        if (!$courier) {
            return 0;
        }
        
        // Base rate + weight-band overage (highest-band-only) + fuel %.
        $rate = \App\Support\CourierWeightRateBands::baseRateWithFuel($courier, floatval($volWt));
        
        // Apply custom conditions if available
        if ($courier->custom_condition) {
            $courierCost = $this->calCulateSurchargeCustomCondition($courier->custom_condition, $rate, $pricing, $volWtKg, $volWtLbs);
            return round($courierCost, 2);
        }
        
        return round($rate, 2);
    }
    
    private function calCulateSurchargeCustomCondition($custom_conditions, $courierCost, $pricing, $volWtKg = 0, $volWtLbs = 0)
    {
        $surCharge = 0;
        $isslideCondition = true;
        $issKgLbsCondition = true;
        
        if ($custom_conditions) {
            $customConditionsArray = json_decode($custom_conditions);
            $boxheight = floatval($pricing->boxheight);
            $boxwidth = floatval($pricing->boxwidth);
            $boxdepth = floatval($pricing->boxdepth);
            // Use newly calculated volumetric weights if provided, otherwise use existing values
            $volWt = $volWtKg > 0 ? $volWtKg : floatval($pricing->volWt);
            $volWt_lbs = $volWtLbs > 0 ? $volWtLbs : floatval($pricing->volWt_lbs ?? 0);
            
            if ($boxheight && $boxwidth && $boxdepth) {
                foreach ($customConditionsArray as $customCondition) {
                    if ($surCharge > 0) {
                        $courierCost = $surCharge;
                    }
                    
                    $conditionType = is_array($customCondition) ? $customCondition[0] : (isset($customCondition->attribute) ? $customCondition->attribute : null);
                    $conditionVals = is_array($customCondition) ? $customCondition[1] : (isset($customCondition->attribute_val) ? $customCondition->attribute_val : null);
                    $conditionPrice = is_array($customCondition) ? $customCondition[2] : (isset($customCondition->popup_price) ? $customCondition->popup_price : null);
                    
                    if (!$conditionType || !$conditionVals || !$conditionPrice) {
                        continue;
                    }
                    
                    // Handle array values for conditionVals and conditionPrice
                    $conditionVal = is_array($conditionVals) ? (isset($conditionVals[0]) ? $conditionVals[0] : 0) : $conditionVals;
                    $conditionPriceVal = is_array($conditionPrice) ? (isset($conditionPrice[0]) ? $conditionPrice[0] : 0) : $conditionPrice;
                    
                    if ($conditionType === '1_sides' && $isslideCondition) {
                        if ($boxheight > $conditionVal || $boxwidth > $conditionVal || $boxdepth > $conditionVal) {
                            $surCharge = floatval($conditionPriceVal) + $courierCost;
                            $isslideCondition = false;
                        }
                    } elseif ($conditionType === '2_sides' && $isslideCondition) {
                        if (($boxheight > $conditionVal && $boxwidth > $conditionVal) || 
                            ($boxwidth > $conditionVal && $boxdepth > $conditionVal) || 
                            ($boxdepth > $conditionVal && $boxheight > $conditionVal)) {
                            $surCharge = floatval($conditionPriceVal) + $courierCost;
                            $isslideCondition = false;
                        }
                    } elseif ($conditionType === '3_sides' && $isslideCondition) {
                        if ($boxheight > $conditionVal && $boxwidth > $conditionVal && $boxdepth > $conditionVal) {
                            $surCharge = floatval($conditionPriceVal) + $courierCost;
                            $isslideCondition = false;
                        }
                    } elseif ($conditionType === 'kg' && $issKgLbsCondition) {
                        if ($volWt > floatval($conditionVal)) {
                            $surCharge = floatval($conditionPriceVal) + $courierCost;
                            $issKgLbsCondition = false;
                        }
                    } elseif ($conditionType === 'lbs' && $issKgLbsCondition) {
                        if ($volWt_lbs > floatval($conditionVal)) {
                            $surCharge = floatval($conditionPriceVal) + $courierCost;
                            $issKgLbsCondition = false;
                        }
                    }
                }
                
                if ($surCharge == 0) {
                    return $courierCost;
                } else {
                    return $surCharge;
                }
            }
        }
        
        return $courierCost;
    }

}
