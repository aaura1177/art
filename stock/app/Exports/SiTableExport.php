<?php

namespace App\Exports;

use App\purchaseOrder;
use App\supplierInvoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class SiTableExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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
        $supplierInvoice = supplierInvoice::whereIn('purchase_order_id', $purchaseOrder)->select('purchase_order_id', 'supplier_invoice_number', 'internal_invoice_number', 'eway_bill_no', 'vehicle_no', 'tquantity', 'tgst', 'subTotal','tamount','user_id');
       // dd($supplierInvoice);
        
        return $supplierInvoice;
    }

    public function headings(): array {
        return [
           "PO","Supplier Invoice No","Supplier Name","Internal Invoice No.","Eway Bill No.","Vehicle No.","Total GST","Sub Total","Total Amount"
        ];
    }

    public function title(): string
    {
        return 'Supplier Invoice';
    }

     public function map($supplierInvoice): array
    {   
        $purchaseOrderTableData = purchaseOrder::find($supplierInvoice->purchase_order_id);
        //dd($purchaseOrderTableData);
        return [
            $purchaseOrderTableData->pono,
            $supplierInvoice->supplier_invoice_number,
            $supplierInvoice->internal_invoice_number,
            $supplierInvoice->eway_bill_no,
            $supplierInvoice->vehicle_no,
            $supplierInvoice->tquantity,
            $supplierInvoice->tgst,
            $supplierInvoice->subTotal,
            $supplierInvoice->tamount
        ];
    }
}
