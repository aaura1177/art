<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicePbTable extends Model
{
    protected $table = 'service_pb_table';
    protected $fillable =
    [
        'product_id',
        'purchaseOrder_id',
        'purchaseBill_id',
        'unit',
        'orderqty',
        'receiveqty',
        'remainingqty',
        'rate',
        'amount',
		'location',
		'remarks',
		'receivepercentage',
    ];

    public function purchaseBillTable()
    {
        return $this->belongsTo('App\servicePurchaseBill','purchasebill_id');
    }

    public function ServiceProduct()
    {
        return $this->belongsTo('App\ServiceProduct', 'product_id', 'id');
    }

     public function purchaseOrder()
    {
        return $this->belongsTo('App\Service', 'purchaseOrder_id', 'id');
    }
}
