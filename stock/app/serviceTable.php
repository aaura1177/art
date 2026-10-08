<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class serviceTable extends Model
{
    protected $table = 'serviceTable';
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
        'remaining_percentage',
        'remaining_amount',
        'service_category_id',
        'description'
    ];

    public function purchaseOrderTable()
    {
        return $this->belongsTo('App\Service', 'poid', 'id');
    }

    public function sub_serviceproduct()
    {
        return $this->hasMany('App\SubServiceTable', 'serviceTable_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\ServiceProduct');
    }
}
