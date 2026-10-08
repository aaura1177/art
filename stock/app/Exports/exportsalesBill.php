<?php

namespace App\Exports;

use App\invoiceTable;
use App\invoice;
use App\buyer;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class exportsalesBill implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct($invoice_ids)
    {
    	$this->invoice_ids = $invoice_ids;
    }

    public function query()
    {
    	$invoice_ids = explode(',',$this->invoice_ids);
       	$invoiceTable = invoiceTable::whereIn('invoice_id', $invoice_ids)->select('invoice_id', 'product_id', 'quantity', 'rate', 'amount', 'weight', 'subtotalnetwt', 'grosswt', 'subtotalgrosswt', 'gstslab', 'gstamount', 'box', 'endbox', 'subtotalbox', 'qtybox', 'descriptionBox')->orderBy('invoice_id', 'ASC');
		//echo '<pre>';print_r($invoiceTable);die;
        return $invoiceTable;
    }

    public function headings(): array {
        return [
           "INV NO","INV DATE","PARTY NAME","Place of Supply","GSTIN","Item Name/ Part No./ Alias", "Description", "GODOWN", "Qty","Unit","Rate","Assessable Value","GST Rate","Exchange Rate","Buyer Name","Buyer_GSTIN","Address1","Address2","Address3","State","SHIPPING BILL NO.","SB DATE","PORT CODE","E WAY BILL","E WAY BILL DATE","SUBTYPE","DOCUMENT TYPE","Vehicle No","Billty No","Billty  Date","Agent","Port of Loading","Port of Discharge","Bill To","Ship To","NARRATION"
        ];
    }

    public function map($invoiceTable): array
    {
		$consignee = buyer::where('id',$invoiceTable->invoice->consignee_id)->first();
		$party_name = $consignee->c_name;
		$buyer = buyer::where('id',$invoiceTable->invoice->buyer_id)->first();
		$buyer_name = $buyer->c_name;
		$valIncGst = ($invoiceTable->invoice->conrate * $invoiceTable->amount) + $invoiceTable->gstamount;
        return[
            $invoiceTable->invoice->invoiceno,
            date('d-M-y',strtotime($invoiceTable->invoice->date)),
			$party_name,
			"Outside India",
			"URP",
            $invoiceTable->product->code,
			$invoiceTable->product->name,
			'Main Location',
            $invoiceTable->quantity,
			'Nos.',
            $invoiceTable->rate,
            $invoiceTable->amount,
			//$valIncGst,
			$invoiceTable->gstslab,
			$invoiceTable->invoice->conrate,
			$buyer_name,
			"URP",
			$buyer->address1,
			$buyer->address2,
			"",
			$buyer->state,
			$invoiceTable->invoice->shipping_bill_no,
			$invoiceTable->invoice->shipping_bill_date,
			$invoiceTable->invoice->port_code,
			$invoiceTable->invoice->ewaybillno,
			$invoiceTable->invoice->ewaybilldate,
			"Export",
			"Tax Invoice",
			$invoiceTable->invoice->vehicleno,
			$invoiceTable->invoice->billty_no,
			$invoiceTable->invoice->billty_date,
			$invoiceTable->invoice->agent,
			$invoiceTable->invoice->port_of_loading,
			$invoiceTable->invoice->port_of_discharge,
			$invoiceTable->invoice->bill_to,
			$invoiceTable->invoice->ship_to,
			"BEING EXPORT SALES BOOKED AGAINST INVOICE. NO " . $invoiceTable->invoice->invoiceno . " DT. " . $invoiceTable->invoice->date . " CONTAINER NO. " . $invoiceTable->invoice->buyerorderno
        ];    
    }
}
