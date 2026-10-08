<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesaleAllocation extends Model
{
    use SoftDeletes;

    protected $table = 'wholesale_allocations';

    protected $fillable = [
        'wholesale_shipment_id',
        'wholesale_shipment_item_id',
        'product_id',
        'product_sku',
        'supplier_id',
        'asked_quantity',
        'rate',
        'status',
        'purchase_order_id',
    ];

    public function shipment()
    {
        return $this->belongsTo(WholesaleShipment::class, 'wholesale_shipment_id');
    }

    public function item()
    {
        return $this->belongsTo(WholesaleShipmentItem::class, 'wholesale_shipment_item_id');
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(supplier::class, 'supplier_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(purchaseOrder::class, 'purchase_order_id');
    }
}
