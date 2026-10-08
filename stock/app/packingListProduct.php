<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class packingListProduct extends Model
{
    protected $table = 'packinglistproducts';
   protected $fillable = [
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
        'qtybox',
        'batch_no',
        'ref_source_snapshot',
    ];
    public function product()
    {
        return $this->belongsTo('App\product');
    }

protected $casts = [
  
    'batch_no' => 'array',
    'ref_source_snapshot' => 'array',
];


    
}
