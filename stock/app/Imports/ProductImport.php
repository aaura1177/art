<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;


class ProductImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $row){

			$product1 = product::where('code', $row['product_code'])->first();
			$pricingTable = pricingTable::where('product_id', $product1->id)->where('buyer_id2',3)->first();
			$product_id = $product1->id;
			if(isset($row['hardware_1'])){
				$hd1 = hardwares::where('name', $row['hardware_1'])->first();
			}
			if(isset($row['hardware_2'])){
				$hd2 = hardwares::where('name', $row['hardware_2'])->first();
			}
			if(isset($row['hardware_3'])){
				$hd3 = hardwares::where('name', $row['hardware_3'])->first();
			}
			if(isset($row['hardware_4'])){
				$hd4 = hardwares::where('name', $row['hardware_4'])->first();
			}
			if(isset($row['hardware_5'])){
				$hd5 = hardwares::where('name', $row['hardware_5'])->first();
			}
			
			if($hd1){
				$product1->hardware1 = $hd1->id;
			}
			if($hd2){
				$product1->hardware2 = $hd2->id;
			}
			if($hd3){
				$product1->hardware3 = $hd3->id;
			}
			if($hd4){
				$product1->hardware4 = $hd4->id;
			}
			if($hd5){
				$product1->hardware5 = $hd5->id;
			}
			if($pricingTable){
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
				for($i = 1;$i <= 5;$i++){
					$hdq = "hardware".$i."_quantity";
					$hdcost = "hardwareCost".$i;
					if(isset($row['hardware_'.$i.'_quantity'])){
						$pricingTable->$hdq = $row['hardware_'.$i.'_quantity'];
					}
					if(isset($row['hardware_cost'.$i])){
						$pricingTable->$hdcost = $row['hardware_cost'.$i];
					}
				}
				$pricingTable->save();
			}
			if(isset($row['location'])){
				if(trim($row['location']) != ""){
					$locations = explode("\n", $row['location']);
					productLocations::where("product_id",$product_id)->delete();
					foreach($locations as $location){
						if($location != ""){
							$loc = explode("-", $location);
							$l = trim($loc[0]);
							$q = trim($loc[1]);
							$pl = productLocations::create([
								'product_id' => $product_id,
								'location' => $l,
								'quantity' => $q,
							]);
						}
					}
				}else{
					productLocations::where("product_id",$product_id)->delete();
				}
			}
			
			if(isset($row['hardware_1_quantity'])){
				if($row['hardware_1_quantity'] != ''){
					$product1->hardware1_quantity = $row['hardware_1_quantity'];
				}
			}
			if(isset($row['hardware_2_quantity'])){
				if($row['hardware_2_quantity'] != ''){
					$product1->hardware2_quantity = $row['hardware_2_quantity'];
				}
			}
			if(isset($row['hardware_3_quantity'])){
				if($row['hardware_3_quantity'] != ''){
					$product1->hardware3_quantity = $row['hardware_3_quantity'];
				}
			}
			if(isset($row['hardware_4_quantity'])){
				if($row['hardware_4_quantity'] != ''){
					$product1->hardware4_quantity = $row['hardware_4_quantity'];
				}
			}
			if(isset($row['hardware_5_quantity'])){
				if($row['hardware_5_quantity'] != ''){
					$product1->hardware5_quantity = $row['hardware_5_quantity'];
				}
			}
			if(isset($row['upholstry'])){
				if($row['upholstry'] != ''){
					$product1->upholstry = $row['upholstry'];
				}
			}
			if(isset($row['corner'])){
				if($row['corner'] != ''){
					$product1->corner    = $row['corner'];
				}
			}
			if(isset($row['l'])){
				if($row['l'] != ''){
					$product1->lhardware = $row['l'];
				}
			}
			if(isset($row['finishing'])){
				if($row['finishing'] != ''){
					$product1->finishing = $row['finishing'];
				}
			}
			if(isset($row['finishing_price'])){
				if($row['finishing_price'] != ''){
					$product1->finishing_price = $row['finishing_price'];
				}
			}
			if(isset($row['quantity'])){
				if($row['quantity'] != ''){
					$product1->quantity = $row['quantity'];
				}
			}
			if(isset($row['width'])){
				if($row['width'] != ''){
					$product1->width = $row['width'];
				}
			}

			if(isset($row['height'])){
				if($row['height'] != ''){
					$product1->height = $row['height'];
				}
			}

			if(isset($row['depth'])){
				if($row['depth'] != ''){
					$product1->depth = $row['depth'];
				}
			}

			if(isset($row['box_height'])){
				if($row['box_height'] != ''){
					$product1->boxheight = $row['box_height'];
				}
			}
			if(isset($row['box_width'])){
				if($row['box_width'] != ''){
					$product1->boxwidth = $row['box_width'];
				}
			}
			if(isset($row['box_depth'])){
				if($row['box_depth'] != ''){
					$product1->boxdepth = $row['box_depth'];
				}
			}

			if(isset($row['box_height']) && isset($row['box_width']) && isset($row['box_depth'])){

				$boxoutput = (($row['box_height']-5)*($row['box_width']-5)*($row['box_depth']-5))/1000000;

				$boxdshipoutput = ($row['box_height'] * $row['box_width'] * $row['box_depth'])/1000000;

				$product1->wholesalevolume = number_format($boxoutput,4,'.','');
				$product1->dropshipvolume = number_format($boxdshipoutput,4,'.','');
			}

    		$product1->save();
    	}

    }
}
