<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class packingListProduct extends Model
{
    protected $table = 'packinglistproducts';
    protected $fillable =
    [
        'packinglist_id',
        'product_id',
        'quantity',
        'weight',
        'subtotalnetwt',
        'grosswt',
        'subtotalgrosswt',
        'box',
        'endBox',
        'subTotalBox',
        'qtybox'
    ];
	
    public function product()
    {
        return $this->belongsTo('App\product');
    }
    
}
