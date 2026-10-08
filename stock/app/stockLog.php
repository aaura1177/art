<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class stockLog extends Model
{
    protected $table = 'stock_log';
    protected $fillable =
    [
        'product_id',
        'ref_no',
        'voucher_no',
        'type',
        'quantity',
        'opening_balance',
        'remaining_stock',
		'created_at',
		'supplier_inv_no',
        'entity_id',
        'supplier_name',
        'batch_balance',
        'batch_no',
        'buyer_name',
        'reference_number',
        'reference_quantity',
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
