<?php

namespace App\Imports;

use App\product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductWeightLbsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['sku'])) {
            return null;
        }

        $product = product::where('code', trim($row['sku']))->first();

        if (!$product) {
            return null;
        }

        if (!empty($row['product_weight']) && is_numeric($row['product_weight'])) {
            $product->net_weight = (float) $row['product_weight'];
        }

        if (!empty($row['boxedweight']) && is_numeric($row['boxedweight'])) {
            $product->gross_weight = (float) $row['boxedweight'];
        }

        $product->save();

        return $product;
    }
}
