<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesPricingExportFormulas;
use App\pricingTable;
use App\tempProduct;
use App\Support\DestinationPricingPolicy;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EachPricingExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use AppliesPricingExportFormulas;

    public function __construct($id)
    {
    	$this->id = $id;
    }

    /**
     * Single-row export: document only that row's destination courier logic.
     */
    protected function pricingExportNotesCountry(): ?string
    {
        $destination = pricingTable::where('id', $this->id)->value('destination');

        return $this->normalizeNotesCountry(is_string($destination) ? $destination : null);
    }

    /**
     * Hybrid formulas for derived columns. Inputs + Courier Cost stay as values.
     * Existing formula columns remain A…BF; India-only inputs are appended BG…BL.
     */
    public function registerEvents(): array
    {
        return $this->pricingFormulaEvents([
            'costPrice' => 'AE',
            'adminCostPercent' => 'AF',
            'adminCost' => 'AG',
            'profitPercent' => 'AH',
            'finalCost' => 'AI',
            'shippingCost' => 'AD',
            'destination' => 'AP',
            'converRate' => 'AK',
            'fob' => 'AL',
            'tariff' => 'AM',
            'tariffFob' => 'AN',
            'ship2' => 'AS',
            'storage' => 'AT',
            'outbound' => 'AU',
            'qa' => 'AV',
            'landed' => 'AW',
            'adminProfit' => 'AX',
            'adminPrice' => 'AY',
            'finalPricePer' => 'AZ',
            'finalPrice' => 'BA',
            'courierCost' => 'BC',
            'deliveryCost' => 'BD',
            'adjustment' => 'BE',
            'newDelCost' => 'BF',
        ], $this->headings());
    }

    public function query()
    {
    	$pricing_id = $this->id;

        $pricingTable = pricingTable::where('id', $pricing_id)->select('product_id', 'buyer_id', 'startDate', 'endDate', 'remarks', 'productheight', 'productwidth', 'productdepth', 'boxheight', 'boxwidth', 'boxdepth', 'wholesalevolume', 'dropshipvolume', 'buyingCost', 'fabricCost', 'tapestryConsumed', 'tapestryUnitCost', 'tapestryCost', 'fillerCost', 'labourCost', 'hardwareCost1', 'hardwareCost2', 'hardwareCost3', 'hardwareCost4', 'hardwareCost5', 'pUnitCost', 'polishCost', 'wSPackageCost', 'dSPackageCost', 'shippingCost', 'costPrice', 'adminCostPercent', 'adminCost', 'profitPercent', 'finalCost', 'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent', 'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost', 'currency', 'converRate', 'fobINCost', 'tariff_percent', 'final_fob_in_cost', 'buyer_id2', 'destination', 'boxWt', 'volWt', 'shippingCost2', 'StorageCost', 'adminCost2', 'qualityAssurance', 'landedCost', 'adminProfit', 'adminPrice', 'finalPricePer', 'finalPrice', 'courierType', 'courierCost', 'deliveryCost', 'adjustment', 'newDelCost', 'productType');
        return $pricingTable;
    }

    public function headings(): array {
        $isIndiaExport = $this->pricingExportNotesCountry() === 'INDIA';
        $headings = [
           "Product","Buyer","Valid From","Valid Till","Remarks","Product Height","Product Width","Product Depth","Box Height","Box Width","Box Depth","WholeSale volume","DropShip Volume","Buying Cost","Fabric Type","Tapestry Consumed(m)","Tapestry Unit Cost", "Tapestry Cost","FillerCost","Labour Cost","Hardware Cost1", "Hardware Cost2", "Hardware Cost3", "Hardware Cost4", "Hardware Cost5", "Polish Unit Cost","Polish Cost","WholeSale Package Cost","DropShip Package Cost",
           $isIndiaExport ? 'India Shipping Cost' : 'Shipping Cost',
           $isIndiaExport ? 'India Cost Price' : 'Cost Price',
           $isIndiaExport ? 'Admin Cost(%) (India)' : 'Admin Cost(%)',
           $isIndiaExport ? 'Admin Cost (India)' : 'Admin Cost',
           $isIndiaExport ? 'Profit(%) (India)' : 'Profit(%)',
           $isIndiaExport ? 'Final Cost (India)' : 'Final Cost',
           "Currency","Conversion Rate","FOB Indian Cost","Tariff","Tariff-Adjusted FOB Cost","Buyer2","Destination","Box Wt(kg)","Volumetric Wt(kg)","Shipping Cost","Storage(1.5 month)","Outbound Charges","Quality Assurance","Landed Cost","Admin Profit(%)","Admin Price","Final Price(%)","Final Price","Courier Type","Courier Cost","Delivered Cost", "Adjustment", "Final Delivered Cost"
        ];

        return $headings;
    }

    public function map($pricingTable): array
    {
      if($pricingTable->productType == 0){
          $product = $pricingTable->tempProduct->code . " - " . $pricingTable->tempProduct->name;
      }

      if($pricingTable->productType == 1){
          $product = $pricingTable->product->code . " - " . $pricingTable->product->name;
      }

      if($pricingTable->buyer_id2 != null){
          $buyer2 = $pricingTable->tempBuyer2->c_name;
      }
      else{
          $buyer2 = null;
      }

        return[
           $product,
           $pricingTable->tempBuyer->c_name,
           $pricingTable->startDate,
           $pricingTable->endDate,
           $pricingTable->remarks,
           $pricingTable->productheight,
           $pricingTable->productwidth,
           $pricingTable->productdepth,
           $pricingTable->boxheight,
           $pricingTable->boxwidth,
           $pricingTable->boxdepth,
           $pricingTable->wholesalevolume,
           $pricingTable->dropshipvolume,
           $pricingTable->buyingCost,
           $pricingTable->fabricCost,
           $pricingTable->tapestryConsumed,
           $pricingTable->tapestryUnitCost,
           $pricingTable->tapestryCost,
           $pricingTable->fillerCost,
           $pricingTable->labourCost,
           $pricingTable->hardwareCost1,
           $pricingTable->hardwareCost2,
           $pricingTable->hardwareCost3,
           $pricingTable->hardwareCost4,
           $pricingTable->hardwareCost5,
           $pricingTable->pUnitCost,
           $pricingTable->polishCost,
           $pricingTable->wSPackageCost,
           $pricingTable->dSPackageCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->indiaShippingCost ?? 0)
               : $pricingTable->shippingCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->indiaCostPrice ?? 0)
               : $pricingTable->costPrice,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->indiaAdminCostPercent ?? 0)
               : $pricingTable->adminCostPercent,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->indiaAdminCost ?? 0)
               : $pricingTable->adminCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->indiaProfitPercent ?? 0)
               : $pricingTable->profitPercent,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->indiaFinalCost ?? 0)
               : $pricingTable->finalCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? ($pricingTable->currency ?: '₹')
               : $pricingTable->currency,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->converRate ?? 0)
               : $pricingTable->converRate,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->fobINCost ?? 0)
               : $pricingTable->fobINCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->tariff_percent ?? 0)
               : $pricingTable->tariff_percent,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->final_fob_in_cost ?? 0)
               : $pricingTable->final_fob_in_cost,
           $buyer2,
           $pricingTable->destination,
           $pricingTable->boxWt,
           $pricingTable->volWt,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->shippingCost2 ?? 0)
               : $pricingTable->shippingCost2,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->StorageCost ?? 0)
               : $pricingTable->StorageCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->adminCost2 ?? 0)
               : $pricingTable->adminCost2,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->qualityAssurance ?? 0)
               : $pricingTable->qualityAssurance,
           $pricingTable->landedCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->adminProfit ?? 0)
               : $pricingTable->adminProfit,
           $pricingTable->adminPrice,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->finalPricePer ?? 0)
               : $pricingTable->finalPricePer,
           $pricingTable->finalPrice,
           $pricingTable->courierType,
           $pricingTable->courierCost,
           $pricingTable->deliveryCost,
           DestinationPricingPolicy::isIndia($pricingTable->destination)
               ? (float) ($pricingTable->adjustment ?? 0)
               : $pricingTable->adjustment,
           $pricingTable->newDelCost,
        ];
    }
}
