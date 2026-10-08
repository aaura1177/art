<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class performanceCardProduct extends Model
{
    protected $table = 'performance_card_products';
    protected $fillable =
    [
        'performance_card_id',
		'product_id',
		'qty_received',
		'qty_rejected',
		'net_qty',
		'remarks',
    ];
	
	
	 public function product(){
		return $this->belongsTo('App\product');
	}
    
    
}
