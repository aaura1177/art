<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesaleShipmentItem extends Model
{
    use SoftDeletes;

    protected $table = 'wholesale_shipment_items';

    protected $fillable = [
        'wholesale_shipment_id',
        'sku',
        'product_id',
        'qty',
        'allocated_qty',
    ];

    public function shipment()
    {
        return $this->belongsTo(WholesaleShipment::class, 'wholesale_shipment_id');
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }

    public function allocations()
    {
        return $this->hasMany(WholesaleAllocation::class, 'wholesale_shipment_item_id');
    }

    public function unallocatedQty(): int
    {
        return max(0, (int) $this->qty - (int) $this->allocated_qty);
    }
}
