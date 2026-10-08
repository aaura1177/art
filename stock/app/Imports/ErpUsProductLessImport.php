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
use App\ErpUsHistory;
use App\ErpUsSheet;


class ErpUsProductLessImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $row){
            $sheet = ErpUsSheet::where('type','less')->orderBy('id','desc')->first();
			$product1 = ErpUsProduct::where('sku', $row['sku'])->first();
            if(isset($product1->id)){
                $quantity = $product1->quantity - $row['quantity'];
                $product1->quantity = $quantity;
            }else{
                $product1 = new ErpUsProduct;
                $product1->sku = $row['sku'];
                $product1->quantity = $row['quantity'];
            }
    		$product1->save();
            $artisan_wayfair = '(Artisan)';
            if(isset($row['artisan_wayfair'])){
                $artisan_wayfair = '('.$row['artisan_wayfair'].')';
            }
            
            $history = ErpUsHistory::create([
                'sheet_id'=>$sheet->id,
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'type' => 'less',
                'date' => $sheet->date,
                'stock' => $product1->quantity,
                'remark'=> 'Less via Order id - '.$row['order_id'] . $artisan_wayfair
            ]);
    	}

    }
}
