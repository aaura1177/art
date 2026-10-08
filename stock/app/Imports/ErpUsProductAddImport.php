<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\ErpUsProduct;
use App\ErpUsSheet;
use App\ErpUsHistory;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;


class ErpUsProductAddImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $row){
            $sheet = ErpUsSheet::where('type','add')->orderBy('id','desc')->first();
			$product1 = ErpUsProduct::where('sku', $row['sku'])->first();
            if(isset($product1->id)){
                $quantity = $product1->quantity + $row['quantity'];
                $product1->quantity = $quantity;
            }else{
                $product1 = new ErpUsProduct;
                $product1->sku = $row['sku'];
                $product1->quantity = $row['quantity'];
            }
    		$product1->save();
            $history = ErpUsHistory::create([
                'sheet_id'=>$sheet->id,
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'type' => 'add',
                'date' => $sheet->date,
                'stock' => $product1->quantity
            ]);
    	}

    }
}
