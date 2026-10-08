<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\SustainabilityStage3;
use App\SustainabilityVariable;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class SustainabilityStage3ExportPowerConsumptionYearly implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
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

        
        $query = SustainabilityStage3::where('type', 'Electricity');

        if ($this->search) {
            $query->where('office_type', 'like', '%' . $this->search . '%');
        }

        if ($this->date_from && $this->date_to) {
            $query->whereBetween('month_year', [$this->date_from, $this->date_to]);
        }
       
        $query = $query->orderBy('created_at', 'desc')->get();

        $this->records = collect($query);

    }

    public function collection(): Collection
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $carbonRate = $sustainabilityVariable->carbon_emission_factor_per_kwh ?? 0;
    
        // Group records by month
        $grouped = $this->records->groupBy(function ($item) {
            return date('Y-m', strtotime($item->month_year)); // Group by Year-Month
        });
    
        $dataRows = [];
    
        // Initialize cumulative totals
        $totalFactory = 0;
        $totalOffice = 0;
        $totalUkOffice = 0;
        $totalPowerAll = 0;
        $totalEmissionAll = 0;
    
        foreach ($grouped as $monthKey => $monthRecords) {
            $factory = $monthRecords->where('office_type', 'Factory')->sum('power_consumed');
            $office = $monthRecords->where('office_type', 'Office')->sum('power_consumed');
            $ukOffice = $monthRecords->where('office_type', 'Uk Office')->sum('power_consumed');
    
            $total_power = $factory + $office + $ukOffice;
            $total_emission = $total_power * $carbonRate;
    
            // Accumulate totals
            $totalFactory += $factory;
            $totalOffice += $office;
            $totalUkOffice += $ukOffice;
            $totalPowerAll += $total_power;
            $totalEmissionAll += $total_emission;
    
            // Format row
            $formattedMonth = date('M - Y', strtotime($monthKey . '-01'));
            $dataRows[] = collect([
                $formattedMonth,
                $factory,
                $office,
                $ukOffice,
                $total_power,
                $total_emission,
            ]);
        }
    
        // Add total row at the end
        $dataRows[] = collect([
            'Total',
            $totalFactory,
            $totalOffice,
            $totalUkOffice,
            $totalPowerAll,
            $totalEmissionAll,
        ]);
    
        return collect($dataRows);
    }
    
    

    public function headings(): array
    {
        $headers[] = 'Month-Year';
        $headers[] = 'Factory';
        $headers[] = 'Office';
        $headers[] = 'Uk Office';
        $headers[] = 'Total Consumption (KWh)';
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
                $sheet->setCellValue('A1', 'Sustainability Stage 3 - Artisan Production Power Consumption');
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
