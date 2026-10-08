<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class DataImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if ($key === 0) continue; 

            Product::updateOrCreate(
                ['code' => $row[0]],  
                [
                    'name' => $row[1],
                    'rong_ean' => $row[2],
                    'right_ean' => $row[3],
                    'category_name' => $row[4],
                    'sub_category_name' => $row[5],
                ]
            );
        }
    }
}

