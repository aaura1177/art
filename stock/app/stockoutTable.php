<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class stockoutTable extends Model
{
    protected $table = 'stockoutable';
    protected $fillable =
    [
        'invoice_id',
        'stock_id',
        'product_id',
        'ean',
        'orderqty',
        'receiveqty',
        'remainingqty',
		'location',
		'supp_inv_no',
       
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function invoice()
    {
        return $this->belongsTo('App\invoice');
    }

    public function stockout()
    {
        return $this->belongsTo('App\stockout','stock_id', 'id' );
    }
}
