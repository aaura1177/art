<?php

namespace App\Imports;

use App\packaging;
use App\product;
use App\ProductCarton;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CartonProductsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private function normalizeQuantity($raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }
        }

        if (!is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $code = isset($row['product_code']) ? trim((string) $row['product_code']) : '';
            if ($code === '') {
                continue;
            }

            $product = product::where('code', $code)->first();
            if (!$product) {
                continue;
            }

            $packaging = packaging::where('product_id', $product->id)->first();
            if (!$packaging) {
                continue;
            }

            $existing = ProductCarton::where('product_id', $product->id)->first();
            $q1Normalized = $this->normalizeQuantity($row['quantity1'] ?? null);
            $q2Normalized = $this->normalizeQuantity($row['quantity2'] ?? null);

            if ($existing === null && $q1Normalized === null && $q2Normalized === null) {
                continue;
            }

            $q1 = $q1Normalized ?? $existing?->quantity1;
            $q2 = $q2Normalized ?? $existing?->quantity2;

            ProductCarton::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'packaging_id' => $packaging->id,
                    'quantity1' => $q1,
                    'quantity2' => $q2,
                ]
            );
        }
    }
}
