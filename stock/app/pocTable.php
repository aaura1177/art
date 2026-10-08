<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class pocTable extends Model
{
    protected $table = 'pocTable';
    protected $fillable =
    [
        'consumable_id',
        'quantity',
        'unit',
        'remqty',
        'rate',
        'amount',
        'gstslab',
        'gstamount',
        'poid',
        'description',
    ];

    public function purchaseOrderTable()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'poid', 'id');
    }

    public function consumable()
    {
        return $this->belongsTo('App\consumable');
    }

    
    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
