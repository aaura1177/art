<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\legs;


class LegsImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
		foreach($rows as $row){
			$product1 = product::where('code', $row['code'])->first();
			if(isset($product1->id)){
				$product_id = $product1->id;
				$legs = legs::where('product_id',$product_id)->first();
				if(isset($legs->id)){
					$legs->product_id 	= $product_id;
					$legs->leg_design = ($row['leg_design'])?$row['leg_design']:0;
					$legs->qty = ($row['set'])?$row['set']:0;
					$legs->price 	= ($row['price'])?$row['price']:0;
					$legs->save();
				}else{
					legs::create([
						'product_id' 	=> $product_id,
						'leg_design' 	=> ($row['leg_design'])?$row['leg_design']:0,
						'qty' 	=> ($row['set'])?$row['set']:0,
						'price' 	=> ($row['price'])?$row['price']:0
					]);
				}
			}
		}
    }
}
