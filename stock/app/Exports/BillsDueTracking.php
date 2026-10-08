<?php

namespace App\Exports;

use App\purchaseBill;
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

class BillsDueTracking implements FromQuery, WithHeadings, WithTitle,ShouldAutoSize,WithStyles,WithMapping
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
        
        $styleArray1 = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000 '],
                ],
            ],
            'font' => array(
                'name'      =>  'Calibri',
                'size'      =>  10,
                'bold'      =>  true
            )
        ];
        $sheet->getStyle('A1:M1000')->applyFromArray($styleArray);
        $sheet->getStyle('A1:M1')->applyFromArray($styleArray1);
    //     $sheet->mergeCells('A1:S1');
    //     $sheet->mergeCells('A2:S2');
        
        $sheet->getStyle('A1:M1')->getFill()
              ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('02FFFF');
    //     $sheet->getStyle('A2:S2')->getFill()
    //     ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    //     ->getStartColor()->setARGB('CCFFFF');
    //     $sheet->getStyle('A3:O3')->getFill()
    //     ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    //     ->getStartColor()->setARGB('FFFF99');
    //     $sheet->getStyle('P3:S3')->getFill()
    //     ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    //     ->getStartColor()->setARGB('538DD5');
    
        $sheet->getStyle('A1:M1000')
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    //     $sheet->getStyle('A2:S2')
    // ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    //     $sheet->getStyle('A3:S100')->applyFromArray($styleArray)->getAlignment()->setWrapText(true);
        

    }
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

        $purchaseBill = purchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])->orderby('id', 'ASC');
        
        return $purchaseBill;
    }

    public function headings(): array {
        \Request::session()->forget('inv_no');
        return [
           ["INVOICE DATE","PARTY","INVOICE NO.","INVOICE AMOUNT","SHIPPED + PAID","SHIPPED + UNPAID","UNSHIPPED +PAID","UNSHIPPED + UNPAID","PAYMENT WHEN DUE","VERIFICATION DATE","DUE DATE","STATUTORY DATE","OVERDUE"]
        ];
    }

    public function title(): string
    {
        return 'Bills Due Tracking';
    }

    public function map($purchaseBill): array
    {   

        $date1 = date_create(date('d-M-Y', strtotime($purchaseBill->created_at)));
        $date2 = date_create(date('Y-m-d'));
        $interval = $date1->diff($date2);
        
        $main_arr[] = [
                        date('d-M-Y', strtotime($purchaseBill->supp_inv_date)),
                        $purchaseBill->purchaseOrder->supplier->c_name,
                        $purchaseBill->supp_inv_no,
                        $purchaseBill->total,
                        ($purchaseBill->payment_status == 5)?'YES':'X',
                        ($purchaseBill->payment_status == 4)?'YES':'X',
                        ($purchaseBill->payment_status == 3)?'YES':'X',
                        ($purchaseBill->payment_status == 1)?'YES':'X',
                        ($purchaseBill->payment_status == 2)?'YES':'X',
                        date('d-M-Y', strtotime($purchaseBill->created_at)),
                        date('d-M-Y', strtotime($purchaseBill->created_at . "+30 days")),
                        date('d-M-Y', strtotime($purchaseBill->created_at . "+45 days")),
                        $interval->days
                    ];
        return $main_arr;
    }

    
}
