<?php

namespace App\Exports;

use App\invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceProductsExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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

        $invoice = invoice::whereBetween('date', [$newfsd, $newfed])->select('invoiceno', 'date', 'consignee_id', 'buyer_id', 'buyerorderno', 'containerno', 'vehicleno', 'ewaybillno', 'pkgs', 'currency', 'conrate', 'declaration', 'fob', 'payterms', 'shipmentby', 'desgoods', 'carriage', 'receipt', 'shipment', 'postloading', 'discharge', 'destination', 'shipping_charges', 'packing_charges', 'discount', 'totalamount', 'totalgst', 'rateamount', 'totalquantity', 'totalwt', 'totalgrosswt', 'totalbox')->orderby('invoiceno', 'ASC');
        return $invoice;
    }

    public function headings(): array {
        return [
           "Invoice No.","Invoice Date","Consignee","Buyer","Buyer Order No.","Container No.","Vehicle No.","E-way Bill No.","PacKages","Currency","Conversion Rate","Declaration","FOB","Payterms","Shipmentby","Description goods","Carriage","Receipt","Shipment","Postloading","Discharge","Destination","Shipping Charges","Packing Charges","Discount","Total Amount","Total GST","Rate Amount","Total Quantity","Total Weight","Total GrossWeight","Total Box"
        ];
    }

    public function title(): string
    {
        return 'Invoice';
    }

    public function map($invoice): array
    {
        return[
            $invoice->invoiceno,
            $invoice->date,
            $invoice->consignee->c_name,
            $invoice->buyer->c_name,
            $invoice->buyerorderno,
            $invoice->containerno,
            $invoice->vehicleno,
            $invoice->ewaybillno,
            $invoice->pkgs,
            $invoice->currency,
            $invoice->conrate,
            $invoice->declaration,
            $invoice->fob,
            $invoice->payterms,
            $invoice->shipmentby,
            $invoice->desgoods,
            $invoice->carriage,
            $invoice->receipt,
            $invoice->shipment,
            $invoice->postloading,
            $invoice->discharge,
            $invoice->destination,
            $invoice->shipping_charges,
            $invoice->packing_charges,
            $invoice->discount,
            $invoice->totalamount,
            $invoice->totalgst,
            $invoice->rateamount,
            $invoice->totalquantity,
            $invoice->totalwt,
            $invoice->totalgrosswt,
            $invoice->totalbox
        ];
    }
}
