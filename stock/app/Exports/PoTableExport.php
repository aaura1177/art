<?php

namespace App\Exports;

use App\purchaseOrder;
use App\poTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class PoTableExport implements FromQuery, WithHeadings, WithTitle, WithMapping
{

    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    public function query()
    {
    	$fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d H:i:s',$fsd1);

    	$fed1 = strtotime($this->fed);
    	$newfed = date('Y-m-d H:i:s',$fed1);

        $purchaseOrder = purchaseOrder::whereBetween('podate', [$newfsd, $newfed])->select('id')->orderBy('id', 'ASC')->get();
        $poTable = poTable::whereIn('poid', $purchaseOrder)->select('poid', 'product_id', 'quantity', 'unit', 'rate', 'amount', 'gstslab', 'gstamount');
        
        return $poTable;
    }

    public function headings(): array {
        return [
           "PO","Product Code","EAN","HSN","Quantity","Unit","Rate","Amount","GST Slab","GST Amount"
        ];
    }

    public function title(): string
    {
        return 'Purchase Order Products ';
    }

     public function map($poTable): array
    {
        return [
            $poTable->purchaseOrderTable->pono,
            $poTable->product->code." - ".$poTable->product->name,
            $poTable->product->EAN,
            $poTable->product->HSN,
            $poTable->quantity,
            $poTable->unit,
            $poTable->rate,
            $poTable->amount,
            $poTable->gstslab,
            $poTable->gstamount
        ];
    }
}
