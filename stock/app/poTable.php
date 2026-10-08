<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class poTable extends Model
{
    protected $table = 'poTable';
    protected $fillable =
    [
        'product_id',  
        'quantity',
        'unit',
        'remqty',
        'rate',
        'amount',
        'gstslab',
        'gstamount',
        'poid',
        'ean',
        'priority',
        'delivery_point',
        'legs',
             'discount',
        'discount_type',
        'remaining_discount',
        'description'
    ];

    public function purchaseOrderTable()
    {
        return $this->belongsTo('App\purchaseOrder', 'poid', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
