<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\SustainabilityStage2;
use App\SustainabilityVariable;
use App\supplier;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class SustainabilityStage2ExportPowerConsumptionYearly implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $date_from;
    protected $date_to;

    protected $present_supplier_ids;
    protected $suppliers; 
    protected $supplierTotals;
    protected $overallTotalEmission;

    protected $records;
    public function __construct($search = '', $date_from = '', $date_to = '')
    {
        $this->search = $search;
        $this->date_from = $date_from;
        $this->date_to = $date_to;

        // Fetch suppliers once
        $this->suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get()->keyBy('id');

        $query = SustainabilityStage2::where('type', 'Electricity')->with('supplierInfo');

        if ($this->search) {
            $query->whereHas('supplierInfo', function ($q) {
                $q->where('c_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->date_from && $this->date_to) {
            $query->whereBetween('month_year', [$this->date_from, $this->date_to]);
        }
       
        $query = $query->orderBy('created_at', 'desc')->get();

        $this->records = collect($query);

        // Only keep supplier IDs that are actually in the records
        $this->present_supplier_ids = $query->pluck('supplier_id')->unique()->values();
    }

    public function collection(): Collection
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $carbonRate = $sustainabilityVariable->carbon_emission_factor_per_kwh ?? 0;
    
        // Pre-calculate grouped data
        $grouped = $this->records->groupBy(function ($item) {
            return date('Y-m', strtotime($item->month_year)); // Group by Year-Month
        });
    
        $dataRows = [];
        $columnSums = array_fill(0, $this->present_supplier_ids->count() + 2, 0); // Suppliers + Total Kms + Emission
    
        foreach ($grouped as $monthKey => $monthRecords) {
            $row = [];
            $formattedMonth = date('M - Y', strtotime($monthKey . '-01'));
            $row[] = $formattedMonth;
        
            $totalPower = 0;

            foreach ($this->present_supplier_ids as $i => $supplierId) {
                $supplierPower = $monthRecords
                    ->where('supplier_id', $supplierId)
                    ->sum('power_consumed');

                $row[] = $supplierPower;

                $columnSums[$i] += $supplierPower;
                $totalPower += $supplierPower;
            }

            $emission = $totalPower * $carbonRate;
            $row[] = $totalPower;
            $row[] = $emission;

            // Update totals
            $columnSums[$this->present_supplier_ids->count()] += $totalPower;
            $columnSums[$this->present_supplier_ids->count() + 1] += $emission;
    
            $dataRows[] = collect($row);
        }
    
        // Add total row
        $totalRow = collect();
        $totalRow[] = 'Total'; // First column (Month-Year)
    
        foreach ($columnSums as $sum) {
            $totalRow[] = $sum;
        }
    
        $dataRows[] = $totalRow;
    
        return collect($dataRows);
    }
    

    public function headings(): array
    {
        $headers[] = 'Month-Year';
        
        foreach ($this->present_supplier_ids as $supplierId) {
            $headers[] = $this->suppliers[$supplierId]->c_name ?? 'Unknown Supplier';
        }
        $headers[] = 'Total (KWh)';
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
                $sheet->setCellValue('A1', 'Sustainability Stage 2 - Artisan Production Power Consumption');
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
