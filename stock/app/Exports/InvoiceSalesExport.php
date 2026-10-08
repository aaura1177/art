<?php

namespace App\Exports;

use App\invoice;
use App\invexport;
use App\invoiceTable;
use App\buyer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InvoiceSalesExport implements FromQuery, WithHeadings, WithTitle,ShouldAutoSize,WithStyles,WithMapping
{

 
    public function styles(Worksheet $sheet)
    {   
            $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000 '],
                ],
            ],
            'font' => array(
                'name'      =>  'Calibri',
                'size'      =>  10,
                'bold'      =>  false
            )
        ];
        
        $sheet->getStyle('A1:S100')->applyFromArray($styleArray);
        $sheet->mergeCells('A1:S1');
        $sheet->mergeCells('A2:S2');
        
        $sheet->getStyle('A1:S1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('CCFFFF');
        $sheet->getStyle('A2:S2')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('CCFFFF');
        $sheet->getStyle('A3:O3')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFFF99');
        $sheet->getStyle('P3:S3')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('538DD5');
    
       $sheet->getStyle('A1:S100')
    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A2:S2')
    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A3:S100')->applyFromArray($styleArray)->getAlignment()->setWrapText(true);
        

    }
    public function __construct($fesd, $feed)
    {
        $this->fesd = $fesd;
        $this->feed = $feed;
    }

    public function query()
    {
        \Request::session()->pull('invCurrentCount');
        \Request::session()->pull('invoiceCount');
        \Request::session()->pull('total_pound');
        \Request::session()->pull('total_dollar');
        \Request::session()->pull('total_euro');
        \Request::session()->pull('realised_pound');
        \Request::session()->pull('realised_dollar');
        \Request::session()->pull('realised_euro');
        \Request::session()->pull('realised_rs_pound');
        \Request::session()->pull('realised_rs_dollar');
        \Request::session()->pull('realised_rs_euro');
        \Request::session()->pull('inv_no');
    	$fsd1 = strtotime($this->fesd);
    	$newfsd = date('Y-m-d H:i:s',$fsd1);        

    	$fed1 = strtotime($this->feed);
    	$newfed = date('Y-m-d H:i:s',$fed1);

        $invoice = invoice::whereBetween('date', [$newfsd, $newfed])->where('invoicetype',0)->select('id','totalamount','currency')->orderby('id', 'ASC')->get();
        $total_pound = 0;
        $total_dollar = 0;
        $total_euro = 0;
        $realised_pound = 0;
        $realised_dollar = 0;
        $realised_euro = 0;
        $realised_rs_pound = 0;
        $realised_rs_dollar = 0;
        $realised_rs_euro = 0;
        foreach($invoice as $inv){
            if($inv->invexport->count() !== 0) {
                $invoice_ids[] = $inv->id;
                if($inv->currency == '£'){
                    $total_pound = $total_pound + $inv->totalamount;
                    foreach($inv->invexport as $invexport){
                        $realised_pound = $realised_pound + $invexport->realisation_fc;
                        $realised_rs_pound = $realised_rs_pound + ($invexport->realisation_fc * $invexport->rate);
                    }
                }
                if($inv->currency == '$'){
                    $total_dollar = $total_dollar + $inv->totalamount;
                    foreach($inv->invexport as $invexport){
                        $realised_dollar = $realised_dollar + $invexport->realisation_fc;
                        $realised_rs_dollar = $realised_rs_dollar + ($invexport->realisation_fc * $invexport->rate);
                    }
                }
                if($inv->currency == '€'){
                    $total_euro = $total_euro + $inv->totalamount;
                    foreach($inv->invexport as $invexport){
                        $realised_euro = $realised_euro + $invexport->realisation_fc;
                        $realised_rs_euro = $realised_rs_euro + ($invexport->realisation_fc * $invexport->rate);
                    }
                }
            }
        }
        \Request::session()->put('total_pound', '£' . $total_pound);
        \Request::session()->put('total_dollar', '$' . $total_dollar);
        \Request::session()->put('total_euro', '€' . $total_euro);
        \Request::session()->put('realised_pound', '£' . $realised_pound);
        \Request::session()->put('realised_dollar', '$' . $realised_dollar);
        \Request::session()->put('realised_euro', '€' . $realised_euro);
        \Request::session()->put('realised_rs_pound', $realised_rs_pound);
        \Request::session()->put('realised_rs_dollar', $realised_rs_dollar);
        \Request::session()->put('realised_rs_euro', $realised_rs_euro);
        $invoiceTable = invexport::whereIn('invoice_id', $invoice_ids)->select('invoice_id', 'realisation_date', 'realisation_fc', 'rate', 'bank_reference')->orderby('invoice_id', 'ASC');
        $invoiceCount = $invoiceTable->count();    
        \Request::session()->put('invoiceCount', $invoiceCount);
        
        return $invoiceTable;
    }

    public function headings(): array {
        \Request::session()->forget('inv_no');

        $iy = date('Y',strtotime($this->fesd));
        $ty = $iy+1;
        $fy = $iy .'-'. $ty;
        return [
             ['Global Vision Direct (P) Ltd'],
             ['EXPORT REALISATION DETAIL FOR THE YEAR '.$fy],
           ["INVOICE NO.","INVOICE DATE","BUYER NAME","BUYER CODE /SHIPMENT #","REALISATION DATE"," BANK Reference #","INVOICE VALUE","BOOKING VALUE","INR AT BOOKING RATE","IGST/LUT","IGST AMOUNT","REALISED F.C.","RATE","FBC"," REALISED RS.","SHIPPED ON","SHIPPING BILL #","S.B. DATE","FC FREIGHT"]
        ];
    }

    public function title(): string
    {
        return 'Sales';
    }

    public function map($invoiceTable): array
    {   

        $buyerData = buyer::where('id', $invoiceTable->invoice->buyer_id)->first();
        //dd($buyerData);
        $last_row = 0;
        if(\Request::session()->get('invCurrentCount') == null){
            \Request::session()->put('invCurrentCount',2);
        }else{
            $c = \Request::session()->get('invCurrentCount');
            $invoiceCount = \Request::session()->get('invoiceCount');
            if($invoiceCount == $c){
                $last_row = 1;
            }
            $c++;
            \Request::session()->put('invCurrentCount',$c);
        }
        $realised_rs = $invoiceTable->realisation_fc * $invoiceTable->rate;
        $inrat_booking_value = $invoiceTable->invoice->booking_value * $invoiceTable->invoice->totalamount;
        $main_arr[] = [
                        $invoiceTable->invoice->invoiceno,
                        date('d-M-Y', strtotime($invoiceTable->invoice->date)),
                        $buyerData->c_name,
                        $invoiceTable->invoice->buyerorderno,
                        ($invoiceTable->realisation_date != NULL)?date('d-M-Y', strtotime($invoiceTable->realisation_date)):'',
                        $invoiceTable->bank_reference,
                        $invoiceTable->invoice->currency . $invoiceTable->invoice->totalamount,
                        $invoiceTable->invoice->booking_value,
                        ' '.$inrat_booking_value,
                        $invoiceTable->invoice->tax_type,
                        ($invoiceTable->invoice->totalgst != '0.00')?$invoiceTable->invoice->totalgst:'NA',
                        $invoiceTable->invoice->currency . $invoiceTable->realisation_fc,
                        $invoiceTable->rate,
                        $invoiceTable->invoice->fbc,
                        $realised_rs,
                        ($invoiceTable->invoice->bl_date != NULL)?date('d-M-Y', strtotime($invoiceTable->invoice->bl_date)):'',
                        $invoiceTable->invoice->shipping_bill_no,
                        $invoiceTable->invoice->shipping_bill_date,
                        "NIL"
                    ];
        
        if(\Request::session()->get('inv_no') == null) {
            \Request::session()->put('inv_no', $invoiceTable->invoice->invoiceno);
            if($last_row){
                $main_arr[] = [];
                $main_arr[] = [];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Dollars'
                ];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Invoice Value Total',
                    '',
                    '',
                    '',
                    '',
                    'Realized F.C.',
                    '',
                    '',
                    'Realized INR'
                ];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    \Request::session()->get('total_dollar'),
                    '',
                    '',
                    '',
                    '',
                    \Request::session()->get('realised_dollar'),
                    '',
                    '',
                    \Request::session()->get('realised_rs_dollar'),
                ];
                $main_arr[] = [];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Euro'
                ];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Invoice Value Total',
                    '',
                    '',
                    '',
                    '',
                    'Realized F.C.',
                    '',
                    '',
                    'Realized INR'
                ];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    \Request::session()->get('total_euro'),
                    '',
                    '',
                    '',
                    '',
                    \Request::session()->get('realised_euro'),
                    '',
                    '',
                    \Request::session()->get('realised_rs_euro'),
                ];
                $main_arr[] = [];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Pounds'
                ];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Invoice Value Total',
                    '',
                    '',
                    '',
                    '',
                    'Realized F.C.',
                    '',
                    '',
                    'Realized INR'
                ];
                $main_arr[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    \Request::session()->get('total_pound'),
                    '',
                    '',
                    '',
                    '',
                    \Request::session()->get('realised_pound'),
                    '',
                    '',
                    \Request::session()->get('realised_rs_pound'),
                ];
            }
            return $main_arr;
        }else{
            $inv_no = \Request::session()->get('inv_no');
            \Request::session()->put('inv_no', $invoiceTable->invoice->invoiceno);
            if($inv_no == $invoiceTable->invoice->invoiceno){
                $main_arr = [];
                $main_arr[] = [
                   '',
                    '',
                    "",
                    date('d-M-Y', strtotime($invoiceTable->realisation_date)),
                    $invoiceTable->bank_reference,
                    "",
                    "",
                    '',
                    "",
                    "",
                    $invoiceTable->invoice->currency . $invoiceTable->realisation_fc,
                    $invoiceTable->rate,
                    '',
                    $realised_rs,
                    '',
                    "",
                    "",
                    "",
                    ""
                ];
                if($last_row){
                    $main_arr[] = [];
                    $main_arr[] = [];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Dollars'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Invoice Value Total',
                        '',
                        '',
                        '',
                        '',
                        'Realized F.C.',
                        '',
                        '',
                        'Realized INR'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('total_dollar'),
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('realised_dollar'),
                        '',
                        '',
                        \Request::session()->get('realised_rs_dollar'),
                    ];
                    $main_arr[] = [];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Euro'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Invoice Value Total',
                        '',
                        '',
                        '',
                        '',
                        'Realized F.C.',
                        '',
                        '',
                        'Realized INR'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('total_euro'),
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('realised_euro'),
                        '',
                        '',
                        \Request::session()->get('realised_rs_euro'),
                    ];
                    $main_arr[] = [];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Pounds'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Invoice Value Total',
                        '',
                        '',
                        '',
                        '',
                        'Realized F.C.',
                        '',
                        '',
                        'Realized INR'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('total_pound'),
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('realised_pound'),
                        '',
                        '',
                        \Request::session()->get('realised_rs_pound'),
                    ];
                }
                $main_arr[] = [];
                return $main_arr;
            }else{
                if($last_row){
                    $main_arr[] = [];
                    $main_arr[] = [];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Dollars'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Invoice Value Total',
                        '',
                        '',
                        '',
                        '',
                        'Realized F.C.',
                        '',
                        '',
                        'Realized INR'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('total_dollar'),
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('realised_dollar'),
                        '',
                        '',
                        \Request::session()->get('realised_rs_dollar'),
                    ];
                    $main_arr[] = [];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Euro'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Invoice Value Total',
                        '',
                        '',
                        '',
                        '',
                        'Realized F.C.',
                        '',
                        '',
                        'Realized INR'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('total_euro'),
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('realised_euro'),
                        '',
                        '',
                        \Request::session()->get('realised_rs_euro'),
                    ];
                    $main_arr[] = [];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Pounds'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Invoice Value Total',
                        '',
                        '',
                        '',
                        '',
                        'Realized F.C.',
                        '',
                        '',
                        'Realized INR'
                    ];
                    $main_arr[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('total_pound'),
                        '',
                        '',
                        '',
                        '',
                        \Request::session()->get('realised_pound'),
                        '',
                        '',
                        \Request::session()->get('realised_rs_pound'),
                    ];
                }
                $main_arr[] = [];
                return $main_arr;
            }
        }
         
    }

    
}
