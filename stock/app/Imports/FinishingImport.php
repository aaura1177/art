<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;


class FinishingImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach ($rows as $row) {
            // Find the product by code
            $product = Product::where('code', $row['product_code'])->first();

            // If product exists, update the finishing and finishing_price
            if ($product) {
                $product->update([
                    'finishing' => $row['finishing'],
                    'finishing_price' => $row['finishing_price']
                ]);
            }
        }

    }
}
