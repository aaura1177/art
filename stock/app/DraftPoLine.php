<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DraftPoLine extends Model
{
    protected $table = 'draft_po_lines';

    protected $fillable = [
        'draft_purchase_order_id',
        'product_id',
        'EAN',
        'quantity',
        'unit',
        'rate',
        'amount',
        'gstslab',
        'gstamount',
        'priority',
        'delivery_point',
        'legs',
        'discount',
        'discount_type',
        'remaining_discount',
        'description',
    ];

    public function draftPurchaseOrder()
    {
        return $this->belongsTo(DraftPurchaseOrder::class, 'draft_purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
}
