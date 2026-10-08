<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class invoiceTableEu extends Model
{
    protected $table = 'invoiceTable_eu';
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
        return $this->belongsTo('App\invoiceeu');
    }

    public function product()
    {
        return $this->belongsTo('App\product', 'product_id', 'id');
    }
}
