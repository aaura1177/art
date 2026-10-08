<?php

namespace App\Exports;

use App\invoiceTable;
use App\invoice;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EachInvoiceExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct($id)
    {
    	$this->id = $id;
    }

    public function query()
    {
    	$inv_id = $this->id;
       	$invoiceTable = invoiceTable::where('invoice_id', $inv_id)->select('invoice_id', 'product_id', 'quantity', 'rate', 'amount', 'weight', 'subtotalnetwt', 'grosswt', 'subtotalgrosswt', 'gstslab', 'gstamount', 'box', 'endbox', 'subtotalbox', 'qtybox', 'descriptionBox')->orderBy('box', 'ASC');
        return $invoiceTable;
    }

    public function headings(): array {
        return [
           "Invoice No.","SKU","Product", "EAN", "HSN", "Quantity","Rate","Amount","Net Weight","Total Net Weight","Gross Weigth","Total Gross Weight","GST Slab","GST Amount","Start Box","End Box","Total Box","Quantity per/box","Remarks"
        ];
    }

    public function map($invoiceTable): array
    {
        return[
            $invoiceTable->invoice->invoiceno,
            $invoiceTable->product->code,
            $invoiceTable->product->code . " - " . $invoiceTable->product->name,
            $invoiceTable->product->EAN,
            $invoiceTable->product->HSN,
            $invoiceTable->quantity,
            $invoiceTable->rate,
            $invoiceTable->amount,
            $invoiceTable->weight,
            $invoiceTable->subtotalnetwt,
            $invoiceTable->grosswt,
            $invoiceTable->subtotalgrosswt,
            $invoiceTable->gstslab,
            $invoiceTable->gstamount,
            $invoiceTable->box,
            $invoiceTable->endbox,
            $invoiceTable->subtotalbox,
            $invoiceTable->qtybox,
            $invoiceTable->descriptionBox
        ];    
    }
}
