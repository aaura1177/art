<?php

namespace App\Exports;

use App\ErpUsProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ErpUsDiffProductsExport implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;
    public function __construct()
    {
        
    }

    public function collection()
    {

        $ErpProduct = ErpUsProduct::whereRaw('erp_us_products.quantity != erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();

        return $ErpProduct;
    }

    public function headings(): array {
        return [
           "SKU","Warehouse Quantity","India Quantity","Discrepancy"
        ];
    }

    public function map($ErpProduct): array
    {
        return [
            $ErpProduct->sku,
            number_format($ErpProduct->us_quantity),
            number_format($ErpProduct->quantity),
            ($ErpProduct->us_quantity - $ErpProduct->quantity)
        ];
    }
}
