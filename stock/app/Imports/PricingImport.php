<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\pricingTable;
use App\product;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\hardwares;


class PricingImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $request){
			$pricingTable = pricingTable::where('id', $request['id'])->first();
			if(isset($pricingTable->id)){
    		
				$buyer1 = tempBuyer::where('c_name', $request['buyer'])->first();
				$buyer_id = $buyer1->id;
				
				if($request['buyer2'] != null){
					$buyer2 = tempBuyer::where('c_name', $request['buyer2'])->first();
					$buyer_id2 = $buyer2->id;	
				}
				else{
					$buyer_id2 = null;
				}
				if($request['tapestry_unit_cost'] == "" || $request['tapestry_unit_cost'] == null){
					$request['tapestry_unit_cost'] = 0;
				}
				
				$pricingTable->buyer_id = $buyer_id;
				if(strstr($request['valid_from'],"-")){
					$pricingTable->startDate = $request['valid_from'];
				}elseif(strstr($request['valid_from'],"/")){
                                        $valid_from_slash = explode('/',$request['valid_from']);
                                        $valid_from = $valid_from_slash[2] . '-'. $valid_from_slash[0] . '-' . $valid_from_slash[1];
                                        $pricingTable->startDate = $valid_from;
                                }else{
					$excel_date = $request['valid_from']; //here is that value 41621 or 41631
					$unix_date = ($excel_date - 25569) * 86400;
					$excel_date = 25569 + ($unix_date / 86400);
					$unix_date = ($excel_date - 25569) * 86400;
					$pricingTable->startDate =  gmdate("Y-m-d", $unix_date);
				}
				if(strstr($request['valid_till'],"-")){
					$pricingTable->endDate = $request['valid_till'];
				}elseif(strstr($request['valid_till'],"/")){
                                        $valid_till_slash = explode('/',$request['valid_till']);
                                        $valid_till = $valid_till_slash[2] . '-'. $valid_till_slash[0] . '-' . $valid_till_slash[1];
                                        $pricingTable->endDate = $valid_till;
                                }else{
					$excel_date = $request['valid_till']; //here is that value 41621 or 41631
					$unix_date = ($excel_date - 25569) * 86400;
					$excel_date = 25569 + ($unix_date / 86400);
					$unix_date = ($excel_date - 25569) * 86400;
					$pricingTable->endDate =  gmdate("Y-m-d", $unix_date);
				}
				
				$hd1 = hardwares::where('name', $request['hardware_1'])->first();
				$hd2 = hardwares::where('name', $request['hardware_2'])->first();
				$hd3 = hardwares::where('name', $request['hardware_3'])->first();
				$hd4 = hardwares::where('name', $request['hardware_4'])->first();
				$hd5 = hardwares::where('name', $request['hardware_5'])->first();
				if($hd1){
					$pricingTable->hardware1 = $hd1->id;
				}
				if($hd2){
					$pricingTable->hardware2 = $hd2->id;
				}
				if($hd3){
					$pricingTable->hardware3 = $hd3->id;
				}
				if($hd4){
					$pricingTable->hardware4 = $hd4->id;
				}
				if($hd5){
					$pricingTable->hardware5 = $hd5->id;
				}
				$pricingTable->remarks = $request['remarks'];
				$pricingTable->productheight = $request['product_height'];
				$pricingTable->productwidth = $request['product_width'];
				$pricingTable->productdepth = $request['product_depth'];
				$pricingTable->boxheight = $request['box_height'];
				$pricingTable->boxwidth = $request['box_width'];
				$pricingTable->boxdepth = $request['box_depth'];
				$pricingTable->wholesalevolume = $request['wholesale_volume'];
				$pricingTable->dropshipvolume = $request['dropship_volume'];
				$pricingTable->buyingCost = $request['buying_cost'];
				$pricingTable->fabricCost = $request['fabric_type'];
				$pricingTable->tapestryConsumed = $request['tapestry_consumedm'];
				$pricingTable->tapestryUnitCost = $request['tapestry_unit_cost'];
				$pricingTable->tapestryCost = $request['tapestry_cost'];
				$pricingTable->fillerCost = $request['fillercost'];
				$pricingTable->labourCost = $request['labour_cost'];
				for($i = 1;$i <= 5;$i++){
					$hdq = "hardware".$i."_quantity";
					$hdcost = "hardwareCost".$i;
					$pricingTable->$hdq = $request['hardware_'.$i.'_quantity'];
					$pricingTable->$hdcost = $request['hardware_cost'.$i];
				}
				
				$pricingTable->pUnitCost = $request['polish_unit_cost'];
				$pricingTable->polishCost = $request['polish_cost'];
				$pricingTable->wSPackageCost = $request['wholesale_package_cost'];
				$pricingTable->dSPackageCost = $request['dropship_package_cost'];
				$pricingTable->shippingCost = $request['shipping_cost_india'];
				$pricingTable->costPrice = $request['cost_price'];
				$pricingTable->adminCostPercent = $request['admin_cost_percent'];
				$pricingTable->adminCost = $request['admin_cost'];
				$pricingTable->profitPercent = $request['profit'];
				$pricingTable->finalCost = $request['final_cost'];
				$pricingTable->currency = $request['currency'];
				$pricingTable->converRate = $request['conversion_rate'];
				$pricingTable->fobINCost = $request['fob_indian_cost'];
				if (isset($request['tariff'])) {
					$pricingTable->tariff_percent = $request['tariff'];
				}
				if (isset($request['tariff_adjusted_fob_cost'])) {
					$pricingTable->final_fob_in_cost = $request['tariff_adjusted_fob_cost'];
				}
				$pricingTable->boxWt = $request['box_wtkg'];
				$pricingTable->volWt = $request['volumetric_wtkg'];
				$pricingTable->shippingCost2 = $request['shipping_cost_uk'];
				$pricingTable->StorageCost = $request['storage15_month'];
				$pricingTable->adminCost2 = $request['outbound_charges'];
				$pricingTable->qualityAssurance = $request['quality_assurance'];
				$pricingTable->landedCost = $request['landed_cost'];
				$pricingTable->adminProfit = $request['admin_profit'];
				$pricingTable->adminPrice = $request['admin_price'];
				$pricingTable->finalPricePer = $request['final_price_percent'];
				$pricingTable->finalPrice = $request['final_price'];
				$pricingTable->courierType = $request['courier_type'];
				$pricingTable->courierCost = $request['courier_cost'];
				$pricingTable->deliveryCost = $request['delivered_cost'];
				$pricingTable->adjustment = $request['adjustment'];
				$pricingTable->newDelCost = $request['final_delivered_cost'];
				$pricingTable->buyer_id2 = $buyer_id2;
				$pricingTable->destination = $request['destination'];
				$pricingTable->save();
			}
    	}

    }
}
