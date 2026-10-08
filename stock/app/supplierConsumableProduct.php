<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class supplierConsumableProduct extends Model
{
    protected $table = 'supplier_consumable_products';
    protected $fillable =
    [
        'product_id',
		'supplier_id',
		'rate',
        'effective_date',
        'pending_rate',
        'admin_approved'
    ];

    public function product()
    {
        return $this->belongsTo('App\consumable');
    }
    public function supplier(){
        return $this->belongsTo('App\supplier');
    }


}
