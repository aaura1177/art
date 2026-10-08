<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class spbTable extends Model
{
    protected $table = 'sample_pb_table';
    protected $fillable =
    [
        'sample_id',
        'purchaseOrder_id',
        'sample_purchasebill_id',
        'EAN',
        'orderqty',
        'receiveqty',
        'remainingqty',
        'prod_remaining',
        'rate',
        'amount',
		'location',
		'remarks',
    ];

    public function samplePurchaseBillTable()
    {
        return $this->belongsTo('App\samplePurchaseBill','sample_purchasebill_id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function sample()
    {
        return $this->belongsTo('App\sample');
    }

     public function samplePurchaseOrder()
    {
        return $this->belongsTo('App\samplePurchaseOrder', 'purchaseOrder_id', 'id');
    }
}
