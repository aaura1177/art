<?php

namespace App\Exports;

use App\purchaseBillConsumable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PoConsumableBillsDueTrackingExport implements FromQuery, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithMapping
{
    use Exportable;

    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    public function styles(Worksheet $sheet)
    {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000 '],
                ],
            ],
            'font' => [
                'name' => 'Calibri',
                'size' => 10,
                'bold' => false,
            ],
        ];

        $styleArray1 = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000 '],
                ],
            ],
            'font' => [
                'name' => 'Calibri',
                'size' => 10,
                'bold' => true,
            ],
        ];

        $sheet->getStyle('A1:M1000')->applyFromArray($styleArray);
        $sheet->getStyle('A1:M1')->applyFromArray($styleArray1);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('02FFFF');

        $sheet->getStyle('A1:M1000')
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function query()
    {
        $newfsd = date('Y-m-d H:i:s', strtotime($this->fsd));
        $newfed = date('Y-m-d H:i:s', strtotime($this->fed));

        return purchaseBillConsumable::whereBetween('supp_inv_date', [$newfsd, $newfed])
            ->with('purchaseOrder.supplier')
            ->orderBy('id', 'ASC');
    }

    public function headings(): array
    {
        \Request::session()->forget('inv_no');

        return [
            ['INVOICE DATE', 'PARTY', 'INVOICE NO.', 'INVOICE AMOUNT', 'SHIPPED + PAID', 'SHIPPED + UNPAID', 'UNSHIPPED +PAID', 'UNSHIPPED + UNPAID', 'PAYMENT WHEN DUE', 'VERIFICATION DATE', 'DUE DATE', 'STATUTORY DATE', 'OVERDUE'],
        ];
    }

    public function title(): string
    {
        return 'Bills Due Tracking';
    }

    public function map($row): array
    {
        $date1 = date_create(date('d-M-Y', strtotime($row->created_at)));
        $date2 = date_create(date('Y-m-d'));
        $interval = $date1->diff($date2);

        $party = optional(optional($row->purchaseOrder)->supplier)->c_name ?? '';

        return [
            date('d-M-Y', strtotime($row->supp_inv_date)),
            $party,
            $row->supp_inv_no,
            $row->total,
            ($row->payment_status == 5) ? 'YES' : 'X',
            ($row->payment_status == 4) ? 'YES' : 'X',
            ($row->payment_status == 3) ? 'YES' : 'X',
            ($row->payment_status == 1) ? 'YES' : 'X',
            ($row->payment_status == 2) ? 'YES' : 'X',
            date('d-M-Y', strtotime($row->created_at)),
            date('d-M-Y', strtotime($row->created_at . '+30 days')),
            date('d-M-Y', strtotime($row->created_at . '+45 days')),
            $interval->days,
        ];
    }
}
