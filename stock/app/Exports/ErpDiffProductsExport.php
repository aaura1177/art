<?php

namespace App\Exports;

use App\ErpProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ErpDiffProductsExport implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;
    public function __construct()
    {
    }

    public function collection()
    {
        $location = \Request::session()->get('country');
        $ErpProduct = ErpProduct::where('site_access', $location)->whereRaw('(erp_products.quantity + erp_products.fullfillment_qty) != erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->get();
        return $ErpProduct;
    }

    public function headings(): array
    {
        return [
            "SKU", "India Quantity", "Fulfillment Quantity", "Warehouse Quantity", "Discrepancy"
        ];
    }

    public function map($ErpProduct): array
    {
        return [
            $ErpProduct->sku,
            number_format($ErpProduct->quantity),
            number_format($ErpProduct->fullfillment_qty),
            number_format($ErpProduct->warehouse_quantity),
            ($ErpProduct->warehouse_quantity - ($ErpProduct->quantity + $ErpProduct->fullfillment_qty))
            // ($ErpProduct->warehouse_quantity - $ErpProduct->quantity)
        ];
    }
}
