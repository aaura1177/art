<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\tempBuyer;
use App\buyer;
use App\product;
use App\pricingTable;
use App\courier;
use App\setting;
use App\hardwares;
use App\tempProduct;
use App\Helpers\Common;
use App\Services\CourierBlockEngineCalculator;
use App\Services\UsCourierRuleService;
use App\Support\PricingBoxWeight;
use App\Support\PricingChargeableWeight;
use App\Support\DestinationPricingPolicy;

class BulkBuyerController extends Controller
{
    /**
     * Two-decimal rounding aligned with the pricing view chain (parseFloat((…).toFixed(2))).
     * Uses half-up at 2dp (typical for chained money math); avoids PHP_ROUND_HALF_EVEN drift vs the browser.
     */
    private static function round2($value): float
    {
        return round((float) $value, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * @return array{wSPackageCost: float, dSPackageCost: float}
     */
    private static function computePackagingCosts(
        float $wholesalevolume,
        float $dropshipvolume,
        float $wspackaging,
        float $dspackaging,
        string $mode
    ): array {
        $wsCalc = self::round2(($wholesalevolume * $wspackaging) / 60);
        $dsCalc = self::round2(($dropshipvolume * $dspackaging) / 60);

        if ($mode === 'wholesale') {
            return ['wSPackageCost' => $wsCalc, 'dSPackageCost' => 0.0];
        }

        return ['wSPackageCost' => 0.0, 'dSPackageCost' => $dsCalc];
    }

    /**
     * Main pricing INR stack (costPrice → adminCost → finalCost). Only one packaging field should be non-zero.
     *
     * @return array{costPrice: float, adminCost: float, finalCost: float}
     */
    private static function computeMainPricingCostStack(array $pricingData): array
    {
        $costPrice = (float) ($pricingData['buyingCost'] ?? 0)
            + (float) ($pricingData['tapestryCost'] ?? 0)
            + (float) ($pricingData['fillerCost'] ?? 0)
            + (float) ($pricingData['labourCost'] ?? 0)
            + (float) ($pricingData['hardwareCost1'] ?? 0)
            + (float) ($pricingData['hardwareCost2'] ?? 0)
            + (float) ($pricingData['hardwareCost3'] ?? 0)
            + (float) ($pricingData['hardwareCost4'] ?? 0)
            + (float) ($pricingData['hardwareCost5'] ?? 0)
            + (float) ($pricingData['polishCost'] ?? 0)
            + (float) ($pricingData['wSPackageCost'] ?? 0)
            + (float) ($pricingData['dSPackageCost'] ?? 0)
            + (float) ($pricingData['shippingCost'] ?? 0);
        $costPrice = self::round2($costPrice);

        $adminCostPercent = (float) ($pricingData['adminCostPercent'] ?? 0);
        $adminCost = self::round2($costPrice + (($costPrice * $adminCostPercent) / 100));

        $profitPercent = (float) ($pricingData['profitPercent'] ?? 0);
        $finalCost = self::round2($adminCost + (($adminCost * $profitPercent) / 100));

        return [
            'costPrice' => $costPrice,
            'adminCost' => $adminCost,
            'finalCost' => $finalCost,
        ];
    }

    /**
     * Soft box weight: saved pricing.boxWt if > 0, else product gross_weight.
     */
    private static function resolveBoxWtKg(array $pricingData): float
    {
        return PricingBoxWeight::resolveKg($pricingData);
    }

    private static function computeTariffAdjustedFob(int $fobINCost, float $tariffPercent): int
    {
        $adjusted = $fobINCost + (($fobINCost * $tariffPercent) / 100);
        $fixed2 = number_format((float) $adjusted, 2, '.', '');

        return (int) round((float) $fixed2, 0, PHP_ROUND_HALF_UP);
    }

    private static function resolveTariffPercent(tempBuyer $buyer, float $tapestryCost): float
    {
        if ($tapestryCost > 0) {
            return (float) ($buyer->tariff_upholstered_percent ?? 0);
        }

        return (float) ($buyer->tariff_solid_wood_percent ?? 0);
    }

    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }
    public function create_bulk_buyer()
    {
        $tempBuyers = tempBuyer::get();
        $pricingData = pricingTable::get()->toarray();
        
        $products = [];
        $buyer2Data = [];
        $buyerDataId = array_unique(array_column($pricingData, 'buyer_id'));
        $buyerData = tempBuyer::whereIn('id', $buyerDataId)->get();
        
        return view('bulk_buyer_update/index', ['tempBuyers' => $tempBuyers, 'products' => $products, 'buyerData' => $buyerData, 'buyer2Data' => $buyer2Data]);
    }

    /**
     * Create Bulk All Cost Input page: one set of cost inputs applied to multiple products' pricing rows.
     * Same cost fields and JS calculations as pricing view (Cost Price, Admin Cost, Final Cost, etc.).
     */
    public function create_bulk_all_cost_input()
    {
        $tempBuyers = tempBuyer::get();
        $pricingData = pricingTable::get()->toArray();
        $products = [];
        $buyer2Data = [];
        $buyerDataId = array_unique(array_column($pricingData, 'buyer_id'));
        $buyerData = tempBuyer::whereIn('id', $buyerDataId)->get();
        $settings = setting::get();
        $hardwares = hardwares::get();
        $settingsOption = Common::getSettings();
        return view('bulk_buyer_update/all_cost_input', [
            'tempBuyers' => $tempBuyers,
            'products' => $products,
            'buyerData' => $buyerData,
            'buyer2Data' => $buyer2Data,
            'settings' => $settings,
            'hardwares' => $hardwares,
            'settings_option' => $settingsOption,
        ]);
    }

    /**
     * Recalculate all cost fields from each product's inputs. Packaging always uses dropship
     * (wSPackageCost = 0, dSPackageCost calculated) — same default as pricing add/edit.
     */
    public function update_bulk_all_cost_input(Request $request)
    {
        $main_buyer_id = $request->input('main_buyer_id');
        if (!$main_buyer_id) {
            return redirect('/buyer/create-bulk-all-cost-input')->with('danger', 'Please select Buyer.');
        }

        $selectProductArr = $request->input('select_product', []);
        if (empty($selectProductArr)) {
            $selectProductArr = pricingTable::where('buyer_id', $main_buyer_id)
                ->where(function ($q) {
                    $q->whereNull('buyer_id2')->orWhere('buyer_id2', '');
                })
                ->pluck('product_id')
                ->unique()
                ->values()
                ->all();
        }
        $selectProductArr = array_filter(array_map('intval', (array) $selectProductArr));
        if (empty($selectProductArr)) {
            return redirect('/buyer/create-bulk-all-cost-input')->with('danger', 'No products to update for this buyer.');
        }

        $firstSetting = setting::first();
        $getSettingData = $firstSetting ? $firstSetting->toArray() : [];
        // Same as pricing view: packaging/shipping from setting
        $wspackaging = $firstSetting ? (float)($firstSetting->wspackaging ?? 0) : 0;
        $dspackaging = $firstSetting ? (float)($firstSetting->dspackaging ?? 0) : 0;
        $ishippingcost = $firstSetting ? (float)($firstSetting->ishippingcost ?? 0) : 0;

        $dataSaveIssueIds = [];
        $updatedCount = 0;
        $hasVolWtLbs = Schema::hasColumn((new pricingTable)->getTable(), 'volWt_lbs');

        foreach ($selectProductArr as $productId) {
            if ($productId <= 0) {
                continue;
            }
            try {
                $row = pricingTable::where('product_id', $productId)
                    ->where('buyer_id', $main_buyer_id)
                    ->where(function ($q) {
                        $q->whereNull('buyer_id2')->orWhere('buyer_id2', '');
                    })
                    ->orderBy('id', 'asc')
                    ->first();

                if (!$row) {
                    \Log::warning("Bulk All Cost Input: No main pricing row for product_id {$productId}, buyer_id {$main_buyer_id}. Skipped.");
                    continue;
                }

                $dropshipvolume = (float)($row->dropshipvolume ?? 0);
                $wholesalevolume = (float)($row->wholesalevolume ?? 0);
                $pUnitCost = (float)($row->pUnitCost ?? 0);

                // Same as pricing view: derived costs — round2 so DB matches view (half-even: 15343.405 → 15343.40)
                $polishCost = self::round2(($dropshipvolume * $pUnitCost) / 60);
                $shippingCost = self::round2(($dropshipvolume * $ishippingcost) / 60);

                $packaging = self::computePackagingCosts(
                    $wholesalevolume,
                    $dropshipvolume,
                    $wspackaging,
                    $dspackaging,
                    'dropship'
                );
                $wSPackageCost = $packaging['wSPackageCost'];
                $dSPackageCost = $packaging['dSPackageCost'];

                $buyingCost = (float)($row->buyingCost ?? 0);
                $tapestryCost = (float)($row->tapestryCost ?? 0);
                $fillerCost = (float)($row->fillerCost ?? 0);
                $labourCost = (float)($row->labourCost ?? 0);
                $hardwareCost1 = (float)($row->hardwareCost1 ?? 0);
                $hardwareCost2 = (float)($row->hardwareCost2 ?? 0);
                $hardwareCost3 = (float)($row->hardwareCost3 ?? 0);
                $hardwareCost4 = (float)($row->hardwareCost4 ?? 0);
                $hardwareCost5 = (float)($row->hardwareCost5 ?? 0);
                $adminCostPercent = (float)($row->adminCostPercent ?? 0);
                $profitPercent = (float)($row->profitPercent ?? 0);
                $boxwidth = (float)($row->boxwidth ?? 0);
                $boxheight = (float)($row->boxheight ?? 0);
                $boxdepth = (float)($row->boxdepth ?? 0);

                $stack = self::computeMainPricingCostStack([
                    'buyingCost' => $buyingCost,
                    'tapestryCost' => $tapestryCost,
                    'fillerCost' => $fillerCost,
                    'labourCost' => $labourCost,
                    'hardwareCost1' => $hardwareCost1,
                    'hardwareCost2' => $hardwareCost2,
                    'hardwareCost3' => $hardwareCost3,
                    'hardwareCost4' => $hardwareCost4,
                    'hardwareCost5' => $hardwareCost5,
                    'polishCost' => $polishCost,
                    'wSPackageCost' => $wSPackageCost,
                    'dSPackageCost' => $dSPackageCost,
                    'shippingCost' => $shippingCost,
                    'adminCostPercent' => $adminCostPercent,
                    'profitPercent' => $profitPercent,
                ]);
                $costPrice = $stack['costPrice'];
                $adminCost = $stack['adminCost'];
                $finalCost = $stack['finalCost'];

                $volWt = 0;
                $volWtLbs = 0;
                try {
                    if ($firstSetting && $boxwidth > 0 && $boxheight > 0 && $boxdepth > 0) {
                        $pricingDataForVol = [
                            'boxwidth' => $boxwidth, 'boxheight' => $boxheight, 'boxdepth' => $boxdepth,
                            'volWt' => 0, 'volWt_lbs' => 0,
                        ];
                        $rowDest = strtolower(trim((string) ($row->destination ?? 'uk')));
                        $volWt = \App\Support\PricingVolumetricWeight::forDestination($pricingDataForVol, $getSettingData, $rowDest);
                        $volWtLbs = $this->computeVolWtLbs($pricingDataForVol, $getSettingData, 'us');
                        $volWtLbs = (float) ceil((float) $volWtLbs);
                    }
                } catch (\Throwable $volEx) {
                    \Log::warning("Bulk All Cost Input: volWt calc failed for product_id {$productId}: " . $volEx->getMessage());
                }

                $row->polishCost = $polishCost;
                $row->wSPackageCost = $wSPackageCost;
                $row->dSPackageCost = $dSPackageCost;
                $row->shippingCost = $shippingCost;
                $row->costPrice = $costPrice;
                $row->adminCost = $adminCost;
                $row->finalCost = $finalCost;
                $row->volWt = $volWt;
                if ($hasVolWtLbs) {
                    $row->volWt_lbs = $volWtLbs;
                }
                $row->updated_at = now();
                $row->save();
                $updatedCount++;
            } catch (\Throwable $e) {
                \Log::error("Bulk All Cost Input failed for product_id {$productId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                $dataSaveIssueIds[] = $productId;
            }
        }

        if (count($dataSaveIssueIds) > 0) {
            $msg = $updatedCount > 0
                ? "{$updatedCount} product(s) updated. " . count($dataSaveIssueIds) . " failed. Check storage/logs/laravel.log for product IDs: " . implode(', ', array_slice($dataSaveIssueIds, 0, 10)) . (count($dataSaveIssueIds) > 10 ? '...' : '')
                : 'No products updated. All failed. Check storage/logs/laravel.log.';
            return redirect('/buyer/create-bulk-all-cost-input')->with('warning', $msg);
        }
        return redirect('/buyer/create-bulk-all-cost-input')->with('success', "Cost columns recalculated and DB updated successfully for {$updatedCount} product(s).");
    }
   
   
    // public function update_bulk_buyer(Request $request)
    // {
    //     $dataSaveIssueIds = [];
    //     $buyerIdsProblem = [];
    //     $selectProductArr = $request['select_product'];
    //     $buyer_id = $request['buyer_id'];
    //     foreach ($selectProductArr as $key => $productId) {
    //         $pricingDatacount = PricingTable::where(['product_id' => $productId, 'buyer_id2' => $buyer_id])->count();
    //         if ($pricingDatacount == 0) {
    //             try {
    //                 $pricingData = PricingTable::select('product_id', 'buyer_id', 'startDate', 'endDate', 'remarks', 'buyingCost', 'fabricCost', 'tapestryConsumed', 'tapestryCost', 'fillerCost', 'labourCost', 'hardwareCost1', 'hardwareCost2', 'hardwareCost3', 'hardwareCost4', 'hardwareCost5', 'pUnitCost', 'polishCost', 'wSPackageCost', 'dSPackageCost', 'shippingCost', 'costPrice', 'adminCostPercent', 'adminCost', 'profitPercent', 'finalCost', 'productType', 'deliveredCostStatus', 'boxheight', 'hardwareCost2', 'hardwareCost3', 'hardwareCost4', 'hardwareCost5', 'boxwidth', 'boxdepth', 'fuelCharge', 'boxWt', 'productheight', 'productwidth', 'productdepth', 'wholesalevolume', 'dropshipvolume', 'adjustment', 'hardware1', 'hardware2', 'hardware3', 'hardware4', 'hardware5', 'hardware1_quantity', 'hardware2_quantity', 'hardware3_quantity', 'hardware4_quantity', 'hardware5_quantity', 'fuelCharge', 'buyer_id2', 'volWt')->where('product_id', $productId)->first()->toArray();
    //                 $tempBuyerCountry = tempBuyer::where(['id' => $buyer_id])->first();
    //                 $finalCost = $pricingData['finalCost'];
    //                 $productDropShipVolume = product::find($pricingData['product_id']);
    //                 $dropShipVolume = $productDropShipVolume->dropshipvolume;

    //                 // Shipping Cost
    //                 $CommonVariable = Common::getSettings();
    //                 if (!empty($tempBuyerCountry->country)) {
    //                     $checkCountry = strtolower($tempBuyerCountry->country);
    //                     $settingShippingCost2Core = $CommonVariable['shipping_cost_' . $checkCountry];
    //                     $settingShippingCost2 = ($dropShipVolume * $settingShippingCost2Core);
    //                     $settingStorageCostCore = $CommonVariable['storage_cost_' . $checkCountry];
    //                     $settingStorageCost = ($dropShipVolume * $settingStorageCostCore * 1.5);
    //                     if ($checkCountry == 'uk') {
    //                         $converRate = $CommonVariable['inr_to_pound_conversion_rate'];
    //                     } else if ($checkCountry == 'us') {
    //                         $converRate = $CommonVariable['inr_to_dollar_conversion_rate'];
    //                     } else if ($checkCountry == 'eu') {
    //                         $converRate = $CommonVariable['inr_to_euro_conversion_rate'];
    //                     }
                       
    //                     $fobINCost = floatval($finalCost) / floatval($converRate);
    //                     $fobINCost = number_format($fobINCost, 2);
    //                     $fobINCost = round($fobINCost);
    //                     // Land Price , Admin Cost Quality Assurance
    //                     $adminCost2 = $pricingData['adminCost2'] ?? 0; //database
    //                     $qualityAssurance = $pricingData['qualityAssurance'] ?? 0; //database
    //                     $sublanded = floatval($settingShippingCost2) + floatval($settingStorageCost) + floatval($adminCost2) + floatval($qualityAssurance) + floatval($fobINCost);
    //                     $sublanded = number_format($sublanded, 2);

    //                     // Admin Price
    //                     $adminProfit = $tempBuyerCountry->admin_profit ?? 0; //database or setting
    //                     $output = ($sublanded * $adminProfit) / 100;
    //                     $adminPrice = floatval($sublanded) + floatval($output);
    //                     $adminPrice = number_format($adminPrice, 2);

    //                     // Final Price
    //                     $finalPricePercent = $tempBuyerCountry->final_price_percent ?? 0; //database
    //                     $output1 = ($adminPrice * $finalPricePercent) / 100;
    //                     $finalPrice = floatval($adminPrice) + floatval($output1);
    //                     $finalPrice = number_format($finalPrice, 2);
                        
    //                     // Delivered Cost
    //                     $getSettingData = setting::first()->toarray();
    //                     $courierData = courier::where(['country' => $tempBuyerCountry->country, 'is_default' => 1])->first();
    //                     $volumetricActualUnit = $this->changeVolumetricUnit($pricingData, $getSettingData, $tempBuyerCountry->country);
    //                     $courierCost = $this->changeCourierRate($courierData, $volumetricActualUnit); //database
    //                     $getCustomValue =  $this->getCustomConditionValue($courierData->name ?? null);
    //                     if($getCustomValue){
    //                         $courierCost  =  $this->calCulateSurchargeCustomCondition($getCustomValue,$courierCost,$pricingData,$volumetricActualUnit);
    //                     }
    //                     $courierCost = $courierCost ?? 0;
    //                     $deliveryCost = floatval($finalPrice) + floatval($courierCost);
    //                     $deliveryCost = floatval(number_format($deliveryCost, 2));
                       
    //                     // Cost Adjustment
    //                     $adjustment = $pricingData['adjustment'] ?? 0;; //database
    //                     $adjustmentOutput = ($deliveryCost * $adjustment) / 100;
    //                     $finalAdjustOutput = floatval($deliveryCost) + floatval($adjustmentOutput);

    //                     // New Delivered Cost
    //                     $newDeliveryCost = round($finalAdjustOutput);
    //                     if ($tempBuyerCountry->country == 'UK') {
    //                         $currency = '£';
    //                     } else if ($tempBuyerCountry->country == 'US') {
    //                         $currency = '$';
    //                     } else if ($tempBuyerCountry->country == 'EU') {
    //                         $currency = '€';
    //                     }

    //                     $updatePriceData = [
    //                         'buyer_id2' => $buyer_id,
    //                         'volWt' => $volumetricActualUnit,
    //                         'destination' => $tempBuyerCountry->country,
    //                         'currency' => $currency,
    //                         'converRate' => $converRate,
    //                         'fobINCost' => round($fobINCost),
    //                         'shippingCost2' => $settingShippingCost2,
    //                         'StorageCost' => $settingStorageCost,
    //                         'adminCost2' => $adminCost2,
    //                         'qualityAssurance' => $qualityAssurance,
    //                         'landedCost' => $sublanded,
    //                         'adminProfit' => $adminProfit,
    //                         'adminPrice' => $adminPrice,
    //                         'finalPricePer' => $finalPricePercent,
    //                         'finalPrice' => $finalPrice,
    //                         'courierCost' => $courierCost ?? 0,
    //                         'courierType' => $courierData->name ?? null,
    //                         'deliveryCost' => $deliveryCost,
    //                         'newDelCost' => $newDeliveryCost,
    //                         'created_at' => date('Y-m-d'),
    //                         'updated_at' => date('Y-m-d'),
    //                     ];

    //                     try {
    //                         $data = array_merge($pricingData, $updatePriceData);
    //                         PricingTable::insert($data);
    //                     } catch (\Exception $e) {
    //                         $dataSaveIssueIds[$key] = $buyer_id;
                           
    //                     }
    //                 } else {
    //                     $buyerIdsProblem[$key] = $buyer_id;
    //                     // continue;
    //                 }
    //             } catch (\Exception $e) {
    //                 $buyerIdsProblem[$key] = $buyer_id;
    //             }
    //         }
    //     }
       
    //     return redirect('/buyer/create-bulk-pricing')->with('success', 'Bulk Pricing was added successfully.');
    // }

    /**
     * Bulk screen lists product_table items — default productType 1; fall back if only temp pricing exists.
     */
    private function detectProductTypeForBulkProduct(int $productId): int
    {
        if (pricingTable::where('product_id', $productId)->where('productType', 1)->exists()) {
            return 1;
        }

        return (int) (pricingTable::where('product_id', $productId)->orderBy('id')->value('productType') ?? 1);
    }

    private function pricingRowsForProductType(int $productId, int $productType)
    {
        return pricingTable::where('product_id', $productId)->where('productType', $productType);
    }

    private function resolveBasePricingRow(int $productId, int $productType): ?pricingTable
    {
        $mainRow = $this->pricingRowsForProductType($productId, $productType)
            ->where(function ($q) {
                $q->whereNull('buyer_id2')->orWhere('buyer_id2', '');
            })
            ->orderBy('id', 'asc')
            ->first();

        if ($mainRow) {
            return $mainRow;
        }

        return $this->pricingRowsForProductType($productId, $productType)
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Canonical destination row = lowest pricingTable.id (same row as pricing list / export).
     *
     * @return array{row: ?pricingTable, duplicateWarning: ?array{code: string, product_id: int, productType: int, updated_id: int, duplicate_ids: int[]}}
     */
    private function resolveBulkDestinationRow(int $productId, int $productType, int $buyerId): array
    {
        $destinationRows = $this->pricingRowsForProductType($productId, $productType)
            ->where('buyer_id2', $buyerId)
            ->orderBy('id', 'asc')
            ->get();

        $duplicateWarning = null;
        $destinationRow = $destinationRows->first();

        if ($destinationRows->count() > 1) {
            $duplicateWarning = [
                'code' => $this->productCodeForBulk($productId, $productType),
                'product_id' => $productId,
                'productType' => $productType,
                'updated_id' => (int) $destinationRow->id,
                'duplicate_ids' => $destinationRows->slice(1)->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ];
        }

        if ($destinationRow) {
            return ['row' => $destinationRow, 'duplicateWarning' => $duplicateWarning];
        }

        $emptyBuyer2Row = $this->pricingRowsForProductType($productId, $productType)
            ->where(function ($query) {
                $query->whereNull('buyer_id2')->orWhere('buyer_id2', '');
            })
            ->orderBy('id', 'asc')
            ->first();

        return ['row' => $emptyBuyer2Row, 'duplicateWarning' => $duplicateWarning];
    }

    private function productCodeForBulk(int $productId, int $productType): string
    {
        if ($productType === 1) {
            $code = product::where('id', $productId)->value('code');
            if ($code) {
                return (string) $code;
            }
        } else {
            $code = tempProduct::where('id', $productId)->value('code');
            if ($code) {
                return (string) $code;
            }
        }

        return "product_id={$productId}";
    }

    /**
     * Products with more than one pricing row for the same destination buyer.
     */
    private function findDuplicateDestinationPricing(array $productIds, int $buyerId): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        $duplicates = [];

        foreach ($productIds as $productId) {
            if ($productId <= 0) {
                continue;
            }

            $productType = $this->detectProductTypeForBulkProduct($productId);
            $rows = $this->pricingRowsForProductType($productId, $productType)
                ->where('buyer_id2', $buyerId)
                ->orderBy('id', 'asc')
                ->get(['id']);

            if ($rows->count() < 2) {
                continue;
            }

            $duplicates[] = [
                'code' => $this->productCodeForBulk($productId, $productType),
                'product_id' => $productId,
                'productType' => $productType,
                'row_count' => $rows->count(),
                'pricing_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'canonical_id' => (int) $rows->first()->id,
                'duplicate_ids' => $rows->slice(1)->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ];
        }

        return $duplicates;
    }

    public function checkBulkDestinationDuplicates(Request $request)
    {
        $buyerId = (int) $request->input('buyer_id', 0);
        $productIds = (array) $request->input('select_product', []);

        if ($buyerId <= 0) {
            return response()->json(['duplicates' => []]);
        }

        return response()->json([
            'duplicates' => $this->findDuplicateDestinationPricing($productIds, $buyerId),
        ]);
    }

  /**
   * Bulk update Buyer 2 fulfilment cost. Landed Cost, Admin Price, Final Price, Delivery Cost, Final Delivered Cost
   * follow pricing/view.blade.php (allCostNew). INR costPrice / finalCost for FOB use dropship packaging only
   * (dSPackageCost); wholesale packaging (wSPackageCost) is not included in that stack for Buyer 2 bulk.
   *
   * One row per (product_id, productType, buyer_id2). When duplicates exist in DB, updates lowest id (list row).
   */
  public function update_bulk_buyer(Request $request)
{
    $selectProductArr = array_filter(array_map('intval', (array) $request->input('select_product', [])));
    $buyer_id = (int) $request->input('buyer_id');

    if ($buyer_id <= 0) {
        return redirect('/buyer/create-bulk-pricing')->with('danger', 'Please select Buyer 2 (Destination).');
    }
    if (empty($selectProductArr)) {
        return redirect('/buyer/create-bulk-pricing')->with('danger', 'Please select at least one product. First select Buyer, then choose products from the list.');
    }

    $dataSaveIssueIds = [];
    $buyerIdsProblem = [];
    $duplicateDestinationWarnings = [];
    $pricingTableName = (new pricingTable)->getTable();
    $hasVolWtLbs = Schema::hasColumn($pricingTableName, 'volWt_lbs');

    foreach ($selectProductArr as $key => $productId) {
        try {
            if ($productId <= 0) {
                continue;
            }

            $productType = $this->detectProductTypeForBulkProduct($productId);
            $basePricing = $this->resolveBasePricingRow($productId, $productType);

            if (!$basePricing) {
                throw new \Exception("Base pricing not found for Product ID: {$productId}");
            }

            $productType = (int) ($basePricing->productType ?? $productType);

            ['row' => $rowToUpdate, 'duplicateWarning' => $duplicateWarning] = $this->resolveBulkDestinationRow(
                $productId,
                $productType,
                $buyer_id
            );

            if ($duplicateWarning) {
                $duplicateDestinationWarnings[] = $duplicateWarning;
            }

            $buyerId2Empty = ($rowToUpdate && ($rowToUpdate->buyer_id2 === null || $rowToUpdate->buyer_id2 === ''));
            if ($rowToUpdate && $buyerId2Empty) {
                $basePricing = $rowToUpdate;
            }

            $pricingKeys = [
                'product_id', 'buyer_id', 'startDate', 'endDate', 'remarks', 'buyingCost',
                'fabricCost', 'tapestryConsumed', 'tapestryCost', 'fillerCost', 'labourCost',
                'hardwareCost1', 'hardwareCost2', 'hardwareCost3', 'hardwareCost4', 'hardwareCost5',
                'pUnitCost', 'polishCost', 'wSPackageCost', 'dSPackageCost', 'shippingCost',
                'costPrice', 'adminCostPercent', 'adminCost', 'profitPercent', 'finalCost',
                'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent',
                'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost',
                'productType', 'deliveredCostStatus', 'boxheight', 'boxwidth', 'boxdepth',
                'boxWt', 'fuelCharge', 'productheight', 'productwidth', 'productdepth',
                'wholesalevolume', 'dropshipvolume', 'adjustment', 'hardware1', 'hardware2',
                'hardware3', 'hardware4', 'hardware5', 'hardware1_quantity', 'hardware2_quantity',
                'hardware3_quantity', 'hardware4_quantity', 'hardware5_quantity', 'volWt',
                'adminCost2', 'qualityAssurance'
            ];
            if ($hasVolWtLbs) {
                $pricingKeys[] = 'volWt_lbs';
            }
            $pricingData = $basePricing->only($pricingKeys);

            // 2. Safely fetch Buyer
            $tempBuyerCountry = tempBuyer::find($buyer_id);
            if (!$tempBuyerCountry || empty($tempBuyerCountry->country)) {
                $buyerIdsProblem[$key] = $buyer_id;
                throw new \Exception("Buyer or Buyer Country missing for Buyer ID: {$buyer_id}");
            }

            // 3. Dropship volume: use base pricing row first (same as view uses form's dropshipvolume)
            $productType = (int)($pricingData['productType'] ?? 1);
            $dropShipVolume = (float)($pricingData['dropshipvolume'] ?? 0);
            if ($dropShipVolume <= 0) {
                $prod = ($productType === 1)
                    ? product::find($productId)
                    : \App\tempProduct::find($productId);
                $dropShipVolume = $prod ? (float)$prod->dropshipvolume : 0;
            }

            // FinalCost for FOB: INR stack matches main inputs except wholesale packaging is omitted (dropship packaging only).
            $costStack = $this->computeCostStackForBuyer2Bulk($pricingData);
            $finalCost = $costStack['finalCost'];

            // Common Settings (matches view: fulFillmentCostBuyer2Calculation + allCostNew)
            $CommonVariable = Common::getSettings();
            $checkCountry = strtolower($tempBuyerCountry->country);

            $settingShippingCost2Core = (float)($CommonVariable['shipping_cost_' . $checkCountry] ?? 0);
            // Match view: (dropshipvolume * settingShippingCost2).toFixed(2)
            $settingShippingCost2 = self::round2($dropShipVolume * $settingShippingCost2Core);

            // View sets StorageCost field to 0 when populating buyer2 (fulFillmentCostBuyer2Calculation)
            $settingStorageCost = 0; // match view: StorageCost input is 0 for buyer2 calculation

            // Conversion rate: match pricing view auto-calc behavior.
            // In JS `allCostNew(null, flag, false)`, converRate starts as 0 and is then filled from settings
            // based on destination country (ignores any prefilled DB value unless user changes it manually).
            $converRate = match ($checkCountry) {
                'uk' => (float)($CommonVariable['inr_to_pound_conversion_rate'] ?? 1),
                'us' => (float)($CommonVariable['inr_to_dollar_conversion_rate'] ?? 1),
                'eu' => (float)($CommonVariable['inr_to_euro_conversion_rate'] ?? 1),
                'canada' => (float)($CommonVariable['inr_to_canadian_conversion_rate'] ?? 1),
                'australia' => (float)($CommonVariable['inr_to_australia_conversion_rate'] ?? 1),
                default => 1.0,
            };
            if ($converRate <= 0) {
                $converRate = 1.0;
            }

            // --- Same as pricing view allCostNew(getCurrency, flag): every step 1:1 so view and DB match ---
            // View: fobINCost = parseFloat(finalCost)/parseFloat(converRate); fobINCost=fobINCost.toFixed(2); fobINCost=Math.round(fobINCost);
            $converRateForFob = $converRate > 0 ? $converRate : 1.0;
            $fobQuotient = ($finalCost ?? 0) / $converRateForFob;
            $fobFixed2 = number_format((float)$fobQuotient, 2, '.', '');
            $fobINCost = (int) round((float)$fobFixed2, 0, PHP_ROUND_HALF_UP);

            $tapestryCost = (float)($pricingData['tapestryCost'] ?? 0);
            $tariffPercent = null;
            if ($rowToUpdate && (string) $rowToUpdate->buyer_id2 === (string) $buyer_id && $rowToUpdate->tariff_percent !== null) {
                $tariffPercent = (float) $rowToUpdate->tariff_percent;
            }
            if ($tariffPercent === null) {
                $tariffPercent = self::resolveTariffPercent($tempBuyerCountry, $tapestryCost);
            }
            $finalFobInCost = self::computeTariffAdjustedFob($fobINCost, $tariffPercent);

            // View: adminCost2 = $('#adminCost2_'+flag).val(); qualityAssurance = $('#qualityAssurance_'+flag).val();
            $adminCost2 = (float)($pricingData['adminCost2'] ?? 0);
            $qualityAssurance = (float)($pricingData['qualityAssurance'] ?? 0);

            // View: sublanded=...toFixed(2); round2 = half-even so DB matches view (15343.405 → 15343.40)
            $sublanded = $settingShippingCost2 + $settingStorageCost + $adminCost2 + $qualityAssurance + $finalFobInCost;
            $sublanded = self::round2($sublanded);

            // View: adminPrice=...; adminPrice=adminPrice.toFixed(2);
            $adminProfit = (float)($tempBuyerCountry->admin_profit ?? 0);
            $adminPrice = $sublanded + (($sublanded * $adminProfit) / 100);
            $adminPrice = self::round2($adminPrice);

            // View: finalPrice=...; finalPrice=finalPrice.toFixed(2);
            $finalPricePercent = (float)($tempBuyerCountry->final_price_percent ?? 0);
            $finalPrice = $adminPrice + (($adminPrice * $finalPricePercent) / 100);
            $finalPrice = self::round2($finalPrice);

            // Volumetric (view: roundUpVolWt = Math.ceil) and courier
            $firstSetting = setting::first();
            if (!$firstSetting) {
                throw new \Exception("Settings missing in database.");
            }
            $getSettingData = $firstSetting->toArray();

            $volumetricActualUnit = $this->changeVolumetricUnit($pricingData, $getSettingData, $tempBuyerCountry->country);
            $volumetricActualUnit = (float)ceil((float)$volumetricActualUnit); // match view roundUpVolWt

            // volWt_lbs: same as pricing view (roundUpVolWt = Math.ceil) — always store LBS for custom conditions/display
            $volWtLbs = $this->computeVolWtLbs($pricingData, $getSettingData, $checkCountry);
            $volWtLbs = (float)ceil((float)$volWtLbs);

            // Temp buyer courierType null/''/0 = No Courier → do not apply country default.
            // When buyer has a courier configured, keep previous behaviour (country is_default).
            $courierData = $this->resolveCourierForTempBuyer($tempBuyerCountry);

            $courierCost = $this->computeBuyer2CourierCost(
                $courierData,
                $pricingData,
                $volumetricActualUnit,
                $volWtLbs,
                $checkCountry,
                $getSettingData,
                (string) $tempBuyerCountry->country
            );

            if ($checkCountry === 'uk' && $courierData) {
                $currentName = strtolower(trim((string) $courierData->name));
                if ($currentName !== 'palletways' && $courierCost > 100) {
                    $palletways = courier::where('country', $tempBuyerCountry->country)
                        ->whereRaw('LOWER(TRIM(name)) = ?', ['palletways'])
                        ->first();
                    if ($palletways) {
                        $courierData = $palletways;
                        $courierCost = $this->computeBuyer2CourierCost(
                            $courierData,
                            $pricingData,
                            $volumetricActualUnit,
                            $volWtLbs,
                            $checkCountry,
                            $getSettingData,
                            (string) $tempBuyerCountry->country
                        );
                    }
                }
            }

            // UK rule: if Courier Cost > 100, force it to 115
            if ($checkCountry === 'uk' && (float)$courierCost > 100) {
                $courierCost = 115;
            }

            // View: deliveryCost = ...toFixed(2); round2 so DB matches view
            $deliveryCost = (float)$finalPrice + (float)$courierCost;
            $deliveryCost = self::round2($deliveryCost);

            // Match pricing view: Cost Adjustment (%) from destination temp buyer (cost_adjustments),
            // same pattern as admin_profit / final_price_percent — not base-row adjustment.
            $adjustment = (float) ($tempBuyerCountry->cost_adjustments ?? 0);
            $finalAdjustOutput = $deliveryCost + (($deliveryCost * $adjustment) / 100);
            $newDeliveryCost = (int) round($finalAdjustOutput, 0, PHP_ROUND_HALF_UP);

            // Currency (match view fulfilment: California uses $ like view.blade.php)
            $currency = match (strtoupper(trim((string) $tempBuyerCountry->country))) {
                'UK' => '£',
                'US' => '$',
                'EU' => '€',
                'CANADA' => 'C$',
                'AUSTRALIA' => 'A$',
                'CALIFORNIA' => '$',
                default => '',
            };

            // All Buyer 2 inputs same as view — save every field that view form submits to DB (no single point difference)
            $updatePriceData = [
                'costPrice' => $costStack['costPrice'],
                'adminCost' => $costStack['adminCost'],
                'finalCost' => $costStack['finalCost'],
                'buyer_id2' => $buyer_id,
                'destination' => $tempBuyerCountry->country,
                'currency' => $currency,
                'converRate' => $converRate,
                'fobINCost' => $fobINCost,
                'tariff_percent' => $tariffPercent,
                'final_fob_in_cost' => $finalFobInCost,
                'shippingCost2' => self::round2($settingShippingCost2),
                'StorageCost' => $settingStorageCost,
                'adminCost2' => $adminCost2,
                'qualityAssurance' => $qualityAssurance,
                'landedCost' => $sublanded,
                'adminProfit' => $adminProfit,
                'adminPrice' => $adminPrice,
                'finalPricePer' => $finalPricePercent,
                'finalPrice' => $finalPrice,
                'courierType' => $courierData ? $courierData->name : null,
                'courierCost' => self::round2($courierCost),
                'deliveryCost' => $deliveryCost,
                'adjustment' => $adjustment,
                'newDelCost' => $newDeliveryCost,
                'volWt' => $volumetricActualUnit,
                'updated_at' => now(),
            ];
            if ($hasVolWtLbs) {
                $updatePriceData['volWt_lbs'] = $volWtLbs;
            }

            if (DestinationPricingPolicy::isIndia($checkCountry)) {
                $hasExistingIndiaDestination = $rowToUpdate
                    && (string) $rowToUpdate->buyer_id2 === (string) $buyer_id;
                $indiaAdminPercent = $hasExistingIndiaDestination
                    ? (float) $rowToUpdate->indiaAdminCostPercent
                    : (float) ($CommonVariable['india_admin_cost_percentage'] ?? 0);
                $indiaProfitPercent = $hasExistingIndiaDestination
                    ? (float) $rowToUpdate->indiaProfitPercent
                    : (float) ($CommonVariable['india_profit_percentage'] ?? 0);
                $indiaShippingCost = $hasExistingIndiaDestination
                    ? (float) $rowToUpdate->indiaShippingCost
                    : (float) ($pricingData['indiaShippingCost'] ?? 0);

                $canonical = DestinationPricingPolicy::applyIndia(array_merge(
                    $pricingData,
                    $updatePriceData,
                    [
                        'destination' => 'india',
                        'indiaShippingCost' => $indiaShippingCost,
                        'indiaAdminCostPercent' => $indiaAdminPercent,
                        'indiaProfitPercent' => $indiaProfitPercent,
                    ]
                ));
                $indiaFields = [
                    'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent',
                    'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost',
                    'currency', 'converRate', 'fobINCost', 'tariff_percent',
                    'final_fob_in_cost', 'shippingCost2', 'StorageCost', 'adminCost2',
                    'qualityAssurance', 'landedCost', 'adminProfit', 'adminPrice',
                    'finalPricePer', 'finalPrice', 'courierCost', 'deliveryCost',
                    'adjustment', 'newDelCost',
                ];
                $updatePriceData = array_merge(
                    $updatePriceData,
                    array_intersect_key($canonical, array_flip($indiaFields))
                );
            }

            // Insert or Update — never create when (product_id, productType, buyer_id2) already exists
            $existingDestinationRow = $this->pricingRowsForProductType($productId, $productType)
                ->where('buyer_id2', $buyer_id)
                ->orderBy('id', 'asc')
                ->first();

            $targetRow = $rowToUpdate ?? $existingDestinationRow;

            if ($targetRow) {
                $targetRow->fill($updatePriceData);
                $targetRow->buyer_id2 = $buyer_id;
                $targetRow->productType = $productType;
                $targetRow->save();
            } else {
                $data = array_merge($pricingData, $updatePriceData);
                $data['created_at'] = now();
                $data['updated_at'] = now();
                // Only pass keys that exist as columns (avoid "Unknown column" on older DBs)
                $fillable = (new pricingTable)->getFillable();
                $data = array_intersect_key($data, array_flip($fillable));
                $data['product_id'] = $basePricing->product_id;
                $data['buyer_id'] = $basePricing->buyer_id;
                $data['buyer_id2'] = $buyer_id;
                $data['productType'] = $productType;
                $data['destination'] = $tempBuyerCountry->country;
                $data['created_at'] = now();
                $data['updated_at'] = now();
                pricingTable::create($data);
            }
        } catch (\Exception $e) {
            // ACTUALLY LOG THE ERROR SO YOU CAN DEBUG IT!
            \Log::error("Bulk Update Failed for Product ID {$productId}, Buyer ID {$buyer_id} | Error: " . $e->getMessage() . " on line " . $e->getLine());
            $dataSaveIssueIds[$key] = $buyer_id;
        }
    }

    if (count($dataSaveIssueIds) > 0) {
        return redirect('/buyer/create-bulk-pricing')->with('warning', 'Bulk Pricing processed, but some items failed. Please check storage/logs/laravel.log for details.');
    }

    $redirect = redirect('/buyer/create-bulk-pricing')->with('success', 'Bulk Pricing was added successfully.');

    if ($duplicateDestinationWarnings !== []) {
        $lines = array_map(static function (array $warning) {
            return sprintf(
                '%s had duplicate destination rows; updated pricing id %d — please delete duplicate id(s): %s manually.',
                $warning['code'],
                $warning['updated_id'],
                implode(', ', $warning['duplicate_ids'])
            );
        }, $duplicateDestinationWarnings);

        $redirect->with(
            'warning',
            'Bulk update completed, but some products still have duplicate destination rows in the database. '
            . implode(' ', $lines)
        );
    }

    return $redirect;
}
    function changeCourierRate($courierData, $volWt)
    {
        if ($courierData) {
            // Base rate + weight-band overage (highest-band-only) + fuel %.
            return \App\Support\CourierWeightRateBands::baseRateWithFuel($courierData, (float) $volWt);
        } else {
            return 0;
        }
    }

    function changeVolumetricUnit($pricingData, $settingData, $country)
    {
        return \App\Support\PricingVolumetricWeight::raw($pricingData, $settingData, (string) $country);
    }

    /**
     * Buyer 2 bulk pricing: same INR stack as pricing allCost(), but only dropship packaging counts
     * (wSPackageCost is excluded so wholesale + dropship are never double-counted for FOB / landed / delivery).
     *
     * @return array{costPrice: float, adminCost: float, finalCost: float}
     */
    protected function computeCostStackForBuyer2Bulk(array $pricingData): array
    {
        $pricingData['wSPackageCost'] = 0;

        return self::computeMainPricingCostStack($pricingData);
    }

    /**
     * Compute volumetric weight in LBS — same as pricing view: each cm÷2.54 ceil to integer inch, then in³/divisor.
     */
    protected function computeVolWtLbs(array $pricingData, array $settingData, string $country): float
    {
        $width = (float)($pricingData['boxwidth'] ?? 0);
        $height = (float)($pricingData['boxheight'] ?? 0);
        $depth = (float)($pricingData['boxdepth'] ?? 0);
        if ($width <= 0 || $height <= 0 || $depth <= 0) {
            return (float)($pricingData['volWt_lbs'] ?? 0);
        }
        $country = strtolower($country);
        $divisor = 166;
        if ($country === 'us') {
            $divisor = (float)($settingData['us_volumetric_weight_lbs'] ?? 166) ?: 166;
        } elseif ($country === 'canada') {
            $divisor = (float)($settingData['canada_volumetric_weight_lbs'] ?? 166) ?: 166;
        } else {
            $divisor = (float)($settingData['volumetric_weight_lbs'] ?? 166) ?: 166;
        }
        $wIn = (int) ceil($width / 2.54);
        $hIn = (int) ceil($height / 2.54);
        $dIn = (int) ceil($depth / 2.54);

        return ($wIn * $hIn * $dIn) / $divisor;
    }

    /**
     * Resolve courier from temp buyer setting for bulk/pricing auto-pick.
     * null / '' / 0 = No Courier → return null (do not apply country default).
     * Otherwise return the country default courier (same as previous behaviour).
     */
    private function resolveCourierForTempBuyer($tempBuyer): ?courier
    {
        if (! $tempBuyer) {
            return null;
        }
        $type = $tempBuyer->courierType ?? null;
        if ($type === null || $type === '' || (int) $type === 0) {
            return null;
        }

        return courier::where('country', $tempBuyer->country)
            ->where('is_default', 1)
            ->first();
    }

    /**
     * Buyer-2 courier line: base rate + block engine or legacy/object custom_condition (matches pricing view before UK cap / Palletways upgrade).
     */
    private function computeBuyer2CourierCost(
        ?courier $courierData,
        array $pricingData,
        float $volumetricActualUnit,
        float $volWtLbs,
        string $checkCountry,
        array $getSettingData,
        string $destinationCountryRaw
    ): float {
        if (! $courierData) {
            return 0.0;
        }

        $chargeableWeight = PricingChargeableWeight::forBaseCourierRate(
            $pricingData,
            $checkCountry,
            $volumetricActualUnit
        );
        $courierCost = (float) $this->changeCourierRate($courierData, $chargeableWeight);
        $blockCountry = UsCourierRuleService::normalizeCountry($destinationCountryRaw);
        $payload = null;
        if (UsCourierRuleService::tablesExist() && UsCourierRuleService::supportsBlockEngine($blockCountry)) {
            $payload = UsCourierRuleService::buildPayload((int) $courierData->id, $blockCountry);
        }
        if ($payload !== null) {
            $boxWtKg = self::resolveBoxWtKg($pricingData);
            $engine = $payload['engine'] ?? '';
            if ($engine === 'us_blocks_v1') {
                $volKgForMetrics = (float) ceil((float) $this->changeVolumetricUnit($pricingData, $getSettingData, 'uk'));

                return CourierBlockEngineCalculator::finalCost(
                    (float) $courierCost,
                    $payload,
                    (float) ($pricingData['boxwidth'] ?? 0),
                    (float) ($pricingData['boxheight'] ?? 0),
                    (float) ($pricingData['boxdepth'] ?? 0),
                    $volKgForMetrics,
                    $volWtLbs,
                    $boxWtKg
                );
            }
            if ($engine === 'uk_blocks_v1') {
                $volKgForMetrics = (float) ceil((float) $this->changeVolumetricUnit($pricingData, $getSettingData, $checkCountry));

                return CourierBlockEngineCalculator::finalCost(
                    (float) $courierCost,
                    $payload,
                    (float) ($pricingData['boxwidth'] ?? 0),
                    (float) ($pricingData['boxheight'] ?? 0),
                    (float) ($pricingData['boxdepth'] ?? 0),
                    $volKgForMetrics,
                    0.0,
                    $boxWtKg
                );
            }
        }
        if (! empty($courierData->custom_condition)) {
            return (float) $this->calCulateSurchargeCustomCondition(
                $courierData->custom_condition,
                $courierCost,
                $pricingData,
                $volumetricActualUnit,
                $checkCountry
            );
        }

        return $courierCost;
    }

    public function getProductByBuyer(Request $request)
    {
        $mainBuyerId = $request['mainBuyerId'];
        // $sourceBuyerId = $request['sourceBuyerId'];
        $pricingData = pricingTable::where('buyer_id',$mainBuyerId)->get()->toarray();
        $pricingDataId = array_unique(array_column($pricingData, 'product_id'));
        $products = product::whereIn('id', $pricingDataId)->get();
        return  $products;
    }

    /**
     * Return source product's pricing row for selected buyer (for bulk all cost input).
     * Same data used by update_bulk_all_cost_input; frontend runs same JS calculation as pricing view.
     */
    public function getSourceProductPricing(Request $request)
    {
        $mainBuyerId = $request->input('main_buyer_id');
        $sourceProductId = $request->input('source_product_id');
        if (!$mainBuyerId || !$sourceProductId) {
            return response()->json(null, 400);
        }
        $sourceRow = pricingTable::where('product_id', $sourceProductId)
            ->where('buyer_id', $mainBuyerId)
            ->where(function ($q) {
                $q->whereNull('buyer_id2')->orWhere('buyer_id2', '');
            })
            ->orderBy('id', 'asc')
            ->first();
        if (!$sourceRow) {
            return response()->json(null, 404);
        }
        $row = [
            'buyingCost' => (float)($sourceRow->buyingCost ?? 0),
            'tapestryCost' => (float)($sourceRow->tapestryCost ?? 0),
            'fillerCost' => (float)($sourceRow->fillerCost ?? 0),
            'labourCost' => (float)($sourceRow->labourCost ?? 0),
            'hardwareCost1' => (float)($sourceRow->hardwareCost1 ?? 0),
            'hardwareCost2' => (float)($sourceRow->hardwareCost2 ?? 0),
            'hardwareCost3' => (float)($sourceRow->hardwareCost3 ?? 0),
            'hardwareCost4' => (float)($sourceRow->hardwareCost4 ?? 0),
            'hardwareCost5' => (float)($sourceRow->hardwareCost5 ?? 0),
            'polishCost' => (float)($sourceRow->polishCost ?? 0),
            'wSPackageCost' => (float)($sourceRow->wSPackageCost ?? 0),
            'dSPackageCost' => (float)($sourceRow->dSPackageCost ?? 0),
            'shippingCost' => (float)($sourceRow->shippingCost ?? 0),
            'adminCostPercent' => (float)($sourceRow->adminCostPercent ?? 0),
            'profitPercent' => (float)($sourceRow->profitPercent ?? 0),
            'boxwidth' => (float)($sourceRow->boxwidth ?? 0),
            'boxheight' => (float)($sourceRow->boxheight ?? 0),
            'boxdepth' => (float)($sourceRow->boxdepth ?? 0),
            'boxWt' => $sourceRow->boxWt ?? null,
        ];
        $firstSetting = setting::first();
        $getSettingData = $firstSetting ? $firstSetting->toArray() : [];
        $volWt = 0;
        $volWtLbs = 0;
        if ($firstSetting && $row['boxwidth'] > 0 && $row['boxheight'] > 0 && $row['boxdepth'] > 0) {
            $volWt = $this->changeVolumetricUnit(array_merge($row, ['volWt' => 0, 'volWt_lbs' => 0]), $getSettingData, 'uk');
            $volWt = (float) ceil((float) $volWt);
            $volWtLbs = $this->computeVolWtLbs(array_merge($row, ['volWt' => 0, 'volWt_lbs' => 0]), $getSettingData, 'us');
            $volWtLbs = (float) ceil((float) $volWtLbs);
        }
        $row['volWt'] = $volWt;
        $row['volWt_lbs'] = $volWtLbs;
        return response()->json($row);
    }

    function getCustomConditionValue($courierType){
		$customCondition = 0;
		$customConditionArray = courier::select('custom_condition')->where('name',$courierType)->first();
		if(!empty($customConditionArray->custom_condition)){
			$customCondition = $customConditionArray->custom_condition;
		}
		return $customCondition;
    }

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

        return $this->calCulateSurchargeCustomConditionLegacyNumericFormat(
            $customConditionsArray,
            $courierCost,
            $pricingData,
            $volumetricActualUnit
        );
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

    private function calCulateSurchargeCustomConditionLegacyNumericFormat(
        array $customConditionsArray,
        $courierCost,
        $pricingData,
        $volumetricActualUnit
    ): float {
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

    public function getSourceBuyer(Request $request)
    {
        $mainBuyerId = $request['mainBuyerId'];
        $buyer_id2 = pricingTable::where('buyer_id', $mainBuyerId)
            ->distinct('buyer_id2')
            ->pluck('buyer_id2');

        $sourceBuyers = tempBuyer::whereIn('id', $buyer_id2)->get();

        return $sourceBuyers;
    }
}
