<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class pbTable extends Model
{
    protected $table = 'pb_table';
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
         'mulitple_po_purchaseBill',
		'remarks',
        	'discount_type',
		'discount',
    ];

    public function purchaseBillTable()
    {
        return $this->belongsTo('App\purchaseBill','purchasebill_id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }

     public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrder', 'purchaseOrder_id', 'id');
    }
}
