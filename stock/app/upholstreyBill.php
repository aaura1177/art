<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class upholstreyBill extends Model
{
    protected $table = 'upholestry_bill';
    protected $fillable =
    [
        'product_id',  
        'invoice_id',
        'quantity',
        'upholestry_rate',
        'amount',
        'contractor_id'
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
