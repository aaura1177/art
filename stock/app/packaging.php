<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class packaging extends Model
{
    protected $table = 'packaging';
    protected $fillable =
    [
        // 'employee_id',  
        // 'invoices',
        // 'type',
        // 'quantity',
		'product_id',
		'box1_height',
		'box1_width',
		'box1_depth',
		'box2_height',
		'box2_width',
		'box2_depth',
		'box1_sqinch',
		'box2_sqinch',
		'no_of_boxes',
		'box1_ply',
		'box2_ply',
		'box1_type',
		'box2_type',
		'box_2_qty',
		'box_1_qty',
		
    ];
	
	public function product()
    {
        return $this->belongsTo('App\product');
    }

}
