<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\product;
use App\StockLogCarton;

class StockOutTableCarton extends Model
{
    protected $table = 'stockouttable_carton';

    protected $fillable = [
        'product_id',
        'stock_id',
        'orderqty',
        'orderqty2',
        'receiveqty',
        'receiveqty2',
        'remainingqty',
        'remainingqty2',
        'location',
        'supp_inv_no',
        'batch_no',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(product::class, 'product_id', 'id');
    }

    public function stockout()
    {
        return $this->belongsTo(StockLogCarton::class, 'stock_id', 'id');
    }
}
