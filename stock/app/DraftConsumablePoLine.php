<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DraftConsumablePoLine extends Model
{
    protected $table = 'draft_consumable_po_lines';

    protected $fillable = [
        'draft_consumable_purchase_order_id',
        'consumable_id',
        'quantity',
        'unit',
        'rate',
        'amount',
        'gstslab',
        'gstamount',
        'description',
    ];

    public function draftConsumablePurchaseOrder()
    {
        return $this->belongsTo(DraftConsumablePurchaseOrder::class, 'draft_consumable_purchase_order_id');
    }

    public function consumable()
    {
        return $this->belongsTo(consumable::class, 'consumable_id');
    }
}
