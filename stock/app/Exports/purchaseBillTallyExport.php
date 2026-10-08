<?php

namespace App\Exports;

use App\purchaseBill;
use App\pbTable;
use App\supplier;
use App\purchaseOrder;
use App\poTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class purchaseBillTallyExport implements FromQuery, WithHeadings, WithTitle, WithMapping
{

	use Exportable;
    public function __construct($pb_ids)
    {
        $this->pb_ids = $pb_ids;
    }

    public function query()
    {
       // $pbTable = purchaseBill::whereIn('id', $pb_ids);
        /*$purchaseBill = purchaseBill::where('id',$pb_ids)->first();
        $purchaseOrderid = $purchaseBill->purchaseOrder_id;
        $purchaseOrder = purchaseOrder::where('id',$purchaseOrderid)->first();       
       
        $pbTable = pbTable::where('purchasebill_id',$pb_ids)->get();*/
        
		$pb_ids = $this->pb_ids;
        $pbTable = pbTable::whereIn('purchasebill_id', $pb_ids);
        
        
        return $pbTable;
    }

    public function headings(): array {
        return [
           "Supplier Invoice No.","Invoice Date","PO NO","PO DATE","SUPPLIER","Buyer reference","Item Name/ Part No./ Alias","Description","GODOWN","Qty","Rate","Assessable Value","GST Rate","CGST","SGST","IGST","Total Value","TDS 194Q","TDS 194Q Value","Address1","Address2","Address3","State","NARRATION"
        ];
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function map($pbTable): array
    {
		$purchaseBill = purchaseBill::where('id',$pbTable->purchasebill_id)->first();
        $purchaseBill->is_downloaded = 1;
        $purchaseBill->save();
        $purchaseOrder = purchaseOrder::where('id',$pbTable->purchaseOrder_id)->first();
        $supplier = supplier::find($purchaseOrder->supplier_id);
        $poTable = poTable::where('poid',$pbTable->purchaseOrder_id)->where('product_id',$pbTable->product_id)->first();
        if(isset($poTable->gstslab)){
            $gstamount = ($poTable->gstslab*$pbTable->amount)/100;
            if($supplier->state_code == "08"){
                $cgst = $gstamount/2;
                $sgst = $gstamount/2;
                $gstamount = 0;
            }else{
                $cgst = '';
                $sgst = '';
            }
            $gstslab = $poTable->gstslab;
        }else{
            $gstamount = 0;
            $cgst = '';
            $sgst = '';
            $gstslab = 18;
        }
        $total_value = $pbTable->amount + $poTable->gstamount;
        $tds194q = ($supplier->tds194q > 0)?"Yes":"No";
        $tds194q_value = ($total_value * $supplier->tds194q)/100;
        return [
            $purchaseBill->supp_inv_no,
            date('d-M-y',strtotime($purchaseBill->supp_inv_date)),
            $purchaseOrder->pono,
            date('d-M-y',strtotime($purchaseOrder->podate)),
            $supplier->c_name,
            $purchaseOrder->buyer_orderno,            
            $pbTable->product->code,
            $pbTable->product->name,
            'Main Location',
            $pbTable->orderqty,
            $pbTable->rate,
            '',
            $gstslab,
			$cgst,
			$sgst,
			$gstamount,
			$total_value,
			$tds194q,
			$tds194q_value,
            $supplier->address1,
			$supplier->address2,
            '',
            $supplier->state,
            'Being goods purchased through invoice no. ' . $purchaseBill->supp_inv_no . ' dated invoice date ' . date('d-M-y',strtotime($purchaseBill->supp_inv_date)) . ' PO no. ' . $purchaseOrder->pono . ' dated PO Date ' . date('d-M-y',strtotime($purchaseOrder->podate))
        ];
    }
}
