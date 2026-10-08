<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class psTable extends Model
{
    protected $table = 'pstable';
    protected $fillable =
    [
        'product_id',
        'invoice_id',
        'packingSheet_id',
        'ean',
        'quantity',
        'box',
        'qtybox',
        'netwt',
        'grosswt',
    ];

    public function packingSheet()
    {
        return $this->belongsTo('App\packingSheet');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
