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

class SustainabilityStage6ExportYearly implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $date_from;
    protected $date_to;

    protected $records;
    public function __construct($search = '', $date_from = '', $date_to = '')
    {
        $this->search = $search;
        $this->date_from = $date_from;
        $this->date_to = $date_to;

        // Pre-initialize as empty collection to avoid null error
        $query = SustainabilityStage6::when($search, function ($query) use ($search) {
            $query->where('location', 'like', '%' . $search . '%');
        });

        if ($this->date_from && $this->date_to) {
            $query->whereBetween('month_year', [$this->date_from, $this->date_to]);
        }

        $query = $query
            ->orderByDesc('month_year')
            ->orderBy('location')
            ->get();

        $this->records = collect($query);

    }

    public function collection(): Collection
    {
        // Pre-calculate grouped data
        $grouped = $this->records->groupBy(function ($item) {
            return date('Y-m', strtotime($item->month_year)); // Group by Year-Month
        });

        $dataRows = [];

        // Initialize cumulative totals
        $cumulative = [
            'IN' => 0,
            'UK' => 0,
            'US' => 0,
            'EU' => 0,
            'CA' => 0,
            'total_parcel' => 0,
            'total_emission' => 0,
        ];

        foreach ($grouped as $monthKey => $monthRecords) {
            $totals = [
                'IN' => $monthRecords->where('location', 'IN')->sum('parcel_delivered'),
                'UK' => $monthRecords->where('location', 'UK')->sum('parcel_delivered'),
                'US' => $monthRecords->where('location', 'US')->sum('parcel_delivered'),
                'EU' => $monthRecords->where('location', 'EU')->sum('parcel_delivered'),
                'CA' => $monthRecords->where('location', 'CA')->sum('parcel_delivered'),
            ];

            $total_carbon_emission = [
                'IN' => $monthRecords->where('location', 'IN')->sum('carbon_emission'),
                'UK' => $monthRecords->where('location', 'UK')->sum('carbon_emission'),
                'US' => $monthRecords->where('location', 'US')->sum('carbon_emission'),
                'EU' => $monthRecords->where('location', 'EU')->sum('carbon_emission'),
                'CA' => $monthRecords->where('location', 'CA')->sum('carbon_emission'),
            ];

            $total_parcel_delivered = array_sum($totals);
            $total_emission = array_sum($total_carbon_emission);


            $total_emission = $total_parcel_delivered *  $total_emission;

            // Add to cumulative totals
            foreach (['IN', 'UK', 'US', 'EU', 'CA'] as $loc) {
                $cumulative[$loc] += $totals[$loc];
            }
            $cumulative['total_parcel'] += $total_parcel_delivered;
            $cumulative['total_emission'] += $total_emission;

            $formattedMonth = date('M - Y', strtotime($monthKey . '-01'));
            $dataRows[] = collect([
                $formattedMonth,
                $totals['IN'],
                $totals['UK'],
                $totals['US'],
                $totals['EU'],
                $totals['CA'],
                $total_parcel_delivered,
                $total_emission,
            ]);
        }

        // Add final Total row
        $dataRows[] = collect([
            'Total',
            $cumulative['IN'],
            $cumulative['UK'],
            $cumulative['US'],
            $cumulative['EU'],
            $cumulative['CA'],
            $cumulative['total_parcel'],
            $cumulative['total_emission'],
        ]);

        return collect($dataRows);
    }

    public function headings(): array
    {
        $headers[] = 'Month-Year';
        
        $headers[] = 'IN';
        $headers[] = 'UK';
        $headers[] = 'US';
        $headers[] = 'EU';
        $headers[] = 'CA';
        $headers[] = 'Total Parcel Delivered';
        $headers[] = 'Carbon Emission';

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
                $sheet->setCellValue('A1', 'Sustainability Stage 6 Report');
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
        return $row->toArray(); // Because rows are collections
    }
}
