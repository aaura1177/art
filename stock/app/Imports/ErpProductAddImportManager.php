<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\ErpProduct;
use App\ErpSheetManager;
use App\ErpHistoryManager;

class ErpProductAddImportManager implements ToCollection, WithHeadingRow
{
    use Importable;

    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        // Get the current location from the session
        $location = \Request::session()->get('country');

        // Get the latest sheet of type 'sheet'
        $sheet = ErpSheetManager::where('type', 'sheet')
            ->orderBy('id', 'desc')->first();

        // Prevent errors if no sheet exists
        if (!$sheet) {
            throw new \Exception("No valid sheet found for site access: " . $location);
        }

        $sheetId = $sheet->id;
        $sheetDate = $sheet->date;

        // Get all products with the current location
        $allProducts = ErpProduct::where('site_access', $location)->get();

        $excelSkus = array_filter(
    $rows->pluck('sku')->toArray(),
    fn($sku) => !is_null($sku) && $sku !== '' && (is_string($sku) || is_int($sku))
);

$excelSkuLookup = array_flip($excelSkus);

        // Set warehouse_quantity to 0 for products NOT in the Excel sheet
        foreach ($allProducts as $product) {
            if (!isset($excelSkuLookup[$product->sku])) {
                $product->warehouse_quantity = 0;
                $product->save();

                // Create a history entry for setting warehouse_quantity to 0
                ErpHistoryManager::create([
                    'sheet_id' => $sheetId,
                    'sku' => $product->sku,
                    'quantity' => 0,
                    'type' => 'less',
                    'date' => $sheetDate,
                    'stock' => $product->warehouse_quantity,
                    'site_access' => $location
                ]);
            }
        }

        // Process the Excel rows to update or create products
        foreach ($rows as $row) {
            if (!isset($row['sku']) || $row['sku'] === null || $row['sku'] === '') {
        continue;
    }
            $product1 = ErpProduct::where(['sku' => $row['sku'], 'site_access' => $location])->first();
            $type = '';

            // Determine if the product exists
            if ($product1) {
                // Compare and update quantities
                if ($product1->warehouse_quantity > $row['quantity']) {
                    $type = 'less';
                } elseif ($product1->warehouse_quantity < $row['quantity']) {
                    $type = 'add';
                } else {
                    $type = 'update'; // No change in quantity
                }
                $product1->warehouse_quantity = $row['quantity'];
            } else {
                // Create a new product if it doesn't exist
                $product1 = new ErpProduct;
                $product1->sku = $row['sku'];
                $product1->warehouse_quantity = $row['quantity'];
                $product1->quantity = 0;
                $type = 'add';
            }

            // Set the site access and save the product
            $product1->site_access = $location;
            $product1->save();

            // Create a history entry for the current product update or creation
            ErpHistoryManager::create([
                'sheet_id' => $sheetId,
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'type' => $type,
                'date' => $sheetDate,
                'stock' => $product1->warehouse_quantity,
                'site_access' => $location
            ]);
        }
    }
}
