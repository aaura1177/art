<?php

namespace App\Exports;

use App\purchaseOrder;
use App\samplePurchaseOrder;
use App\poTable;
use App\posTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class PosTableExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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
       /* dd($newfsd);
        dd($newfed);*/
        $purchaseOrder = samplePurchaseOrder::whereBetween('podate', [$newfsd, $newfed])->select('id')->orderBy('id', 'ASC')->get();
        $poTable = posTable::whereIn('poid', $purchaseOrder)->select('poid', 'product_id','sample_id', 'quantity', 'unit', 'rate', 'amount', 'gstslab', 'gstamount');
        
        return $poTable;
    }

    public function headings(): array {
        return [
           "PO","Sample Code","EAN","HSN","Quantity","Unit","Rate","Amount","GST Slab","GST Amount"
        ];
    }

    public function title(): string
    {
        return 'Purchase Order Samples ';
    }

     public function map($poTable): array
    {
        return [
            $poTable->samplePurchaseOrderTable->pono,
            $poTable->sample->code." - ".$poTable->sample->name,
            $poTable->sample->EAN,
            $poTable->sample->HSN,
            $poTable->quantity,
            $poTable->unit,
            $poTable->rate,
            $poTable->amount,
            $poTable->gstslab,
            $poTable->gstamount
        ];
    }
}
