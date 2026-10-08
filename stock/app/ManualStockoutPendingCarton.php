<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ManualStockoutPendingCarton extends Model
{
    protected $table = 'manual_stockout_pending_cartons';

    protected $fillable = [
        'invoice_id',
        'stock_id',
        'product_id',
        'location',
        'reason',
        'short_qty_box1',
        'short_qty_box2',
        'status',
    ];

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

