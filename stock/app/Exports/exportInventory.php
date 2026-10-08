<?php

namespace App\Exports;

use App\product;
use App\stockLog;
use App\pricingTable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class exportInventory implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;

    public function __construct($from, $to)
    {
        $this->from = date('Y-m-d H:i:s',strtotime($from. " 00:00:00"));
        $this->to = date('Y-m-d H:i:s',strtotime($to. " 11:59:59"));
    }

    public function collection() {
        $logs	=	stockLog::whereBetween('created_at',[$this->from,$this->to])->where('type','<','3')->orderBy('created_at','DESC')->orderBy('id','desc')->get();
        return $logs;
    }

    public function headings(): array {
        return [
           "Product Code","Product Name","Quantity","Voucher No.","Reference","Type","Opening Balance","Closing Balance","Date","Batch No."
        ];
    }

    public function map($logs): array
    {
		if($logs->type == 1){
			$type 	=	"IN";
		}else{
			$type 	=	"OUT";
		}
		
		$date = date('d F Y',strtotime($logs->created_at));
		
        return [
            $logs->product->code ?? $logs->product_code ?? 'N/A',
            $logs->product->name ?? 'Unknown Product',
            $logs->quantity,
			$logs->voucher_no,
			$logs->ref_no,
			$type,
			$logs->opening_balance,
			$logs->remaining_stock,
			$date,
            $logs->batch_no
        ];
    }
}
