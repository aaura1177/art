<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UniqueReferenceNumberProduct extends Model
{
    protected $table = 'unique_referencenumber_product';

    protected $fillable = [
        'unique_referencenumber_id',
        'product_id',
        'originalqty',
        'remaining_qty',
        'remark'
    ];

    /**
     * Relation: Belongs to Unique Reference Number
     */
    public function reference()
    {
        return $this->belongsTo(UniqueReferenceNumber::class, 'unique_referencenumber_id');
    }

    /**
     * Relation: Belongs to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}