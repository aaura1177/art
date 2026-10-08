<?php

namespace App\Exports;
use Illuminate\Http\Request;
use App\SustainabilityStage0;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings,WithMapping, WithStyles, WithEvents, WithCustomStartCell};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class SustainabilityStage0Export implements  FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $date_from;
    protected $date_to;
    protected float $total_qty = 0;
    protected float $total_weight = 0;
    protected float $total_carbon = 0;
    protected $recordsWithTotal;

    public function __construct($search,$date_from,$date_to)
    {
        $this->search = $search ?? '';
        $this->date_from = $date_from ?? '';
        $this->date_to = $date_to ?? '';
    }

    public function collection()
    {
       
        $records = SustainabilityStage0::when($this->date_from && $this->date_to, function ($query) {
                $query->whereBetween('month_year', [$this->date_from, $this->date_to]);
            })
            ->orderByDesc('created_at')
            ->get();

        // Compute totals
        $this->total_qty = $records->sum('total_qty_delivered_by_containers');
        $this->total_weight = $records->sum('total_weight_delivered_in_containers');
        $this->total_carbon = $records->sum('carbon_sequestration');

        // Add a fake "totals" row (using stdClass to stay flexible)
        $totalsRow = new \stdClass();
        $totalsRow->month_year = 'Total';
        $totalsRow->total_qty_delivered_by_containers = $this->total_qty;
        $totalsRow->total_weight_delivered_in_containers = $this->total_weight;
        $totalsRow->carbon_sequestration = $this->total_carbon;

        $this->recordsWithTotal = $records->push($totalsRow);

        return $this->recordsWithTotal;
        
    }

    public function headings(): array
    {
        return [
            'Month-Year',
            'Total Qty. Delivered By Containers',
            'Total Weight Delivered In Containers',
            'Carbon Sequestration'
        ];
    }

    public function map($record): array
    {
        $isTotalRow = $record->month_year === 'Total';

        return [
            $isTotalRow ? 'Total' : date('M - Y', strtotime($record->month_year)),
            $record->total_qty_delivered_by_containers,
            $record->total_weight_delivered_in_containers,
            $record->carbon_sequestration,
        ];
    }

    public function startCell(): string
    {
        return 'A2'; // Data starts on row 2 (row 1 is title)
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->recordsWithTotal) + 2; // since header starts at row 2

        return [
            2 => [ // Header row
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ]
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9D9D9'], // Light gray
                ],
            ],
            $lastRow => [ // Totals row
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFF2CC'], // Light yellow
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge cells for the title
                $highestColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', 'Sustainability Stage 0 Report');
                // Set row height
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Style title
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
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }
            }
        ];
    }
}
