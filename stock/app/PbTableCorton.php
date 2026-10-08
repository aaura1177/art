<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PbTableCorton extends Model
{
    protected $table = 'pb_table_carton';

    protected $fillable = [
        'product_id',
        'purchaseOrder_id',
        'purchaseBill_id',
        'ean',
        'orderqty',
        'receiveqty',
        'remainingqty',
        'rate',
        'rate2',
        'amount',
        'location',
        'remarks',
        'orderqty2',
        'receiveqty2',
        'remainingqty2',
    ];

    public function purchaseBillTable()
    {
        return $this->belongsTo('App\PurchaseBillCarton', 'purchaseBill_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product','product_id', 'id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'purchaseOrder_id', 'id');
    }
}
