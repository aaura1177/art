<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\product;
use App\ProductCarton;


class CartonProductsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $rows = [];

        $products = product::orderBy('code')->get(['id', 'code']);
        $productIds = $products->pluck('id')->all();
        $cartonsByProductId = ProductCarton::whereIn('product_id', $productIds)
            ->get(['product_id', 'quantity1', 'quantity2'])
            ->keyBy('product_id');

        foreach ($products as $p) {
            $carton = $cartonsByProductId->get($p->id);

            $rows[] = [
                'product_code' => $p->code,
                'quantity1' => $carton?->quantity1,
                'quantity2' => $carton?->quantity2,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['product_code', 'quantity1', 'quantity2'];
    }
}
