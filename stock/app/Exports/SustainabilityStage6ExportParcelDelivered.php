<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\SustainabilityStage6;
use App\SustainabilityVariable;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;
class SustainabilityStage6ExportParcelDelivered implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $month_year;
    protected $location;

    protected $records;
    public function __construct($month_year = '', $location = '')
    {
        $this->month_year = $month_year;
        $this->location = $location;

        $startOfMonth = Carbon::parse($month_year . '-01')->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($month_year . '-01')->endOfMonth()->toDateString();

        $url = env(strtoupper($location) . '_URL') .
            "/wp-json/erp-route/all-orders-quantity?from={$startOfMonth}&to={$endOfMonth}&post_status=completed";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // Consider making this true for production
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = [];

        if (curl_errno($ch)) {
            \Log::error("cURL Error: " . curl_error($ch));
        } elseif ($httpCode !== 200) {
            \Log::error("HTTP Error: $httpCode");
            \Log::error("Response: $response");
        } else {
            $records = json_decode($response, true);

            if (!empty($records)) {
                foreach ($records as $record) {
                    $products_sold = [];
                    foreach ($record['product_name'] as $i => $product) {
                        $products_sold[] = $product . " - " . $record['qty'][$i];
                    }

                    $data[] = [
                        'orderId' => $record['orderId'],
                        'order_date' => $record['order_date'],
                        'product_name' => implode("\n", $products_sold),
                        'total_qty' => $record['total_qty'],
                    ];
                }
            }
        }

        $totalParcels = count($data);

        $data[] = [
            'orderId'      => '',
            'order_date'   => '',
            'product_name' => '',
            'total_qty'    => $totalParcels,
        ];

        $this->records = collect($data);
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        $headers[] = 'Order Id';
        $headers[] = 'Order Date';
        $headers[] = 'Products / Qty';
        $headers[] = 'Total Qty';

        return $headers;
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            2 => [ // Header row
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9D9D9'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Merge title
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', 'Parcel Delivered Record For '.$this->location.' in '. date('M Y', strtotime($this->month_year)));
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Title styling
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Auto-size columns
                $columnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                for ($i = 1; $i <= $columnCount; $i++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }

                // Highlight the total row
                $totalRowIndex = $highestRow;

                $sheet->mergeCells("A{$totalRowIndex}:C{$totalRowIndex}");
                $sheet->setCellValue("A{$totalRowIndex}", 'Total parcels (orders)');

                $event->sheet->getStyle("A{$totalRowIndex}:D{$totalRowIndex}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);

                
                $sheet->getStyle("A{$totalRowIndex}:{$highestColumn}{$totalRowIndex}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFE599'], // Light yellow
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
                
            },
        ];
    }

    public function map($row): array
    {
        return $row;
    }
}
