<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinishingPriceRecalcExport implements FromCollection, WithHeadings
{
    /** @var Collection<int, array<int, int|float|string>> */
    protected $rows;

    /**
     * @param Collection<int, array<int, int|float|string>> $rows
     */
    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Code',
            'Name',
            'Finishing',
            'Rate',
            'Volume m3',
            'Old finishing price',
            'New base (rate only)',
            'Extra finishing price',
            'New finishing price',
            'Difference',
            'Note',
        ];
    }
}
