<?php

namespace App\Exports;

use App\samplePurchaseBill;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class InwardSupplySampleProductsExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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

        $samplePurchaseBill = samplePurchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])->select('purchaseOrder_id',  'supp_inv_date', 'ewaybill', 'quantity', 'subtotal', 'gst', 'freight', 'total')->orderBy('supp_inv_date', 'ASC');
        return $samplePurchaseBill;
    }

    public function headings(): array {
        return [
           "Purchase Order No.","Supplier Invoice No.","Supplier Invoice Date","E-way Bill No.","Quantity","SubTotal","GST","Freight","Total Amount"
        ];
    }

    public function title(): string
    {
        return 'Inward Supply Samples';
    }

    public function map($samplePurchaseBill): array
    {
        return[
            $samplePurchaseBill->samplePurchaseOrder->pono,
            $samplePurchaseBill->supp_inv_no,
            $samplePurchaseBill->supp_inv_date,
            $samplePurchaseBill->ewaybill,
            $samplePurchaseBill->quantity,
            $samplePurchaseBill->subtotal,
            $samplePurchaseBill->gst,
            $samplePurchaseBill->freight,
            $samplePurchaseBill->total
        ];
    }
}
