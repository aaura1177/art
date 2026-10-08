<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class pbTableConsumable extends Model
{
    protected $table = 'pb_table_consumables';
    protected $fillable =
    [
        'product_id',
        'purchaseOrder_id',
        'purchaseBill_id',
        'ean',
        'orderqty',
        'receiveqty',
        'remainingqty',
        'rate',
        'amount',
		'location',
		'remarks',
		'unit',
    ];

    public function purchaseBillTable()
    {
        return $this->belongsTo('App\purchaseBillConsumable','purchasebill_id');
    }

    public function consumable()
    {
        return $this->belongsTo('App\consumable','product_id','id');
    }

     public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'purchaseOrder_id', 'id');
    }
}
