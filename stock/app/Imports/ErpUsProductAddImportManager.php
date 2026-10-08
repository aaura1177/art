<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\ErpUsProduct;
use App\ErpUsSheetManager;
use App\ErpUsHistoryManager;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;


class ErpUsProductAddImportManager implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
        $sheet = ErpUsSheetManager::where('type','sheet')->orderBy('id','desc')->first();
    	foreach($rows as $row){
			$product1 = ErpUsProduct::where('sku', $row['sku'])->first();
            $type = '';
            
            if(isset($product1->id)){
                if($product1->us_quantity > $row['quantity']){
                    $type = 'less';
                }
                if($product1->us_quantity < $row['quantity']){
                    $type = 'add';
                }
                $product1->sku = $row['sku'];
                $product1->us_quantity = $row['quantity'];
            }else{
                $product1 = new ErpUsProduct;
                $product1->sku = $row['sku'];
                $product1->us_quantity = $row['quantity'];
                $product1->quantity = 0;
            }
    		$product1->save();
            if($type != ''){
                $history = ErpUsHistoryManager::create([
                    'sheet_id'=>$sheet->id,
                    'sku' => $row['sku'],
                    'quantity' => $row['quantity'],
                    'type' => 'add',
                    'date' => $sheet->date,
                    'stock' => $product1->us_quantity
                ]);
            }
            $products_updated[] = $product1->id;
    	}
        $products_not_updated = ErpUsProduct::whereNotIn('id', $products_updated)->get();
        foreach($products_not_updated as $products_not){
            $type = 'less';
            $products_not_updated->us_quantity = 0;
            $history = ErpUsHistoryManager::create([
                'sheet_id'=>$sheet->id,
                'sku' => $product1->sku,
                'quantity' => $product1->quantity,
                'type' => 'less',
                'date' => $sheet->date,
                'stock' => 0
            ]);
            $products_not->save();
        }

    }
}
