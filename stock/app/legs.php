<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class legs extends Model
{
    protected $table = 'legs';
    protected $fillable =
    [
        // 'employee_id',  
        // 'invoices',
        // 'type',
        // 'quantity',
		'product_id',
		'leg_design',
		'qty',
		'price',
		'size',
		'height',
		'width',
		'depth'
    ];
	
	public function product()
    {
        return $this->belongsTo('App\product');
    }

}
