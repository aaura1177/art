<?php

namespace App\Exports;

use App\product;
use App\hardwares;
use App\pricingTable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductCodeWiseExport implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function collection() {
		if($this->code == "All"){
			$product	=	product::orderBy('code','ASC')->get();
		}else{
			$product	=	product::where('code','like','%'.$this->code.'%')->orderBy('code','ASC')->get();
		}
        return $product;
    }

    public function headings(): array {
        return [
           "Product Code","Product Name","Category Name","Sub-Category Name","Quantity","Location"
        ];
    }

    public function map($product): array
    {
		$location = "";
		
		if(isset($product->productLocations)){
			foreach($product->productLocations as $productLocation){
				$location = $location . $productLocation->location .' - '. $productLocation->quantity . "\n";
			}
		}
        return [
            $product->code,
            $product->name,
            $product->category->name,
            $product->subcategory->name,
            $product->quantity,
			$location
        ];
    }
}
