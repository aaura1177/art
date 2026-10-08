<?php

namespace App\Exports;

use App\purchaseBill;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class InwardSupplyProductsExport implements FromQuery, WithHeadings, WithTitle, WithMapping
{

    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    public function query()
    {
    	$fsd1 = strtotime($this->fsd);
    	$newfsd = date('Y-m-d',$fsd1);

    	$fed1 = strtotime($this->fed);
    	$newfed = date('Y-m-d',$fed1);

        $purchaseBill = purchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])->select('purchaseOrder_id', 'supp_inv_no', 'supp_inv_date', 'ewaybill', 'quantity', 'subtotal', 'gst', 'freight', 'total')->orderBy('supp_inv_date', 'ASC');
        return $purchaseBill;
    }

    public function headings(): array {
        return [
           "Purchase Order No.","Supplier Invoice No.","Supplier Invoice Date","E-way Bill No.","Quantity","SubTotal","GST","Freight","Total Amount"
        ];
    }

    public function title(): string
    {
        return 'Inward Supply';
    }

    public function map($purchaseBill): array
    {
        if(isset($purchaseBill->purchaseOrder->pono)){
            return[
                $purchaseBill->purchaseOrder->pono,
                $purchaseBill->supp_inv_no,
                $purchaseBill->supp_inv_date,
                $purchaseBill->ewaybill,
                $purchaseBill->quantity,
                $purchaseBill->subtotal,
                $purchaseBill->gst,
                $purchaseBill->freight,
                $purchaseBill->total
            ];
        }
    }
}
