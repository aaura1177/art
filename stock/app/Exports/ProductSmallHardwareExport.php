<?php

namespace App\Exports;

use App\product;
use App\smallHardwareProducts;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Product small hardware in the same sheet layout as consumable import
 * ({@see ProductConsumableTemplateExport} / {@see ProductConsumableImport}):
 * S.No, Product Code, then 15 × (Consumable Name, Qty).
 *
 * Small hardware names and quantities are filled from `small_hardware_products`;
 * slots beyond 15 per product are omitted from this export.
 */
class ProductSmallHardwareExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $rows = [];

        $products = product::query()
            ->orderBy('code')
            ->get(['id', 'code']);

        $sno = 1;
        foreach ($products as $p) {
            $row = [$sno++, (string) $p->code];

            $links = smallHardwareProducts::with('smallhardwares')
                ->where('product_id', $p->id)
                ->orderBy('id')
                ->get();

            for ($i = 0; $i < 15; $i++) {
                if ($i < $links->count()) {
                    $link = $links[$i];
                    $name = $link->smallhardwares ? (string) $link->smallhardwares->name : '';
                    $row[] = $name;
                    $row[] = $link->quantity;
                } else {
                    $row[] = '';
                    $row[] = '';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $headings = ['S.No', 'Product Code'];

        for ($i = 1; $i <= 15; $i++) {
            $headings[] = 'Consumable Name '.$i;
            $headings[] = 'Qty '.$i;
        }

        return $headings;
    }
}
