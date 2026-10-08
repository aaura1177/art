<?php

namespace App\Exports;

use App\stockoutTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class outReport implements FromQuery, WithHeadings, WithMapping
{
	use Exportable;

    public function __construct($fsd, $fed, $product_id)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
        $this->product_id = $product_id;
    }

    public function query()
    {
    	$fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d',$fsd1) . " 00:00:00";

    	$fed1 = strtotime($this->fed);
    	$newfed = date('Y-m-d',$fed1) . " 23:59:59";

        $stockoutTable = stockoutTable::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$this->product_id)->orderBy('created_at', 'desc');
        return $stockoutTable;
    }

    public function headings(): array {
        return [
           "Product Code","Product Name","Quantity","Date"
        ];
    }

    public function map($stockoutTable): array
    {
        return [
            $stockoutTable->product->code,
            $stockoutTable->product->name,
            $stockoutTable->receiveqty,
            date('d M Y', strtotime($stockoutTable->created_at))
        ];
    }
}
