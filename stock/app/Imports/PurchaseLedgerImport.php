<?php

namespace App\Imports;

use App\product;
use App\ProductLedger;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PurchaseLedgerImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $updatedProducts = [];

        foreach ($rows as $key => $row) {
            if ($key === 0) continue; // Skip header row

            $productId = trim($row[0]); // Get product ID from the first column
            $purchaseLedgerName = trim($row[5]); // Get purchase ledger name

            // Find product by ID
            $product = product::where('id', $productId)->first();

            if ($product) {
                // Find the corresponding purchase ledger ID
                $purchaseLedger = ProductLedger::where('name', $purchaseLedgerName)->first();

                if ($purchaseLedger) {
                    $oldValue = $product->product_ledger_id;
                    $product->update(['product_ledger_id' => $purchaseLedger->id]);

                    $updatedProducts[] = [
                        'code' => $product->code,
                        'Old purchase_ledger_id' => $oldValue,
                        'New purchase_ledger_id' => $purchaseLedger->id,
                    ];
                } else {
                    $updatedProducts[] = [
                        'code' => $product->code,
                        'error' => 'Purchase ledger not found',
                    ];
                }
            } else {
                $updatedProducts[] = [
                    'code' => $product->code,
                    'error' => 'Product not found',
                ];
            }
        }

        // Save import log
        \Storage::put('logs/import_log.json', json_encode($updatedProducts, JSON_PRETTY_PRINT));
    }
}
