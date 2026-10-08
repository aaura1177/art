<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockOutCartonSwapSource extends Model
{
    protected $table = 'stockout_carton_swap_sources';

    protected $fillable = [
        'stockout_carton_id',
        'stock_id',
        'invoice_id',
        'target_product_id',
        'source_product_id',
        'swap_priority',
        'box1_qty',
        'box2_qty',
        'carton_swapping_id',
    ];
}
