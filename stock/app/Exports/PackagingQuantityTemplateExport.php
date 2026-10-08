<?php

namespace App\Exports;

use App\packaging;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PackagingQuantityTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $rows = [];

        $packagingRows = packaging::with('product')
            ->whereHas('product', function ($q) {
                $q->whereNotNull('code')->where('code', '!=', '');
            })
            ->orderBy('id')
            ->get();

        foreach ($packagingRows as $packaging) {
            if (!$packaging->product) {
                continue;
            }

            $rows[] = [
                'product_code' => $packaging->product->code,
                'quantity1' => $packaging->box_1_qty,
                'quantity2' => $packaging->box_2_qty,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['product_code', 'quantity1', 'quantity2'];
    }
}

