<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class smallHardwareProducts extends Model
{
    protected $table = 'small_hardware_products';
    protected $fillable =
    [
    	'product_id',
    	'small_hardware_id',
    	'size',
    	'quantity',
    	'price',
    ];

    public function smallhardwares()
    {
        return $this->belongsTo('App\smallhardwares', 'small_hardware_id', 'id');
    }

     public function product()
    {
        return $this->belongsTo('App\product', 'product_id', 'id');
    }
}
