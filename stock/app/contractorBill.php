<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class contractorBill extends Model
{
    protected $table = 'contractor_bill';
    protected $fillable =
    [
        'product_id',  
        'invoice_id',
        'quantity',
        'finishing_rate',
        'amount',
        'contractor_id',
		'finishing'
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
