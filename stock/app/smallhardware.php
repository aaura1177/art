<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class smallhardware extends Model
{
    protected $table = 'small_hardware';
    protected $fillable =
    [
		'product_id',
		'dust_cover_size',
		'dust_cover_price',
		'pouch',
		'pouch_price',
        'supplier_id'
    ];
	
	public function product()
    {
        return $this->belongsTo('App\product');
    }

}
