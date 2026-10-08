<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class stockLogConsumable     extends Model
{
    protected $table = 'stock_log_consumable';
    protected $fillable =
    [
        'consumable_id',
        'product_id',
        'ref_no',
        'voucher_no',
        'type',
        'quantity',
        'opening_balance',
        'remaining_stock',
		'created_at',
		'supplier_inv_no',
        'invoice_id',
        'entity_id',
        'supplier_name',
        'supplier_id',
        'batch_balance',
        'batch_no',
        'remark',
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
