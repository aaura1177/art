<?php

namespace App\Exports;

use App\ErpProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ErpProductsExport implements FromCollection, WithHeadings, WithMapping
{

    use Exportable;
    public function __construct()
    {
    }

    public function collection()
    {
        $location = \Request::session()->get('country');
        $ErpProduct = ErpProduct::where('site_access', $location)->get();

        return $ErpProduct;
    }

    public function headings(): array
    {
        return [
            "zone_name", "zone_serial", "sku", "qty"
        ];
    }

    public function map($ErpProduct): array
    {
        return [
            $ErpProduct->zone_name,
            $ErpProduct->zone_serial,
            $ErpProduct->sku,
            number_format($ErpProduct->quantity)
        ];
    }
}
