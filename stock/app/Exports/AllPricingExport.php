<?php

namespace App\Exports;

//use App\product;
use App\Exports\Concerns\AppliesPricingExportFormulas;
use App\pricingTable;
use App\hardwares;
use App\tempBuyer;
use App\Support\DestinationPricingPolicy;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllPricingExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{

    use Exportable;
    use AppliesPricingExportFormulas;

    protected $ids;
    protected $buyerId2;

    public function __construct($ids, $buyerId2 = null)
    {
        $this->ids = $ids;
        $this->buyerId2 = $buyerId2;
    }

    /**
     * When exporting for one destination buyer, notes cover that country only.
     * "All destinations" keeps the full multi-country courier guide.
     */
    protected function pricingExportNotesCountry(): ?string
    {
        if ($this->buyerId2 !== null && $this->buyerId2 !== '' && $this->buyerId2 !== 'all') {
            $fromBuyer = tempBuyer::where('id', (int) $this->buyerId2)->value('country');
            $normalized = $this->normalizeNotesCountry(is_string($fromBuyer) ? $fromBuyer : null);
            if ($normalized) {
                return $normalized;
            }
        }

        $listRowIds = array_values(array_filter(array_map('intval', explode(',', (string) $this->ids))));
        $pricingIds = pricingTable::resolveExportIds($listRowIds, $this->buyerId2);
        if ($pricingIds === []) {
            return null;
        }

        $destinations = pricingTable::whereIn('id', $pricingIds)
            ->pluck('destination')
            ->map(fn ($d) => $this->normalizeNotesCountry(is_string($d) ? $d : null))
            ->filter()
            ->unique()
            ->values();

        return $destinations->count() === 1 ? $destinations->first() : null;
    }

    /**
     * Hybrid formulas for derived columns. Inputs + Courier Cost stay as values.
     * Existing formula columns remain A…BR; India-only inputs are appended BS…BX.
     */
    public function registerEvents(): array
    {
        return $this->pricingFormulaEvents([
            'costPrice' => 'AQ',
            'adminCostPercent' => 'AR',
            'adminCost' => 'AS',
            'profitPercent' => 'AT',
            'finalCost' => 'AU',
            'shippingCost' => 'AP',
            'destination' => 'BB',
            'converRate' => 'AW',
            'fob' => 'AX',
            'tariff' => 'AY',
            'tariffFob' => 'AZ',
            'ship2' => 'BE',
            'storage' => 'BF',
            'outbound' => 'BG',
            'qa' => 'BH',
            'landed' => 'BI',
            'adminProfit' => 'BJ',
            'adminPrice' => 'BK',
            'finalPricePer' => 'BL',
            'finalPrice' => 'BM',
            'courierCost' => 'BO',
            'deliveryCost' => 'BP',
            'adjustment' => 'BQ',
            'newDelCost' => 'BR',
        ], $this->headings());
    }

    public function query()
    {
    	$listRowIds = array_values(array_filter(array_map('intval', explode(",", (string) $this->ids))));
        $pricing_ids = pricingTable::resolveExportIds($listRowIds, $this->buyerId2);
        $pricingTable = pricingTable::whereIn('id', $pricing_ids)->select('id','product_id', 'buyer_id', 'startDate', 'endDate', 'remarks', 'productheight', 'productwidth', 'productdepth', 'boxheight', 'boxwidth', 'boxdepth', 'wholesalevolume', 'dropshipvolume', 'buyingCost', 'fabricCost', 'tapestryConsumed', 'tapestryUnitCost', 'tapestryCost', 'fillerCost', 'labourCost', 'hardware1','hardware1_quantity', 'hardwareCost1','hardware2','hardware2_quantity', 'hardwareCost2','hardware3','hardware3_quantity', 'hardwareCost3', 'hardware4','hardware4_quantity','hardwareCost4','hardware5','hardware5_quantity','hardwareCost5', 'pUnitCost', 'polishCost', 'wSPackageCost', 'dSPackageCost', 'shippingCost', 'costPrice', 'adminCostPercent', 'adminCost', 'profitPercent', 'finalCost', 'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent', 'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost', 'currency', 'converRate', 'fobINCost', 'tariff_percent', 'final_fob_in_cost', 'buyer_id2', 'destination', 'boxWt', 'volWt', 'shippingCost2', 'StorageCost', 'adminCost2', 'qualityAssurance', 'landedCost', 'adminProfit', 'adminPrice', 'finalPricePer', 'finalPrice', 'courierType', 'courierCost', 'deliveryCost', 'adjustment', 'newDelCost', 'productType')->orderBy("id","asc");
        return $pricingTable;
    }

    public function headings(): array {
        $isIndiaExport = $this->pricingExportNotesCountry() === 'INDIA';
        $headings = [
            "id","SKU","Product","Buyer","Valid From","Valid Till","Remarks","Product Height","Product Width","Product Depth","Box Height","Box Width","Box Depth","WholeSale volume","DropShip Volume","Buying Cost","Fabric Type","Tapestry Consumed(m)","Tapestry Unit Cost", "Tapestry Cost","FillerCost","Labour Cost","Hardware 1","Hardware 1 Quantity","Hardware Cost1","Hardware 2","Hardware 2 Quantity", "Hardware Cost2","Hardware 3","Hardware 3 Quantity", "Hardware Cost3", "Hardware 4","Hardware 4 Quantity", "Hardware Cost4", "Hardware 5","Hardware 5 Quantity","Hardware Cost5", "Polish Unit Cost","Polish Cost","WholeSale Package Cost","DropShip Package Cost",
            $isIndiaExport ? 'India Shipping Cost' : 'Shipping CostIndia',
            $isIndiaExport ? 'India Cost Price' : 'Cost Price',
            $isIndiaExport ? 'Admin Cost Percent (India)' : 'Admin Cost Percent',
            $isIndiaExport ? 'Admin Cost (India)' : 'Admin Cost',
            $isIndiaExport ? 'Profit(%) (India)' : 'Profit(%)',
            $isIndiaExport ? 'Final Cost (India)' : 'Final Cost',
            "Currency","Conversion Rate","FOB Indian Cost","Tariff","Tariff-Adjusted FOB Cost","Buyer2","Destination","Box Wt(kg)","Volumetric Wt(kg)",
            $isIndiaExport ? 'Destination Shipping Cost' : 'Shipping Cost UK',
            "Storage(1.5 month)","Outbound Charges","Quality Assurance","Landed Cost","Admin Profit(%)","Admin Price","Final Price Percent","Final Price","Courier Type","Courier Cost","Delivered Cost", "Adjustment", "Final Delivered Cost"
        ];
        return $headings;
    }

    public function map($pricingTable): array
    {
		if($pricingTable->productType == 0){
          $productName = $pricingTable->tempProduct->name ?? '';
          $productCode 					= 	$pricingTable->tempProduct->code ?? '';
          $product = $productName . ' - ' .$productCode;
          $sku 						= 	$pricingTable->tempProduct->code ?? '';
		      $product_height 			=	$pricingTable->tempProduct->height ?? '';
          $product_width 			=	$pricingTable->tempProduct->width ?? '';
          $product_depth 			=	$pricingTable->tempProduct->depth ?? '';
          $product_boxheight 		=	$pricingTable->tempProduct->boxheight ?? '';
          $product_boxwidth 		=	$pricingTable->tempProduct->boxwidth ?? '';
          $product_boxdepth 		=	$pricingTable->tempProduct->boxdepth ?? '';
          $product_wholesalevolume 	=	$pricingTable->tempProduct->wholesalevolume ?? '';
          $product_dropshipvolume  	=   $pricingTable->tempProduct->dropshipvolume ?? '';
		}

		if($pricingTable->productType == 1){
      $productName = $pricingTable->tempProduct->name ?? '';
      $productCode 					= 	$pricingTable->tempProduct->code ?? '';
      $product = $productName . ' - ' .$productCode;
		  $sku 						= 	$pricingTable->product->code ?? '';
		  $product_height 			=	$pricingTable->product->height ?? '';
          $product_width 			=	$pricingTable->product->width ?? '';
          $product_depth 			=	$pricingTable->product->depth ?? '';
          $product_boxheight 		=	$pricingTable->product->boxheight ?? '';
          $product_boxwidth 		=	$pricingTable->product->boxwidth ?? '';
          $product_boxdepth 		=	$pricingTable->product->boxdepth ?? '';
          $product_wholesalevolume 	=	$pricingTable->product->wholesalevolume ?? '';
          $product_dropshipvolume  	=   $pricingTable->product->dropshipvolume ?? '';
		}

		if($pricingTable->buyer_id2 != null && isset($pricingTable->tempBuyer2->c_name)){
		  $buyer2 = $pricingTable->tempBuyer2->c_name;
		}
		else{
		  $buyer2 = null;
		}
		
		$hd1_name = "";
		$hd2_name = "";
		$hd3_name = "";
		$hd4_name = "";
		$hd5_name = "";
		
		if($pricingTable->hardware1){
			$hd1 = hardwares::where('id', $pricingTable->hardware1)->first();
			if($hd1){
				$hd1_name = $hd1->name;
			}
		}
		if($pricingTable->hardware2){
			$hd2 = hardwares::where('id', $pricingTable->hardware2)->first();
			if($hd2){
				$hd2_name = $hd2->name;
			}
		}
		if($pricingTable->hardware3){
			$hd3 = hardwares::where('id', $pricingTable->hardware3)->first();
			if($hd3){
				$hd3_name = $hd3->name;
			}
		}
		if($pricingTable->hardware4){
			$hd4 = hardwares::where('id', $pricingTable->hardware4)->first();
			if($hd4){
				$hd4_name = $hd4->name;
			}
		}
		if($pricingTable->hardware5){
			$hd5 = hardwares::where('id', $pricingTable->hardware5)->first();
			if($hd5){
				$hd5_name = $hd5->name;
			}
		}
        return [
			$pricingTable->id,
			$sku,
            $product,
           $pricingTable->tempBuyer->c_name,
           $pricingTable->startDate,
           $pricingTable->endDate,
           $pricingTable->remarks,
           $product_height,
           $product_width,
           $product_depth,
           $product_boxheight,
           $product_boxwidth,
           $product_boxdepth,
           $product_wholesalevolume,
           $product_dropshipvolume,
           $pricingTable->buyingCost,
           $pricingTable->fabricCost,
           $pricingTable->tapestryConsumed,
           $pricingTable->tapestryUnitCost,
           $pricingTable->tapestryCost,
           $pricingTable->fillerCost,
           $pricingTable->labourCost,
		   $hd1_name,
		   $pricingTable->hardware1_quantity,
           $pricingTable->hardwareCost1,
		   $hd2_name,
		   $pricingTable->hardware2_quantity,
           $pricingTable->hardwareCost2,
		   $hd3_name,
		   $pricingTable->hardware3_quantity,
           $pricingTable->hardwareCost3,
		   $hd4_name,
		   $pricingTable->hardware4_quantity,
           $pricingTable->hardwareCost4,
		   $hd5_name,
		   $pricingTable->hardware5_quantity,
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
