<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\packaging;


class PackagingImport implements ToCollection, WithHeadingRow
{
	use Importable;

    /**
    * @param Collection $collection
    */

    public function collection(Collection $rows)
    {
		foreach($rows as $row){
			$product1 = product::where('code', $row['code'])->first();
			if(isset($product1->id)){
				$product_id = $product1->id;

				$packaging = packaging::where('product_id',$product_id)->first();
				if(isset($packaging->id)){
					$packaging->product_id 	= $product_id;
					$packaging->no_of_boxes = ($row['number_of_boxes'])?$row['number_of_boxes']:0;
					$packaging->box1_height = ($row['box1_height'])?$row['box1_height']:0;
					$packaging->box1_width 	= ($row['box1_width'])?$row['box1_width']:0;
					$packaging->box1_depth 	= ($row['box1_depth'])?$row['box1_depth']:0;
					$packaging->box1_sqinch = ($row['box1_sqinch'])?$row['box1_sqinch']:0;
					$packaging->box2_height = ($row['box2_height'])?$row['box2_height']:0;
					$packaging->box2_width 	= ($row['box2_width'])?$row['box2_width']:0;
					$packaging->box2_depth 	= ($row['box2_depth'])?$row['box2_depth']:0;
					$packaging->box2_sqinch = ($row['box2_sqinch'])?$row['box2_sqinch']:0;
					$packaging->box1_ply 	= ($row['box1_ply'])?$row['box1_ply']:0;
					$packaging->box2_ply 	= ($row['box2_ply'])?$row['box2_ply']:0;
					$packaging->box1_type 	= ($row['box1_type'])?$row['box1_type']:'';
					$packaging->box2_type 	= ($row['box2_type'])?$row['box2_type']:'';

					$packaging->save();
				}else{
					packaging::create([
						'product_id' 	=> $product_id,
						'no_of_boxes' 	=> ($row['number_of_boxes'])?$row['number_of_boxes']:0,
						'box1_height' 	=> ($row['box1_height'])?$row['box1_height']:0,
						'box1_width' 	=> ($row['box1_width'])?$row['box1_width']:0,
						'box1_depth' 	=> ($row['box1_depth'])?$row['box1_depth']:0,
						'box1_sqinch' 	=> ($row['box1_sqinch'])?$row['box1_sqinch']:0,
						'box2_height' 	=> ($row['box2_height'])?$row['box2_height']:0,
						'box2_width' 	=> ($row['box2_width'])?$row['box2_width']:0,
						'box2_depth' 	=> ($row['box2_depth'])?$row['box2_depth']:0,
						'box2_sqinch' 	=> ($row['box2_sqinch'])?$row['box2_sqinch']:0,
						'box1_ply' 		=> ($row['box1_ply'])?$row['box1_ply']:0,
						'box2_ply' 		=> ($row['box2_ply'])?$row['box2_ply']:0,
						'box1_type' 	=> ($row['box1_type'])?$row['box1_type']:'',
						'box2_type' 	=> ($row['box2_type'])?$row['box2_type']:'',
					]);
				}
			}
		}
    }
}
