<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ManualStockoutPendingConsumable extends Model
{
    protected $table = 'manual_stockout_pending_consumables';

    protected $fillable = [
        'invoice_id',
        'stock_id',
        'product_id',
        'consumable_id',
        'location',
        'reason',
        'status',
    ];

    public function consumable()
    {
        return $this->belongsTo(consumable::class, 'consumable_id');
    }

    public function invoice()
    {
        return $this->belongsTo(invoice::class, 'invoice_id');
    }

    public function stockout()
    {
        return $this->belongsTo(stockout::class, 'stock_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id', 'id');
    }
}

