<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\smallhardware;
use App\smhsupplier;


class SmallHardwareImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
		foreach($rows as $row){
			$product1 = product::where('code', $row['product_code'])->first();
			$supplier = smhsupplier::where('c_name',$row['supplier'])->select('id')->first();
			if(isset($product1->id)){
				$product_id = $product1->id;
				$smallhardware = smallhardware::where('product_id',$product_id)->first();
				if(isset($smallhardware->id)){
					$smallhardware->dust_cover_size 		= $row['dust_cover_size'];
					$smallhardware->dust_cover_price 		= $row['dust_cover_price'];
					$smallhardware->pouch 					= $row['pouch'];
					$smallhardware->pouch_price 			= $row['pouch_price'];
					$smallhardware->supplier_id 			= $supplier->id;
					$smallhardware->save();
				}else{
					smallhardware::create([
						'product_id'=>$product1->id,
						'dust_cover_size'=>$row['dust_cover_size'],
						'dust_cover_price'=>$row['dust_cover_price'],
						'pouch'=>$row['pouch'],
						'pouch_price'=>$row['pouch_price'],
						'supplier_id' => $supplier->id
					]);
				}
			}
		}
    }
}
