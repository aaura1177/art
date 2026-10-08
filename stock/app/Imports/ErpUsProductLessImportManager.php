<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\ErpUsProduct;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;
use App\ErpUsHistoryManager;
use App\ErpUsSheetManager;


class ErpUsProductLessImportManager implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $row){
            $sheet = ErpUsSheetManager::where('type','less')->orderBy('id','desc')->first();
			$product1 = ErpUsProduct::where('sku', $row['sku'])->first();
            if(isset($product1->id)){
                $quantity = $product1->us_quantity - $row['quantity'];
                $product1->us_quantity = $quantity;
            }else{
                $product1 = new ErpUsProduct;
                $product1->sku = $row['sku'];
                $product1->us_quantity = $row['quantity'];
            }
    		$product1->save();
            $artisan_wayfair = '(Artisan)';
            if(isset($row['artisan_wayfair'])){
                $artisan_wayfair = '('.$row['artisan_wayfair'].')';
            }
            
            $history = ErpUsHistoryManager::create([
                'sheet_id'=>$sheet->id,
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'type' => 'less',
                'date' => $sheet->date,
                'stock' => $product1->us_quantity,
                'remark'=> 'Less via Order id - '.$row['order_id'] . $artisan_wayfair
            ]);
    	}

    }
}
