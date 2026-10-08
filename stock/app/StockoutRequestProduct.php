<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockoutRequestProduct extends Model
{
    protected $table = 'stockout_request_products';
        public $timestamps = false;

    protected $fillable = [
        'backup_id',
        'product_id',
        'ean',
        'orderqty',
        'receiveqty',
        'remainingqty',
        'location'
    ];

    // Each product belongs to one backup
    public function backup()
    {
        return $this->belongsTo(StockoutRequestBackup::class, 'backup_id', 'id');
    }

    public function product(){
        return $this->belongsTo(product::class,'product_id');
    }
}
