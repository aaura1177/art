<?php

namespace App\Exports;

use App\supplierProduct;
use Maatwebsite\Excel\Concerns\FromArray;

class SupplierPricingWideExport implements FromArray
{
    public function array(): array
    {
        $downloadedAt = now()->format('Y-m-d H:i:s');

        $grouped = supplierProduct::with(['product', 'supplier'])
            ->whereHas('product')
            ->whereHas('supplier')
            ->get()
            ->groupBy('product_id')
            ->sortBy(function ($group) {
                $product = $group->first()->product;

                return $product ? $product->code : '';
            });

        $max = (int) $grouped->map->count()->max();
        if ($max < 1) {
            return [
                ['Downloaded as on', $downloadedAt],
                ['Product SKU'],
            ];
        }

        $header = ['Product SKU'];
        for ($i = 1; $i <= $max; $i++) {
            $header[] = 'Supplier '.$i;
            $header[] = 'Price '.$i;
            $header[] = 'UK 45 Price '.$i;
        }

        $rows = [
            ['Downloaded as on', $downloadedAt],
            $header,
        ];

        foreach ($grouped as $group) {
            $group = $group->sortBy(function ($sp) {
                return optional($sp->supplier)->c_name ?? '';
            })->values();
            $product = $group->first()->product;
            $row = [$product ? $product->code : 'N/A'];
            foreach ($group as $sp) {
                $row[] = optional($sp->supplier)->c_name ?? '';
                $row[] = $sp->rate;
                $row[] = $sp->uk_45_rate;
            }
            $missingTriplets = ($max - $group->count()) * 3;
            for ($j = 0; $j < $missingTriplets; $j++) {
                $row[] = '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
