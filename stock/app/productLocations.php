<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class productLocations extends Model
{
    protected $table = 'product_locations';
    protected $fillable = 
    [
        'product_id',
		'location',
		'quantity'
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
