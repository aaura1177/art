<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class invoiceTable extends Model
{
    protected $table = 'invoiceTable';
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
        return $this->belongsTo('App\invoice');
    }

    public function product()
    {
        return $this->belongsTo('App\product', 'product_id', 'id');
    }
}
