<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\ErpProduct;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;
use App\ErpHistoryManager;
use App\ErpSheetManager;


class ErpProductLessImportManager implements ToCollection, WithHeadingRow
{
    use Importable;

    /**
     * @param Collection $collection
     */

    public function collection(Collection $rows)
    {
        $location = \Request::session()->get('country');
        foreach ($rows as $row) {
            $sheet = ErpSheetManager::where('type', 'less')->orderBy('id', 'desc')->first();
            $product1 = ErpProduct::where(['sku' => $row['sku'], 'site_access' => $location])->first();
            if (isset($product1->id)) {
                $quantity = $product1->warehouse_quantity - $row['quantity'];
                $product1->warehouse_quantity = $quantity;
            } else {
                $product1 = new ErpProduct;
                $product1->sku = $row['sku'];
                $product1->warehouse_quantity = $row['quantity'];
            }
            $product1->site_access = $location;
            $product1->save();
            $artisan_wayfair = '(Artisan)';
            if (isset($row['artisan_wayfair'])) {
                $artisan_wayfair = '(' . $row['artisan_wayfair'] . ')';
            }

            $history = ErpHistoryManager::create([
                'sheet_id' => $sheet->id,
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'type' => 'less',
                'date' => $sheet->date,
                'stock' => $product1->warehouse_quantity,
                'remark' => 'Less via Order id - ' . $row['order_id'] . $artisan_wayfair,
                'site_access' => $location
            ]);
        }
    }
}
