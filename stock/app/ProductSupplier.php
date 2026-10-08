<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    use HasFactory;
    protected $table='product_suppliers';
    protected $fillable = [
        'product_id',
        'supplier_id',
        'price',
    ];

    // Define the relationship to Product
    public function product()
    {
        return $this->belongsTo(product::class);
    }

    // Define the relationship to Supplier
    public function supplier()
    {
        return $this->belongsTo(supplier::class);
    }
}
