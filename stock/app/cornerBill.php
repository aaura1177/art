<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class cornerBill extends Model
{
    protected $table = 'corner_bill';
    protected $fillable =
    [
        'product_id',  
        'invoice_id',
        'corners',
        'total_corners',
        'total_corners_amount',
		'l',
        'total_l',
        'total_l_amount',
        'product_quantity'
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
