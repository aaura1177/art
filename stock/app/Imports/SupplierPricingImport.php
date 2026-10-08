<?php

namespace App\Imports;

use App\product;
use App\supplier;
use App\supplierProduct;
use App\Support\SupplierProductPriceLogWriter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierPricingImport implements ToCollection, WithHeadingRow
{
    private int $saved = 0;

    private int $skipped = 0;

    public function summary(): string
    {
        return 'Supplier pricing import finished: '.$this->saved.' row(s) applied, '.$this->skipped.' row(s) skipped.';
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $row instanceof Collection ? $row->toArray() : (array) $row;

            $sku = $this->firstPresent($data, ['sku', 'product_sku', 'code', 'product_code']);
            $supplierName = $this->firstPresent($data, ['supplier_name', 'supplier', 'c_name', 'supplier_1st']);
            $price = $this->firstPresent($data, ['price', 'rate', 'supplier_rate']);
            $uk45Price = $this->firstPresent($data, ['uk_45_price', 'uk_45_rate', 'uk45_price', 'uk45_rate']);

            if ($sku === null || $supplierName === null || $price === null) {
                $this->skipped++;

                continue;
            }

            $sku = trim((string) $sku);
            $supplierName = trim((string) $supplierName);
            if ($sku === '' || $supplierName === '') {
                $this->skipped++;

                continue;
            }

            $product = product::where('code', $sku)->first();
            $supplier = supplier::where('c_name', $supplierName)->first();
            if (! $product || ! $supplier) {
                $this->skipped++;

                continue;
            }

            if (! is_numeric($price)) {
                $this->skipped++;

                continue;
            }

            if ($uk45Price !== null && $uk45Price !== '' && ! is_numeric($uk45Price)) {
                $this->skipped++;

                continue;
            }

            $rate = $price + 0;
            $uk45Rate = ($uk45Price !== null && $uk45Price !== '') ? ($uk45Price + 0) : null;

            $sp = supplierProduct::where('product_id', $product->id)->where('supplier_id', $supplier->id)->first();
            if ($sp) {
                $before = SupplierProductPriceLogWriter::buildState($sp);
                $sp->rate = $rate;
                if ($uk45Rate !== null) {
                    $sp->uk_45_rate = $uk45Rate;
                }
                $sp->save();
                SupplierProductPriceLogWriter::log($sp, 'update', 'import', $before);
            } else {
                $sp = supplierProduct::create([
                    'product_id' => $product->id,
                    'supplier_id' => $supplier->id,
                    'rate' => $rate,
                    'uk_45_rate' => $uk45Rate,
                ]);
                SupplierProductPriceLogWriter::log($sp, 'create', 'import', null);
            }
            $this->saved++;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function firstPresent(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }
}
