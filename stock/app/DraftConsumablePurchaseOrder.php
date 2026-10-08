<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DraftConsumablePurchaseOrder extends Model
{
    public const STATUS_OPEN = 0;

    public const STATUS_COMPLETE = 1;

    protected $table = 'draft_consumable_purchase_orders';

    protected $fillable = [
        'draft_pono',
        'supplier_id',
        'podate',
        'del_date',
        'buyer_orderno',
        'payterms',
        'remarks',
        'month',
        'subTotal',
        'tgst',
        'tquantity',
        'tamount',
        'address_option',
        'status',
        'send_to_supplier_status',
        'converted_purchase_order_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(supplier::class, 'supplier_id');
    }

    public function lines()
    {
        return $this->hasMany(DraftConsumablePoLine::class, 'draft_consumable_purchase_order_id');
    }

    public function convertedPurchaseOrder()
    {
        return $this->belongsTo(purchaseOrderConsumable::class, 'converted_purchase_order_id');
    }

    public function isOpen(): bool
    {
        return (int) $this->status === self::STATUS_OPEN;
    }

    public function isSentToSupplier(): bool
    {
        return (int) ($this->send_to_supplier_status ?? 0) === 1;
    }
}
