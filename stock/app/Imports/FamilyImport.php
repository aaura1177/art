<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\productGrouping;


class FamilyImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
		foreach($rows as $row){
			$product1 = product::where('code', $row['similar_code'])->first();
			if(isset($product1->id)){
				$product2 = product::where('code', $row['code'])->first();
				if(isset($product2->id)){
					$group = productGrouping::where('parent_id',$product1->id)->where('child_id',$product2->id)->first();
					if(!isset($group->id)){
						productGrouping::create([
							'parent_id' => $product1->id,
							'child_id' => $product2->id,
							'parent_finish' => $row['finish_of_similar_code'],
							'child_finish' => $row['finish']
						]);
					}
				}
			}
		}
    }
}
