<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class supplierProduct extends Model
{
    protected $table = 'supplier_products';
    protected $fillable =
    [
        'product_id',
		'supplier_id',
		'rate',
        'uk_45_rate',
        'effective_date',
        'pending_rate',
        'admin_approved'
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }
    public function supplier(){
        return $this->belongsTo('App\supplier');
    }

    public function priceLogs()
    {
        return $this->hasMany(SupplierProductPriceLog::class, 'supplier_product_id');
    }
}
