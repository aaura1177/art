<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\consumable;


class ConsumableImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
    	foreach($rows as $row){
			consumable::create([
				'name' => $row['item'],
				'unit' => $row['unit'],
				'price' => $row['price'],
				'payment_terms' => $row['payment_terms'],
			]);
    	}

    }
}
