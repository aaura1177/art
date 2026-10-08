<?php

namespace App\Exports;

use App\purchaseOrder;
use App\samplePurchaseOrder;
use App\supplier;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class SamplesExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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

        $purchaseOrder = samplePurchaseOrder::whereBetween('podate', [$newfsd, $newfed])->select('pono', 'supplier_id', 'podate', 'del_date', 'ref_supplier', 'buyer_orderno', 'payterms', 'remarks', 'tgst', 'tquantity', 'subTotal', 'tamount')->orderBy('pono', 'ASC');

        return $purchaseOrder;
    }

    public function headings(): array {
        return [
           "PO No.","Supplier Name","PODate","Delivery Date","Supplier Ref.","Buyer Order No.","Payterms","Remarks","Total GST","Total Quantity","SubTotal","Total Amount"
        ];
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function map($purchaseOrder): array
    {
        return [
            $purchaseOrder->pono,
            $purchaseOrder->supplier->c_name,
            $purchaseOrder->podate,
            $purchaseOrder->del_date,
            $purchaseOrder->ref_supplier,
            $purchaseOrder->buyer_orderno,
            $purchaseOrder->payterms,
            $purchaseOrder->remarks,
            $purchaseOrder->tgst,
            $purchaseOrder->tquantity,
            $purchaseOrder->subTotal,
            $purchaseOrder->tamount
        ];
    }
}
