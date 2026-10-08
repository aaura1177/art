<?php

namespace App\Exports;
use Illuminate\Http\Request;
use App\SustainabilityStage3Miscellaneous;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class SustainabilityStage3MiscellaneousExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $date_from;
    protected $date_to;

    public function __construct($search = '', $date_from = '', $date_to = '')
    {
        $this->search = $search;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function collection()
    {
        $search = $this->search;

        $query = SustainabilityStage3Miscellaneous::when($search, function ($query) use ($search) {
            $query->where('user_name', 'like', '%' . $search . '%')
                ->orWhere('travel_mode', 'like', '%' . $search . '%')
                ->orWhere('origin_name', 'like', '%' . $search . '%')
                ->orWhere('destination_name', 'like', '%' . $search . '%');
        });

        if ($this->date_from && $this->date_to) {
            $query->whereBetween('month_year', [$this->date_from, $this->date_to]);
        }
    
        $records = $query->orderBy('created_at', 'desc')->get();
        
        // Compute totals
        $totalDistance = $records->sum('distance_travelled');
        $totalEmission = $records->sum('carbon_emission');

        // Add total row
        $records->push((object)[
            'month_year' => null,
            'user_name' => '',
            'travel_mode' => '',
            'origin_name' => '',
            'destination_name' => '',
            'distance_travelled' => $totalDistance,
            'carbon_emission' => $totalEmission,
        ]);

        return $records;
        
    }

    public function headings(): array
    {
        return [
            'Month-Year',
            'Person Name',
            'Travel Mode',
            'Origin',
            'Destination',
            'Distance Travelled (Km)',
            'Carbon Emission'
        ];
    }

    public function map($record): array
    {
        return [
            $record->month_year ? date('d M Y', strtotime($record->month_year)) : '',
            $record->user_name,
            $record->travel_mode,
            $record->origin_name,
            $record->destination_name,
            $record->distance_travelled,
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
                $sheet->setCellValue('A1', 'Sustainability Stage 3 Miscellaneous Report');
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
                $sheet->mergeCells("A{$totalRowIndex}:E{$totalRowIndex}");
                //Set title text in the merged cell (leftmost cell of the range)
                $sheet->setCellValue("A{$totalRowIndex}", 'Total');
                $event->sheet->getStyle("A{$totalRowIndex}:E{$totalRowIndex}")->applyFromArray([
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
