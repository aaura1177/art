<?php

namespace App\Exports;

use App\invoice;
use App\invoiceTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceTableExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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

        $invoice = invoice::whereBetween('date', [$newfsd, $newfed])->select('id')->orderby('id', 'ASC')->get();
        $invoiceTable = invoiceTable::whereIn('invoice_id', $invoice)->select('invoice_id', 'product_id', 'quantity', 'rate', 'amount', 'weight', 'subtotalnetwt', 'grosswt', 'subtotalgrosswt', 'gstslab', 'gstamount', 'box', 'endbox', 'subtotalbox', 'qtybox', 'descriptionBox')->orderby('invoice_id', 'ASC')->orderby('box', 'ASC');
        return $invoiceTable;
    }

    public function headings(): array {
        return [
           "Invoice No.","Product","EAN","HSN","Quantity","Rate","Amount","Net Weight","Total Net Weight","Gross Weigth","Total Gross Weight","GST Slab","GST Amount","Start Box","End Box","Total Box","Quantity per/box","Product Description"
        ];
    }

    public function title(): string
    {
        return 'Invoice Table Products';
    }

    public function map($invoiceTable): array
    {
        return[
            $invoiceTable->invoice->invoiceno,
            $invoiceTable->product->code." - ".$invoiceTable->product->name,
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
