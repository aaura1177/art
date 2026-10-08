<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\purchaseOrder;
use App\poTable;
use App\product;
use App\supplier;

class PurchaseOrderImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
        foreach($rows as $row){
            
            if($row['supplier_name'] != null){
    			$supplier = supplier::where('c_name', $row['supplier_name'])->first();
    			$supplier_id = $supplier->id;	
    		}

        	purchaseOrder::create([
        		'pono'=>strtoupper($rows['pono']),
                'supplier_id'=>$supplier_id,
                'podate'=>$rows['podate'],
                'del_date'=>$rows['del_date'],
                'ref_supplier'=>strtoupper($rows['ref_supplier']),
                'buyer_orderno'=>strtoupper($rows['buyer_orderno']),
                'payterms'=>$rows['payterms'],
                'remarks'=>$rows['remarks'],
                'subTotal'=>$rows['subtotalamount'],
                'tgst'=>$rows['tgst'],
                'tquantity'=>$rows['tquantity'],
                'tamount'=>$rows['tamount'],
                'remqty'=>$rows['tquantity']
        	]);
        }
		
    }
}