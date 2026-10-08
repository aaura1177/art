<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\smallhardwares;
use App\smhsupplier;
use App\smallHardwareProducts;


class SmallHardwaresImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
        $keys = array_keys($rows[0]->toArray());
        $smallhardware_name = ucwords(str_replace('_',' ',$keys[1]));
        $smallhardware = smallhardwares::where('name',$smallhardware_name)->first();
        
        // $supplier = smhsupplier::where('c_name',$rows[0]['supplier'])->select('id')->first();
        // if(!isset($supplier->id)){
        //     $supplier = smhsupplier::create([
        //         'c_name' => $rows[0]['supplier'],
        //         'name' => $rows[0]['supplier']
        //     ]);
        // }
        // if(!isset($smallhardware->id)){
        //     $smallhardware = smallhardwares::create([
        //         'name' => $smallhardware_name,
        //         'supplier' => $supplier->id
        //     ]);
        // }
		foreach($rows as $row){
            
			$product1 = product::where('code', $row['product_code'])->first();
			
			if(isset($product1->id)){
				$product_id = $product1->id;
                if(isset($row['uk18'])){
                    if($row['uk18'] == 'Yes'){
                        $buyer = 1;
                    }
                    if($row['uk18'] == 'No'){
                        $buyer = 2;
                    }
                    if($row['uk18'] == 'Common'){
                        $buyer = 3;
                    }
                }else{
                    $buyer = 1;
                }
				$smallHardwareProducts = smallHardwareProducts::where('product_id',$product_id)->where('small_hardware_id',$smallhardware->id)->where('buyer',$buyer)->first();
				if(!isset($smallHardwareProducts->id)){
                    if($row[$keys[1]] != ''){
                        smallHardwareProducts::create([
                            'product_id'=> $product_id,
                            'small_hardware_id'=>$smallhardware->id,
                            'quantity' => $row[$keys[1]],
                            'size' => $row['size'],
                            'price' => $row['rate'],
                        ]);
                    }
				}else{
                    $smallHardwareProducts->quantity = $row[$keys[1]];
                    $smallHardwareProducts->size = $row['size'];
                    $smallHardwareProducts->price = $row['rate'];
                    $smallHardwareProducts->save();
                }
			}
		}
    }
}
