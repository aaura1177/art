<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class porTable extends Model
{
    protected $table = 'porTable';
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
        'ean'
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
