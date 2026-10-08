<?php

namespace App\Exports;

use App\pbTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class inReport implements FromQuery, WithHeadings, WithMapping
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

        $pbTable = pbTable::whereBetween('created_at', [$newfsd, $newfed])->where('product_id',$this->product_id)->orderBy('created_at', 'desc');
        return $pbTable;
    }

    public function headings(): array {
        return [
           "Product Code","Product Name","Quantity","Rate","Total Amount","Date"
        ];
    }

    public function map($pbTable): array
    {
        return [
            $pbTable->product->code,
            $pbTable->product->name,
            $pbTable->receiveqty,
            $pbTable->rate,
            $pbTable->amount,
            date('d M Y', strtotime($pbTable->created_at))
        ];
    }
}
