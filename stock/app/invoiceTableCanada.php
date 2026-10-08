<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class invoiceTableCanada extends Model
{
    protected $table = 'invoiceTable_canada';
    protected $fillable =
    [
        'product_id',  
        'invoice_id',
        'quantity',
        'rate',
        'amount',
        'weight',
        'subtotalnetwt',
        'grosswt',
        'subtotalgrosswt',
        'gstslab',
        'gstamount',
        'remqty',
        'box',
        'endbox',
        'subtotalbox',
        'qtybox',
        'descriptionBox'
    ];

    public function invoice()
    {
        return $this->belongsTo('App\invoicecanada');
    }

    public function product()
    {
        return $this->belongsTo('App\product', 'product_id', 'id');
    }
}
