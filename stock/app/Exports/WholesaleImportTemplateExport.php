<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WholesaleImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['SKU', 'Qty'];
    }

    public function array(): array
    {
        return [
            ['IN882', 10],
            ['EXAMPLE-SKU', 25],
        ];
    }
}
