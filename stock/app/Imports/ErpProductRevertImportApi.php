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
use App\ErpHistory;
use App\ErpSheet;


class ErpProductRevertImportApi implements ToCollection, WithHeadingRow
{
    use Importable;

    /**
     * @param Collection $collection
     */


     protected $location;
     protected $remarks;

     public function __construct($location, $remarks)
     {
        $this->location = $location;
        $this->remarks = $remarks;
     } 

    public function collection(Collection $rows)
    {
        $location = $this->location;
        foreach ($rows as $row) {
            $sheet = ErpSheet::where(['type' => 'less', 'site_access' => $location])->orderBy('id', 'desc')->first();
            $product1 = ErpProduct::where(['sku' => $row['sku'], 'site_access' => $location])->first();
            if (isset($product1->id)) {
                $quantity = $product1->quantity - $row['quantity'];
                $product1->quantity = $quantity;
            } else {
                $product1 = new ErpProduct;
                $product1->sku = $row['sku'];
                $product1->quantity = ($row['quantity'] == 0) ? 0 : -$row['quantity'];
            }
            $product1->site_access = $location;

            $product1->save();
            $artisan_wayfair = '(Artisan)';
            if (isset($row['artisan_wayfair'])) {
                $artisan_wayfair = '(' . $row['artisan_wayfair'] . ')';
            }
            $history = ErpHistory::create([
                'sheet_id' => $sheet->id,
                'sku' => $row['sku'],
                'quantity' => $row['quantity'],
                'type' => 'less',
                'date' => $sheet->date,
                'stock' => $product1->quantity,
                'remark' => $this->remarks,
                'site_access' => $location
            ]);
        }
    }
}
