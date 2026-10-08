<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockLogCarton extends Model
{
    protected $table = 'stock_log_carton';

    protected $fillable = [
        'product_id',
        'ref_no',
        'voucher_no',
        'type',
        'quantity',
        'opening_balance',
        'remaining_stock',
        'quantity2',          
        'opening_balance2',   
        'remaining_stock2',   
        'created_at',
        'updated_at',
        'supplier_inv_no',
        'entity_id',
        'supplier_name',
        'batch_balance',
        'batch_no',
        'batch_balance2',
    ];

    public function product()
    {
        return $this->belongsTo('App\product', 'product_id', 'id');
    }
}
