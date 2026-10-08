<?php

namespace App\Imports;

use App\product;
use App\consumable;
use App\UnitType;
use App\WfConsumable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class ProductConsumableImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $rows->shift();
        foreach ($rows as $index => $row) {
            $rowIndex       = $index + 2;
            // Expected template column order:
            // [0] S.No (optional), [1] Product Code
            // [consumable name 1] [qty 1] ... [consumable name 15] [qty 15]
            $productCodeFromCol1 = isset($row[1]) ? trim((string) $row[1]) : '';
            $productCodeFromCol0 = isset($row[0]) ? trim((string) $row[0]) : '';

            $productCodeCol = $productCodeFromCol1 !== '' ? 1 : 0;
            $productKey = $productCodeCol === 1 ? $productCodeFromCol1 : $productCodeFromCol0;

            if ($productKey === '') {
                continue;
            }

            $product = product::where('code', $productKey)->first();
            if (!$product) {
                Log::warning("Row skipped: Product not found", [
                    'row_index'   => $rowIndex,
                    'product_key' => $productKey,
                ]);
                continue;
            }

            // After product code column, columns are:
            // name(i) at +1 + (i-1)*2 and qty(i) at +2 + (i-1)*2
            $consumableNameColStart = $productCodeCol + 1;
            $qtyColStart = $productCodeCol + 2;

            for ($i = 1; $i <= 15; $i++) {
                $consumableCol = $consumableNameColStart + (($i - 1) * 2);
                $qtyCol = $qtyColStart + (($i - 1) * 2);

                $consumableKey = isset($row[$consumableCol]) ? trim((string) $row[$consumableCol]) : '';
                if ($consumableKey === '') {
                    continue;
                }

                $rawQty = $row[$qtyCol] ?? null;
                if ($rawQty === null) {
                    continue;
                }
                if (is_string($rawQty) && trim($rawQty) === '') {
                    // Skip slots where qty is not provided.
                    continue;
                }
                if ($rawQty === '') {
                    continue;
                }
                $qty = (float) $rawQty;

                $consumable = consumable::where('name', $consumableKey)->first();
                if (!$consumable) {
                    Log::warning("Consumable not found, skipped", [
                        'row_index'      => $rowIndex,
                        'product_key'    => $productKey,
                        'consumable_key' => $consumableKey,
                    ]);
                    continue;
                }

                $unitType = UnitType::find($consumable->unit_type_id);
                $unitTypeId = $unitType ? $unitType->id : 0;
                $unitTypeName = $unitType ? $unitType->name : '';

                try {
                    WfConsumable::updateOrCreate(
                        [
                            'product_id'     => $product->id,
                            'consumables_id' => $consumable->id,
                        ],
                        [
                            'unit_type_id'   => $unitTypeId,
                            'unit_type_name' => $unitTypeName,
                            'qty'            => $qty,
                        ]
                    );

                    Log::info("Inserted/Updated successfully", [
                        'product' => $productKey,
                        'consumable' => $consumableKey,
                        'slot' => $i,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Insert failed", [
                        'row_index'   => $rowIndex,
                        'product_key' => $productKey,
                        'consumable'  => $consumableKey,
                        'message'     => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
