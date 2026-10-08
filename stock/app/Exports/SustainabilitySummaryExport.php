<?php

namespace App\Exports;

use App\Support\SustainabilityCountryEmissions;
use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings, WithStyles, WithEvents, WithCustomStartCell};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;

class SustainabilitySummaryExport implements FromArray, WithHeadings, WithStyles, WithEvents, WithCustomStartCell
{
    protected $search;
    protected $date_from;
    protected $date_to;
    protected $months;
    protected $title;
    protected int $summaryStartRow = 11;
    protected int $summaryEndRow = 19;

    public function __construct($search, $date_from, $date_to, $months)
    {
        $this->search = $search ?? '';
        $this->date_from = $date_from ?? '';
        $this->date_to = $date_to ?? '';
        $this->months = $months ?? 1;
        $this->title = "Carbon Emissions Report (" . date("M Y", strtotime($this->date_from)) . " to " . date("M Y", strtotime($this->date_to)) . ")";
    }

    public function array(): array
    {
        $stages = [
            'stage0' => 'Tree plantation and carbon sequestration impact',
            'stage1' => 'Upstream Raw Material Emissions. Covering emissions from sourcing raw timber and operations at the sawmill, including transportation to the artisans',
            'stage2' => 'Artisan Production Emissions. Emissions associated with the artisanal crafting and initial processing of the furniture',
            'stage3' => 'Factory Finishing Emissions. Encompassing emissions from the Jaipur factory for polishing, hardware fixation, packaging, and other finishing processes',
            'stage4' => 'Transport and Maritime Emissions. Accounting for emissions from transporting the products to the Indian port and the subsequent sea voyage to the destination port',
            'stage5' => 'Inbound Logistics Emissions. Emissions from the final leg of transportation from the destination port to the local distribution centre',
            'stage6' => 'Last Mile Distribution Emissions. Focused on the emissions from delivering the products from the Ipswich warehouse to the end customer',
        ];

        $rows = [];
        $totalEmissions = 0;
        $sequestrationTotal = 0;
        $total_qty_delivered_per_month = 0;
        $total_container_sent = 0;
        $savedS123Period = 0.0;
        $savedS4Period = 0.0;
        $savedS5Period = 0.0;

        foreach ($stages as $table => $process) {
            if ($table === 'stage0') {
                $emission = DB::table("sustainability_{$table}")
                    ->whereBetween('month_year', [$this->date_from, $this->date_to])
                    ->whereNull('deleted_at')
                    ->sum('carbon_sequestration');

                $total_qty_delivered_per_month = DB::table("sustainability_" . $table)
                    ->whereBetween('month_year', [$this->date_from, $this->date_to])
                    ->whereNull('deleted_at')
                    ->sum('total_qty_delivered_by_containers');
            } else {
                $emission = DB::table("sustainability_{$table}")
                    ->whereBetween('month_year', [$this->date_from, $this->date_to])
                    ->whereNull('deleted_at')
                    ->sum('carbon_emission');

                if ($table === 'stage3') {
                    $misc = DB::table("sustainability_{$table}_miscellaneous")
                        ->whereBetween('month_year', [$this->date_from, $this->date_to])
                        ->whereNull('deleted_at')
                        ->sum('carbon_emission');
                    $emission += $misc;

                    $employee = DB::table("sustainability_{$table}_employee")
                        ->whereBetween('month_year', [$this->date_from, $this->date_to])
                        ->whereNull('deleted_at')
                        ->sum('carbon_emission');
                    $emission += $employee;
                }

                if ($table === 'stage5') {
                    $total_container_sent = DB::table("sustainability_" . $table)
                        ->whereBetween('month_year', [$this->date_from, $this->date_to])
                        ->whereNull('deleted_at')
                        ->sum('container_sent');
                }
            }

            $monthly = $this->months > 0 ? $emission / $this->months : 0;

            $rows[] = [
                ucfirst($table),
                $process,
                round($monthly, 5),
            ];

            if ($table === 'stage0') {
                $sequestrationTotal = $monthly;
            } else {
                $totalEmissions += $monthly;

                if (in_array($table, ['stage1', 'stage2', 'stage3'], true)) {
                    $savedS123Period += (float) $emission;
                } elseif ($table === 'stage4') {
                    $savedS4Period = (float) $emission;
                } elseif ($table === 'stage5') {
                    $savedS5Period = (float) $emission;
                }
            }
        }

        $avg_monthly_containers = ($this->months > 0) ? ($total_container_sent / $this->months) : 0;
        $emission_per_container = ($avg_monthly_containers > 0)
            ? round($totalEmissions / $avg_monthly_containers, 5) : "0";

        $avg_pieces_per_container = ($total_container_sent > 0)
            ? ($total_qty_delivered_per_month / $total_container_sent) : 0;
        $per_product_emission = ($avg_pieces_per_container > 0)
            ? round($emission_per_container / $avg_pieces_per_container, 5) : "0";

        $perProductByCountry = SustainabilityCountryEmissions::perProductByCountry(
            $this->date_from,
            $this->date_to,
            $savedS123Period,
            $savedS4Period,
            $savedS5Period,
            (float) $total_qty_delivered_per_month
        );

        // Summary rows (data starts at Excel row 3 after title+header; blank spacer + N summary rows)
        $rows[] = [''];
        $summaryRows = [
            ['Total carbon emission per month', '', round($totalEmissions, 5)],
            ['Average Monthly Sequestration', '', round($sequestrationTotal, 5)],
            ['Net Emissions', '', round($sequestrationTotal - $totalEmissions, 5)],
            ['Emission Per Container', '', $emission_per_container],
            ['Per Product Emissions (Overall)', '', $per_product_emission],
        ];

        foreach ($perProductByCountry as $label => $value) {
            $summaryRows[] = [$label, '', $value];
        }

        // Title row 1, headings row 2, stage rows 3..(2+stageCount), blank, then summary
        $stageCount = count($stages);
        $this->summaryStartRow = 2 + $stageCount + 1 + 1; // after blank spacer
        $this->summaryEndRow = $this->summaryStartRow + count($summaryRows) - 1;

        $noteRows = [
            [''],
            ['How Per Product Emissions are calculated', '', ''],
            ['Overall = total Stage 1–6 carbon ÷ total products (same as Emission Per Container ÷ average pieces per container).', '', ''],
            ['By country = shared Stages 1–3 by product share + saved Stages 4–5 by destination share + Stage 6 by location, then ÷ that country’s products.', '', ''],
            ['Do not add country Per Product rates together; weighted by qty they average back to the Overall figure. Destinations outside UK/US/EU/CA/IN are in Overall only.', '', ''],
        ];

        return array_merge($rows, $summaryRows, $noteRows);
    }

    public function headings(): array
    {
        return [
            'Stage',
            'Process',
            'Carbon Emission',
        ];
    }

    public function startCell(): string
    {
        return 'A2'; // Data starts on row 2 (row 1 is title)
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        // Header row (Row 2)
        $styles[2] = [
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
        ];

        foreach (range($this->summaryStartRow, $this->summaryEndRow) as $row) {
            $styles[$row] = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFF2CC'], // Light yellow
                ],
            ];
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge cells for the title
                $highestColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', $this->title);
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

                for ($row = $this->summaryStartRow; $row <= $this->summaryEndRow; $row++) {
                    $event->sheet->mergeCells("A{$row}:B{$row}");

                    $event->sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                    ]);
                }
            }
        ];
    }
}
