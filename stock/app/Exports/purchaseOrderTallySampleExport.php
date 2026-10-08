<?php

namespace App\Exports;

use App\purchaseOrder;
use App\samplePurchaseOrder;
use App\poTable;
use App\posTable;
use App\supplier;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class purchaseOrderTallySampleExport implements FromQuery, WithHeadings, WithTitle, WithMapping
{

	use Exportable;
    public function __construct($po_ids)
    {
        $this->po_ids = $po_ids;
    }

    public function query()
    {
    	$po_ids = explode(',',$this->po_ids);
        $poTable = posTable::whereIn('poid', $po_ids);
		
        return $poTable;
    }

    public function headings(): array {
        return [
           "PO NO","PO DATE","SUPPLIER","Place of Supply","Term of payment","Delivery Date","Buyer reference ","GSTIN","Item Name/ Part No./ Alias","Description","GODOWN","Qty","Unit","Rate","Assessable Value","GST Rate","CGST","SGST","IGST","Total value","Address1","Address2","Address3","State","NARRATION"
        ];
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function map($poTable): array
    {
		$supplier = supplier::find($poTable->samplePurchaseOrderTable->supplier_id);
        return [
            $poTable->samplePurchaseOrderTable->pono,
			date('d-M-y',strtotime($poTable->samplePurchaseOrderTable->podate)),
            $supplier->c_name,
            $supplier->state,
            $poTable->samplePurchaseOrderTable->payterms,
            date('d-M-y',strtotime($poTable->samplePurchaseOrderTable->del_date)),
            $poTable->samplePurchaseOrderTable->buyer_orderno,
            $supplier->gstin,
            $poTable->sample->code,
            $poTable->sample->name,
			'Main Location',
            $poTable->quantity,
            $poTable->unit,
            $poTable->rate,
            '',
			$poTable->gstslab,
			$poTable->gstamount/2,
			$poTable->gstamount/2,
			$poTable->gstamount,
			$poTable->amount + $poTable->gstamount,
			$supplier->address1,
			$supplier->address2,
			'',
			$supplier->state,
			''
        ];
    }
}
