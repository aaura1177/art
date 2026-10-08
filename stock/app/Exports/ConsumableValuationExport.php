<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConsumableValuationExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Consumable Code', 'Consumable Name', 'Quantity', 'Rate', 'Amount'];
    }

    public function map($row): array
    {
        return [
            $row['code'] ?? '',
            $row['name'] ?? '',
            $row['quantity'] ?? 0,
            $row['rate'] ?? 0,
            $row['total_value'] ?? 0,
        ];
    }
}
