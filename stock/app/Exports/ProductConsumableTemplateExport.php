<?php

namespace App\Exports;

use App\product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Dummy Excel template for Consumable import.
 *
 * Contains:
 * - All Product Codes from `product` table
 * - For each product: Consumable Name 1..15 and Qty 1..15 columns
 */
class ProductConsumableTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $rows = [];

        $products = product::query()
            ->orderBy('code')
            ->get(['id', 'code']);

        $sno = 1;
        foreach ($products as $p) {
            // S.No, Product Code, then 15 pairs: (Consumable Name, Qty)
            $row = [$sno++, (string) $p->code];

            for ($i = 1; $i <= 15; $i++) {
                $row[] = ''; // Consumable Name i
                $row[] = ''; // Qty i
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $headings = ['S.No', 'Product Code'];

        for ($i = 1; $i <= 15; $i++) {
            $headings[] = 'Consumable Name ' . $i;
            $headings[] = 'Qty ' . $i;
        }

        return $headings;
    }
}
