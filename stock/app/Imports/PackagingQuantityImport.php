<?php

namespace App\Imports;

use App\packaging;
use App\product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PackagingQuantityImport implements ToCollection, WithHeadingRow
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

            $q1 = $this->normalizeQuantity($row['quantity1'] ?? null);
            $q2 = $this->normalizeQuantity($row['quantity2'] ?? null);

            if ($q1 !== null) {
                $packaging->box_1_qty = max(0, $q1);
            }

            if ($q2 !== null) {
                $packaging->box_2_qty = max(0, $q2);
            }

            $packaging->save();
        }
    }
}

