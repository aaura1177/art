<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SupplierPricingImportTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['SKU', 'Supplier Name', 'Price', 'UK 45 Price'],
            ['YOUR-SKU-CODE', 'Exact supplier company name (c_name)', '0.00', '0.00'],
        ];
    }
}
