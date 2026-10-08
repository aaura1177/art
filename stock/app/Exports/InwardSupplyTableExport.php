<?php

namespace App\Exports;

use App\purchaseBill;
use App\pbTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class InwardSupplyTableExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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

        $purchaseBill = purchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])->select('id')->orderBy('id', 'ASC')->get();
        $pbTable = pbTable::whereIn('purchasebill_id', $purchaseBill)->select('purchaseOrder_id', 'product_id', 'EAN', 'orderqty', 'receiveqty', 'remainingqty', 'rate', 'amount','purchasebill_id');
        //dd($pbTable);
        return $pbTable;
    }

    public function headings(): array {
        return [
           "Purchase Order No.","Product", "EAN", "Order Quantity","Receive Quantity","Rate","Amount"
        ];
    }

    public function title(): string
    {
        return 'Inward Supply Table Products';
    }

    public function map($pbTable): array
    {   
        //dd($pbTable->purchaseBill->id);
       // $pb = purchaseBill::where('id',$pbTable->purchasebill_id)->get();
        // dd($pb);
       //if($pb->is_checked == '1'){
        if(isset($pbTable->purchaseOrder->pono)){
            return[
                $pbTable->purchaseOrder->pono,
                $pbTable->product->code. "-" . $pbTable->product->name,
                $pbTable->product->EAN,
                $pbTable->orderqty,
                $pbTable->receiveqty,
                $pbTable->rate,
                $pbTable->amount
            ];
        }
        //}
    }
}
