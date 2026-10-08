<?php

namespace App\Exports;

use App\samplePurchaseBill;
use App\spbTable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class InwardSupplySampleTableExport implements FromQuery, WithHeadings, WithTitle, WithMapping
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

        $samplePurchaseBill = samplePurchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])->select('id')->orderBy('id', 'ASC')->get();
        $spbTable = spbTable::whereIn('sample_purchasebill_id', $samplePurchaseBill)->select('purchaseOrder_id',  'sample_id','EAN', 'orderqty', 'receiveqty', 'remainingqty', 'rate', 'amount','sample_purchasebill_id');
        //dd($pbTable);
        return $spbTable;
    }

    public function headings(): array {
        return [
           "Purchase Order No.","Product", "EAN", "Order Quantity","Receive Quantity","Rate","Amount"
        ];
    }

    public function title(): string
    {
        return 'Inward Supply Table Samples';
    }

    public function map($spbTable): array
    {   
        //dd($pbTable->purchaseBill->id);
       // $pb = purchaseBill::where('id',$pbTable->purchasebill_id)->get();
        // dd($pb);
       //if($pb->is_checked == '1'){
        return[
            $spbTable->samplePurchaseOrder->pono,
            $spbTable->sample->code. "-" . $spbTable->sample->name,
            $spbTable->sample->EAN,
            $spbTable->orderqty,
            $spbTable->receiveqty,
            $spbTable->rate,
            $spbTable->amount
        ];
        //}
    }
}
