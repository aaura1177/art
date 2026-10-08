<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class PricingExcelSheetFormatExport implements FromCollection, WithHeadings
{
    /**
     * Return empty collection (no data, only header)
     */
    public function collection()
    {
        return new Collection([]);
    }

    /**
     * Define the Excel sheet header row
     */
    public function headings(): array
    {
        return [
            'product_sku',
            'buying_cost',
        ];
    }
}
