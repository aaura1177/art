<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class posTable extends Model
{
    protected $table = 'posTable';
    protected $fillable =
    [
        'product_id',  
        'sample_id',  
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
        'legs'
    ];

    public function samplePurchaseOrderTable()
    {
        return $this->belongsTo('App\samplePurchaseOrder', 'poid', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function sample()
    {
        return $this->belongsTo('App\sample');
    }
}
