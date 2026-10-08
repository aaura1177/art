<?php

namespace App\Exports;

use App\product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductWeightLbsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $rows = [];

        $products = product::orderBy('code')->get(['code', 'net_weight', 'gross_weight']);

        foreach ($products as $p) {
            $rows[] = [
                'sku' => $p->code,
                'product_weight' => $p->net_weight,
                'boxedweight' => $p->gross_weight,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['sku', 'product_weight', 'boxedweight'];
    }
}
