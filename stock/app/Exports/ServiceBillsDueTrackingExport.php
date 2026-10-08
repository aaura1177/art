<?php
namespace App\Exports;

use App\servicePurchaseBill;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\Exportable; // Add this line
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ServiceBillsDueTrackingExport implements FromQuery, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithMapping
{
    use Exportable; // Add this line

    public function styles(Worksheet $sheet)
    {   
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
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
                    'borderStyle' => Border::BORDER_THIN,
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
        $sheet->getStyle('A1:M1')->getFill()
              ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('02FFFF');
        
        $sheet->getStyle('A1:M1000')
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    public function query()
    {
        $fsd1 = strtotime($this->fsd);
        $newfsd = date('Y-m-d H:i:s', $fsd1);        

        $fed1 = strtotime($this->fed);
        $newfed = date('Y-m-d H:i:s', $fed1);

        return servicePurchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])
                                  ->with('purchaseOrder')
                                  ->orderBy('id', 'ASC');
    }

    public function headings(): array
    {
        \Request::session()->forget('inv_no');
        return [
            ["INVOICE DATE", "PARTY", "INVOICE NO.", "INVOICE AMOUNT", "SHIPPED + PAID", "SHIPPED + UNPAID", "UNSHIPPED +PAID", "UNSHIPPED + UNPAID", "PAYMENT WHEN DUE", "VERIFICATION DATE", "DUE DATE", "STATUTORY DATE", "OVERDUE"]
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

        return [
            date('d-M-Y', strtotime($purchaseBill->supp_inv_date)),
            $purchaseBill->purchaseOrder->supplier->c_name,
            $purchaseBill->supp_inv_no,
            $purchaseBill->total,
            ($purchaseBill->payment_status == 5) ? 'YES' : 'X',
            ($purchaseBill->payment_status == 4) ? 'YES' : 'X',
            ($purchaseBill->payment_status == 3) ? 'YES' : 'X',
            ($purchaseBill->payment_status == 1) ? 'YES' : 'X',
            ($purchaseBill->payment_status == 2) ? 'YES' : 'X',
            date('d-M-Y', strtotime($purchaseBill->created_at)),
            date('d-M-Y', strtotime($purchaseBill->created_at . "+30 days")),
            date('d-M-Y', strtotime($purchaseBill->created_at . "+45 days")),
            $interval->days
        ];
    }
}
