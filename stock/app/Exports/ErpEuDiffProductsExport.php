<?php

namespace App\Exports;

use App\ErpEuProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ErpEuDiffProductsExport implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;
    public function __construct()
    {
        
    }

    public function collection()
    {

        $ErpProduct = ErpEuProduct::whereRaw('erp_eu_products.quantity != erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();

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
            number_format($ErpProduct->eu_quantity),
            number_format($ErpProduct->quantity),
            ($ErpProduct->eu_quantity - $ErpProduct->quantity)
        ];
    }
}
