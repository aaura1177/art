<?php

namespace App\Exports;
use Illuminate\Http\Request;
use App\SustainabilityStage3Employee;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class SustainabilityStage3EmployeeExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $month;
    protected $year;

    public function __construct($search, $month, $year)
    {
        $this->search = $search ?? '';
        $this->month = $month ?? '';
        $this->year = $year ?? '';
    }

    public function collection()
    {
        $search = $this->search;

        $query = SustainabilityStage3Employee::when($search, function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%');
            });

        if (!empty($this->month) && !empty($this->year)) {
            // Full date match
            $query->where('month_year', '=', $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '-01');
        } elseif (!empty($this->year)) {
            // Match year only
            $query->whereYear('month_year', $this->year);
        } elseif (!empty($this->month)) {
            // Match month only (any year)
            $query->whereMonth('month_year', $this->month);
        }
    
        $records = $query->orderBy('created_at', 'desc')->get();
        
        // Compute totals
        $totalDistance = $records->sum('distance_travelled');
        $totalRounds = $records->sum('number_of_rounds');
        $totalDays = $records->sum('days');
        $totalEmission = $records->sum('carbon_emission');

        // Add total row
        $records->push((object)[
            'month_year' => null,
            'user_name' => 'Total',
            'distance_travelled' => $totalDistance,
            'number_of_rounds' => $totalRounds,
            'days' => $totalDays,
            'vehicle_type' => '',
            'fuel_type' => '',
            'carbon_emission' => $totalEmission,
        ]);

        return $records;
        
    }

    public function headings(): array
    {
        return [
            'Month-Year',
            'User Name',
            'Vehicle Type',
            'Fuel Type',
            'Distance Travelled (One Way)',
            'Number Of Rounds',
            'Days',
            'Total Distance Travelled (Km)',
            'Carbon Emission'
        ];
    }

    public function map($record): array
    {
        return [
            $record->month_year ? date('M - Y', strtotime($record->month_year)) : '',
            $record->user_name,
            $record->vehicle_type,
            $record->fuel_type,
            $record->distance_travelled,
            $record->number_of_rounds,
            $record->days,
            $record->distance_travelled * $record->number_of_rounds * $record->days,
            $record->carbon_emission,
        ];
    }

    public function startCell(): string
    {
        return 'A2'; // Data starts on row 2 (row 1 is title)
    }

    public function styles(Worksheet $sheet): array
    {

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
           
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge cells for the title
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', 'Sustainability Stage 3 Employee Report');
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

                // Highlight the total row
                $totalRowIndex = $highestRow;
                $sheet->mergeCells("A{$totalRowIndex}:D{$totalRowIndex}");
                // Set title text in the merged cell (leftmost cell of the range)
                $sheet->setCellValue("A{$totalRowIndex}", 'Total');
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

                

            }
        ];
    }
}
