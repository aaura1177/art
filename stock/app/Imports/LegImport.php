<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\legs;


class LegImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
		foreach($rows as $row){
			$product1 = product::where('code', $row['product_code'])->first();
			if(isset($product1->id)){
				$product_id = $product1->id;
				$legs = legs::where('product_id',$product_id)->first();
				if(isset($legs->id)){
					$leg_design = trim($row['leg_design']);
					$legs->product_id 	= $product_id;
					$legs->leg_design = ($row['leg_design'])?$leg_design:0;
					if($leg_design == 'Bridge'){
						$row['set'] = '2 Pairs';
					}else{
						if($leg_design == 'Island' && trim($row['set']) == ''){
							$row['set'] = 'Set of 4';
						}
					}
					$legs->qty = ($row['set'])?$row['set']:0;
					$legs->price 	= ($row['price'])?$row['price']:0;
					$legs->save();
				}else{
					$leg_design = trim($row['leg_design']);
					if($leg_design == 'Bridge'){
						$row['set'] = '2 Pairs';
					}else{
						if($leg_design == 'Island' && trim($row['set']) == ''){
							$row['set'] = 'Set of 4';
						}
					}
					legs::create([
						'product_id' 	=> $product_id,
						'leg_design' 	=> ($row['leg_design'])?$leg_design:0,
						'qty' 	=> ($row['set'])?$row['set']:0,
						'price' 	=> ($row['price'])?$row['price']:0
					]);
				}
			}
		}
    }
}
