<?php

namespace App\Imports;

use App\supplier;
use App\product;
use App\supplierProduct;
use App\Support\SupplierProductPriceLogWriter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class SuppliersImports implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $row){
			$supplier = supplier::where('c_name',$row['supplier_1st'])->first();
			$product = product::where('code',$row['code'])->first();
			if(isset($supplier->id) && isset($product->id)){
				$supplier_product = supplierProduct::where('product_id',$product->id)->where('supplier_id',$supplier->id)->first();
				
				if(isset($supplier_product->id)){
					$before = SupplierProductPriceLogWriter::buildState($supplier_product);
					$supplier_product->rate = $row['supplier_rate'];
					$supplier_product->save();
					SupplierProductPriceLogWriter::log($supplier_product, 'update', 'csv_import', $before);
				}else{
					$s = supplierProduct::create([
						'supplier_id'=>$supplier->id,
						'product_id'=>$product->id,
						'rate'=>$row['supplier_rate']
					]);
					SupplierProductPriceLogWriter::log($s, 'create', 'csv_import', null);
				}
				if($row['supplier_2nd'] != null){
					$supplier = supplier::where('c_name',$row['supplier_2nd'])->first();
					if(isset($supplier->id)){
						$supplier_product = supplierProduct::where('product_id',$product->id)->where('supplier_id',$supplier->id)->first();
						if(isset($supplier_product->id)){
							$before = SupplierProductPriceLogWriter::buildState($supplier_product);
							$supplier_product->rate = $row['supplier_rate2'];
							$supplier_product->save();
							SupplierProductPriceLogWriter::log($supplier_product, 'update', 'csv_import', $before);
						}else{
							$created = supplierProduct::create([
								'supplier_id'=>$supplier->id,
								'product_id'=>$product->id,
								'rate'=>$row['supplier_rate2'],
							]);
							SupplierProductPriceLogWriter::log($created, 'create', 'csv_import', null);
						}
					}
				}
			}
    	}
    }
}
