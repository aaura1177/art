<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class quality extends Model
{
     protected $table = 'quality';
    protected $fillable =
    [
    	'product_id',
        'channel_id',
        'supplier_inv_no',
    	'status',
    	'quantity',
    	'date',
    	'quantity',
    	'remarks',
		'imageURL'
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function channel()
    {
        return $this->belongsTo('App\channel');
    }
}
