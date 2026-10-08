<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubServicePbTable extends Model
{
    protected $table = 'sub_service_pb_table';
    protected $fillable = [
        'purchase_bill_id',
        'pb_id',
        'product_id',
        'sub_name',
        'sub_rate',
        'sub_amount',
        'sub_receivepercentage',
        'sub_receiveqty',
        'sub_gstslab',
        'sub_gstamount',
    ];

    public function purchaseBillProduct()
{
    return $this->belongsTo(ServicePbTable::class, 'pb_id');
}
   
}
